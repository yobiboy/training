<?php

namespace App\Models;

use Database\Factories\RoutingHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['disbursement_voucher_id', 'processing_stage_id', 'action', 'remarks', 'acted_at'])]
class RoutingHistory extends Model
{
    /** @use HasFactory<RoutingHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (self $routingHistory) {
            if (Auth::check()) {
                $routingHistory->acted_by ??= Auth::id();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acted_at' => 'datetime',
        ];
    }

    public function disbursementVoucher(): BelongsTo
    {
        return $this->belongsTo(DisbursementVoucher::class);
    }

    public function processingStage(): BelongsTo
    {
        return $this->belongsTo(ProcessingStage::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
