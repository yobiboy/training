<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

trait TracksAuthenticatedUser
{
    protected static function bootTracksAuthenticatedUser(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by ??= Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
