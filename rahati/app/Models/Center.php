<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'is_active',
    ];

    /**
     * Get the appointments for the center.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get the rooms for the center.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
