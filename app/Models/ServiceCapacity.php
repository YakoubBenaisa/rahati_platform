<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCapacity extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'center_id',
        'service_type',
        'max_capacity',
        'date',
        'start_time',
        'end_time',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the center that owns the service capacity.
     */
    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    /**
     * Check if the capacity is available for a given date and time.
     *
     * @param \Carbon\Carbon $dateTime
     * @return bool
     */
    public function isAvailable($dateTime)
    {
        // Check if the date matches
        if ($this->date->format('Y-m-d') !== $dateTime->format('Y-m-d')) {
            return false;
        }

        // Check if the time is within the range (if applicable)
        if ($this->start_time && $this->end_time) {
            $time = $dateTime->format('H:i:s');
            if ($time < $this->start_time->format('H:i:s') || $time > $this->end_time->format('H:i:s')) {
                return false;
            }
        }

        // Check if the capacity is not exceeded
        // This would require a more complex query in a real implementation
        // to count the number of appointments/accommodations for this date/time
        return true;
    }
}
