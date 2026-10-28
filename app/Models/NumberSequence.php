<?php

namespace App\Models;

use App\Enums\SequenceResetPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $format
 * @property string|null $prefix
 * @property int $next_number
 * @property int $padding
 * @property SequenceResetPeriod $reset_period
 * @property Carbon|null $last_reset_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'name', 'format', 'prefix', 'next_number', 'padding', 'reset_period', 'last_reset_at'])]
class NumberSequence extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'padding' => 'integer',
            'reset_period' => SequenceResetPeriod::class,
            'last_reset_at' => 'datetime',
        ];
    }
}
