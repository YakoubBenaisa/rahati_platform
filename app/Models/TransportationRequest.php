<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportationRequest extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'user_id',
        'appointment_id',
        'pickup_location',
        'dropoff_location',
        'pickup_time',
        'transportation_type',
        'number_of_passengers',
        'status',
        'special_instructions',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'number_of_passengers' => 'integer',
    ];

    /**
     * Get the user that owns the transportation request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the appointment associated with the transportation request.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
