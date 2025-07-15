<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'appointment_id',
        'start_time',
        'end_time',
        'provider_notes',
        'diagnosis',
        'treatment_plan',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the appointment that owns the consultation.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient associated with the consultation through the appointment.
     */
    public function patient()
    {
        return $this->hasOneThrough(User::class, Appointment::class, 'id', 'id', 'appointment_id', 'patient_id');
    }

    /**
     * Get the provider associated with the consultation through the appointment.
     */
    public function provider()
    {
        return $this->hasOneThrough(User::class, Appointment::class, 'id', 'id', 'appointment_id', 'provider_id');
    }
}
