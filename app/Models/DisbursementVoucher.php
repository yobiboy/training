<?php

namespace App\Models;

use App\Models\Concerns\TracksAuthenticatedUser;
use Database\Factories\DisbursementVoucherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'dv_no', 'payee_name', 'fund_id', 'amount', 'particulars', 'current_stage_id',
    'status', 'submitted_at', 'completed_at',
])]
class DisbursementVoucher extends Model
{
    /** @use HasFactory<DisbursementVoucherFactory> */
    use HasFactory, SoftDeletes, TracksAuthenticatedUser;

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
