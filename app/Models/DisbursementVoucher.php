<?php

namespace App\Models;

use App\Models\Concerns\TracksAuthenticatedUser;
use Database\Factories\DisbursementVoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable([
    'dv_no', 'payee_name', 'fund_id', 'amount', 'particulars', 'current_stage_id',
    'status', 'submitted_at', 'completed_at',
])]
class DisbursementVoucher extends Model
{
    /** @use HasFactory<DisbursementVoucherFactory> */
    use HasFactory, SoftDeletes, TracksAuthenticatedUser;

    /**
     * Human-readable labels for every workflow status.
     *
     * @var array<string, string>
     */
    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'in_process' => 'In Process',
        'returned' => 'Returned',
        'for_payment' => 'For Payment',
        'completed' => 'Completed',
    ];

    /**
     * Aging buckets: key => [label, minimum age in days, maximum age in days].
     *
     * @var array<string, array{0: string, 1: int, 2: ?int}>
     */
    public const AGING_BUCKETS = [
        '0-7' => ['0–7 days', 0, 7],
        '8-15' => ['8–15 days', 8, 15],
        '16-30' => ['16–30 days', 16, 30],
        '31+' => ['Over 30 days', 31, null],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Generate the next DV number for the current year (e.g. DV-2026-001).
     */
    public static function generateNextDvNo(): string
    {
        $prefix = 'DV-'.now()->year.'-';

        $lastSequence = static::withTrashed()
            ->where('dv_no', 'like', $prefix.'%')
            ->pluck('dv_no')
            ->map(fn (string $dvNo): int => (int) substr($dvNo, strlen($prefix)))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($lastSequence + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Submit the voucher into the workflow, placing it at the Finance Processor
     * stage. A returned voucher keeps its DV number and is logged as resubmitted.
     */
    public function submit(): void
    {
        $routingAction = $this->status === 'returned' ? 'resubmitted' : 'submitted';
        $financeProcessorStage = ProcessingStage::query()->where('name', ProcessingStage::FINANCE_PROCESSOR)->firstOrFail();

        DB::transaction(function () use ($financeProcessorStage, $routingAction) {
            $this->update([
                'status' => 'submitted',
                'current_stage_id' => $financeProcessorStage->id,
                'submitted_at' => now(),
            ]);

            RoutingHistory::create([
                'disbursement_voucher_id' => $this->id,
                'processing_stage_id' => $financeProcessorStage->id,
                'action' => $routingAction,
                'acted_at' => now(),
            ]);
        });
    }

    /**
     * Requesting units may only delete vouchers that have not entered, or have
     * been sent back out of, the Finance workflow.
     */
    public function isDeletableByRequester(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }

    /**
     * Soft delete the voucher on the requesting unit's behalf, recording who
     * deleted it in updated_by first. Admins can still view and restore it.
     */
    public function deleteByRequester(): void
    {
        if (! $this->isDeletableByRequester()) {
            throw new LogicException("Voucher {$this->dv_no} can no longer be deleted by the requesting unit.");
        }

        DB::transaction(function () {
            $this->forceFill(['updated_by' => Auth::id()])->save();
            $this->delete();
        });
    }

    /**
     * Whole days since the voucher was submitted, counted up to today while it
     * is still open and frozen at completion once completed. Null for drafts.
     */
    public function ageInDays(): ?int
    {
        if ($this->submitted_at === null) {
            return null;
        }

        return (int) $this->submitted_at->diffInDays($this->completed_at ?? now());
    }

    /**
     * Whole days since the voucher's last routing step, i.e. how long it has
     * been sitting where it is now. Null for drafts and completed vouchers.
     * Uses a preloaded `withMax('routingHistories', 'acted_at')` when present.
     */
    public function daysAtCurrentStage(): ?int
    {
        if (in_array($this->status, ['draft', 'completed'], true)) {
            return null;
        }

        $lastActedAt = $this->routing_histories_max_acted_at ?? $this->routingHistories()->max('acted_at');

        return $lastActedAt ? (int) Carbon::parse($lastActedAt)->diffInDays(now()) : null;
    }

    /**
     * The AGING_BUCKETS key the voucher's age falls into, or null for drafts.
     */
    public function agingBucket(): ?string
    {
        $age = $this->ageInDays();

        if ($age === null) {
            return null;
        }

        foreach (self::AGING_BUCKETS as $key => [, $minDays, $maxDays]) {
            if ($age >= $minDays && ($maxDays === null || $age <= $maxDays)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Limit to open (submitted but not yet completed) vouchers whose age falls
     * in the given AGING_BUCKETS key.
     */
    #[Scope]
    protected function openAndAged(Builder $query, string $bucket): void
    {
        [, $minDays, $maxDays] = self::AGING_BUCKETS[$bucket];

        $query->whereNotNull('submitted_at')
            ->whereNotIn('status', ['draft', 'completed'])
            ->where('submitted_at', '<=', now()->subDays($minDays))
            ->when($maxDays !== null, fn (Builder $query) => $query->where('submitted_at', '>', now()->subDays($maxDays + 1)));
    }

    /**
     * The remarks left the last time this voucher was returned, if any.
     */
    public function latestReturnRemarks(): ?string
    {
        return $this->routingHistories()
            ->where('action', 'returned')
            ->latest('acted_at')
            ->value('remarks');
    }

    /**
     * Limit to submitted vouchers waiting in the Finance Processor's queue.
     */
    #[Scope]
    protected function awaitingFinanceProcessor(Builder $query): void
    {
        $query->where('status', 'submitted')
            ->whereRelation('currentStage', 'name', ProcessingStage::FINANCE_PROCESSOR);
    }

    /**
     * Limit to forwarded vouchers waiting in the Supervisor's queue for completion.
     */
    #[Scope]
    protected function awaitingSupervisor(Builder $query): void
    {
        $query->where('status', 'for_payment')
            ->whereRelation('currentStage', 'name', ProcessingStage::SUPERVISOR);
    }

    /**
     * Limit to the vouchers that concern the given user's role, for reports:
     * everything for admins, the office's vouchers for a requesting unit,
     * everything that entered the workflow for the Finance Processor, and
     * everything forwarded on for the Supervisor.
     */
    #[Scope]
    protected function reportableBy(Builder $query, User $user): void
    {
        match ($user->primaryRole()) {
            'super_admin' => null,
            'finance_supervisor' => $query->whereIn('status', ['for_payment', 'completed']),
            'finance_processor' => $query->where('status', '!=', 'draft'),
            'requesting_unit' => $query->visibleToOfficeOf($user),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Limit to vouchers created by anyone in the given user's office, or only
     * the user's own vouchers when they have no office assigned.
     */
    #[Scope]
    protected function visibleToOfficeOf(Builder $query, User $user): void
    {
        if ($user->office_id === null) {
            $query->where('created_by', $user->id);

            return;
        }

        $query->whereHas('createdBy', fn (Builder $query) => $query->where('office_id', $user->office_id));
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(ProcessingStage::class, 'current_stage_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function routingHistories(): HasMany
    {
        return $this->hasMany(RoutingHistory::class);
    }
}
