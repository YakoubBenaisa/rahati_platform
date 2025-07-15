<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accommodation extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'appointment_id',
        'room_id',
        'meal_option_id',
        'check_in_date',
        'check_out_date',
        'number_of_guests',
        'total_price',
        'status',
        'special_requests',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'number_of_guests' => 'integer',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the appointment that owns the accommodation.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the room that owns the accommodation.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the meal option that owns the accommodation.
     */
    public function mealOption()
    {
        return $this->belongsTo(MealOption::class);
    }

    /**
     * Get the patient associated with the accommodation through the appointment.
     */
    public function patient()
    {
        return $this->hasOneThrough(User::class, Appointment::class, 'id', 'id', 'appointment_id', 'patient_id');
    }
}
