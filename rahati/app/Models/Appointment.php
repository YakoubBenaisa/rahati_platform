<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'patient_id',
        'center_id',
        'provider_id',
        'appointment_datetime',
        'appointment_duration',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_datetime' => 'datetime',
    ];

    /**
     * Get the patient that owns the appointment.
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the provider that owns the appointment.
     */
    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    /**
     * Get the center that owns the appointment.
     */
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Get the consultation associated with the appointment.
     */
    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    /**
     * Get the payment associated with the appointment.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get the accommodation associated with the appointment.
     */
    public function accommodation()
    {
        return $this->hasOne(Accommodation::class);
    }
}
