<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'center_id',
        'room_number',
        'type',
        'description',
        'price_per_night',
        'capacity',
        'is_accessible',
        'is_available',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'capacity' => 'integer',
        'is_accessible' => 'boolean',
        'is_available' => 'boolean',
    ];

    /**
     * Get the center that owns the room.
     */
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Get the accommodations for the room.
     */
    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }
}
