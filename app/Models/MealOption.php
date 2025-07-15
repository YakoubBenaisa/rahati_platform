<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealOption extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_vegetarian' => 'boolean',
        'is_vegan' => 'boolean',
        'is_gluten_free' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the accommodations that include this meal option.
     */
    public function accommodations()
    {
        return $this->hasMany(Accommodation::class);
    }
}
