<?php

namespace App\Models;

use App\Models\Concerns\TracksAuthenticatedUser;
use Database\Factories\ProcessingStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'sequence', 'is_required'])]
class ProcessingStage extends Model
{
    /** @use HasFactory<ProcessingStageFactory> */
    use HasFactory, SoftDeletes, TracksAuthenticatedUser;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function disbursementVouchers(): HasMany
    {
        return $this->hasMany(DisbursementVoucher::class, 'current_stage_id');
    }

    public function routingHistories(): HasMany
    {
        return $this->hasMany(RoutingHistory::class);
    }
}
