<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'caregiver_name',
        'caregiver_phone',
        'center_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the center that the user belongs to.
     */
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Check if the user is a superuser.
     *
     * @return bool
     */
    public function isSuperuser(): bool
    {
        return $this->role === 'Superuser';
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'Admin';
    }

    /**
     * Check if the user is an admin or superuser.
     *
     * @return bool
     */
    public function isAdminOrSuperuser(): bool
    {
        return $this->isAdmin() || $this->isSuperuser();
    }

    /**
     * Check if the user is a provider.
     *
     * @return bool
     */
    public function isProvider(): bool
    {
        return $this->role === 'Provider';
    }

    /**
     * Check if the user is a patient.
     *
     * @return bool
     */
    public function isPatient(): bool
    {
        return $this->role === 'Patient';
    }

    /**
     * Get the appointments where this user is the patient.
     */
    public function patientAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    /**
     * Get the appointments where this user is the provider.
     */
    public function providerAppointments()
    {
        return $this->hasMany(Appointment::class, 'provider_id');
    }

    /**
     * Get the patients assigned to this provider.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function patients()
    {
        if (!$this->isProvider()) {
            return collect([]);
        }

        return User::whereIn('id', function($query) {
            $query->select('patient_id')
                ->from('appointments')
                ->where('provider_id', $this->id)
                ->distinct();
        })->get();
    }
}
