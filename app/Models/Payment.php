<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'appointment_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the appointment that owns the payment.
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the patient associated with the payment through the appointment.
     */
    public function patient()
    {
        return $this->hasOneThrough(User::class, Appointment::class, 'id', 'id', 'appointment_id', 'patient_id');
    }
}
