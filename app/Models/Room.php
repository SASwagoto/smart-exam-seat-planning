<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'building',
        'total_rows',
        'total_cols',
        'total_seats',
        'disabled_seats',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_rows' => 'integer',
        'total_cols' => 'integer',
        'total_seats' => 'integer',
        'disabled_seats' => 'array',
    ];

    public function getEffectiveCapacityAttribute(): int
    {
        $baseCapacity = $this->total_seats ?? ($this->total_rows * $this->total_cols);
        $disabledCount = is_array($this->disabled_seats) ? count($this->disabled_seats) : 0;
        return max(0, $baseCapacity - $disabledCount);
    }
}
