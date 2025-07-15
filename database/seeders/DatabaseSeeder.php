<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Center;
use App\Models\Room;
use App\Models\MealOption;
use App\Models\ServiceCapacity;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Get or create centers
        $center1 = Center::firstOrCreate(
            ['name' => 'Rahati Wellness Center'],
            [
                'description' => 'A premier wellness center offering comprehensive health services.',
                'address' => '100 Wellness Way, Health City, HC 12345',
                'phone' => '+1999888777',
                'email' => 'info@rahatiwellness.com',
                'website' => 'https://rahatiwellness.com',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'is_active' => true,
            ]
        );

        $center2 = Center::firstOrCreate(
            ['name' => 'Rahati Rehabilitation Center'],
            [
                'description' => 'Specialized rehabilitation services for all ages.',
                'address' => '200 Rehab Road, Recovery Town, RT 67890',
                'phone' => '+1888777666',
                'email' => 'info@rahatirehab.com',
                'website' => 'https://rahatirehab.com',
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'is_active' => true,
            ]
        );

        // Create or update superuser
        User::updateOrCreate(
            ['email' => 'superuser@rahati.com'],
            [
                'name' => 'Superuser',
                'password' => Hash::make('password'),
                'role' => 'Superuser',
                'phone' => '+1000000000',
                'address' => '100 Super St, Super City',
            ]
        );

        // Create or update admin user for center 1
        User::updateOrCreate(
            ['email' => 'admin1@rahati.com'],
            [
                'name' => 'Admin User 1',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'center_id' => $center1->id,
                'phone' => '+1234567890',
                'address' => '123 Admin St, Admin City',
            ]
        );

        // Create or update admin user for center 2
        User::updateOrCreate(
            ['email' => 'admin2@rahati.com'],
            [
                'name' => 'Admin User 2',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'center_id' => $center2->id,
                'phone' => '+1234567891',
                'address' => '124 Admin St, Admin City',
            ]
        );

        // Create or update provider user
        User::updateOrCreate(
            ['email' => 'provider@rahati.com'],
            [
                'name' => 'Provider User',
                'password' => Hash::make('password'),
                'role' => 'Provider',
                'phone' => '+1987654321',
                'address' => '456 Provider Ave, Provider Town',
            ]
        );

        // Create or update patient user
        User::updateOrCreate(
            ['email' => 'patient@rahati.com'],
            [
                'name' => 'Patient User',
                'password' => Hash::make('password'),
                'role' => 'Patient',
                'phone' => '+1122334455',
                'address' => '789 Patient Blvd, Patient Village',
                'caregiver_name' => 'Caregiver Name',
                'caregiver_phone' => '+1555666777',
            ]
        );

        // Centers already created above

        // Create or update rooms for center 1
        Room::updateOrCreate(
            [
                'center_id' => $center1->id,
                'room_number' => '101',
            ],
            [
                'type' => 'single',
                'description' => 'Comfortable single room with private bathroom',
                'price_per_night' => 100.00,
                'capacity' => 1,
                'is_accessible' => true,
                'is_available' => true,
            ]
        );

        Room::updateOrCreate(
            [
                'center_id' => $center1->id,
                'room_number' => '102',
            ],
            [
                'type' => 'double',
                'description' => 'Spacious double room with private bathroom and sitting area',
                'price_per_night' => 150.00,
                'capacity' => 2,
                'is_accessible' => true,
                'is_available' => true,
            ]
        );

        // Create or update rooms for center 2
        Room::updateOrCreate(
            [
                'center_id' => $center2->id,
                'room_number' => '201',
            ],
            [
                'type' => 'single',
                'description' => 'Standard single room with private bathroom',
                'price_per_night' => 90.00,
                'capacity' => 1,
                'is_accessible' => false,
                'is_available' => true,
            ]
        );

        Room::updateOrCreate(
            [
                'center_id' => $center2->id,
                'room_number' => '202',
            ],
            [
                'type' => 'suite',
                'description' => 'Luxury suite with separate bedroom, living area, and kitchenette',
                'price_per_night' => 200.00,
                'capacity' => 3,
                'is_accessible' => true,
                'is_available' => true,
            ]
        );

        // Create or update meal options
        MealOption::updateOrCreate(
            ['name' => 'Standard Meal Plan'],
            [
                'description' => 'Three balanced meals per day',
                'price' => 30.00,
                'is_vegetarian' => false,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_active' => true,
            ]
        );

        MealOption::updateOrCreate(
            ['name' => 'Vegetarian Meal Plan'],
            [
                'description' => 'Three vegetarian meals per day',
                'price' => 35.00,
                'is_vegetarian' => true,
                'is_vegan' => false,
                'is_gluten_free' => false,
                'is_active' => true,
            ]
        );

        MealOption::updateOrCreate(
            ['name' => 'Vegan Meal Plan'],
            [
                'description' => 'Three vegan meals per day',
                'price' => 40.00,
                'is_vegetarian' => true,
                'is_vegan' => true,
                'is_gluten_free' => false,
                'is_active' => true,
            ]
        );

        MealOption::updateOrCreate(
            ['name' => 'Gluten-Free Meal Plan'],
            [
                'description' => 'Three gluten-free meals per day',
                'price' => 45.00,
                'is_vegetarian' => false,
                'is_vegan' => false,
                'is_gluten_free' => true,
                'is_active' => true,
            ]
        );

        // Create or update service capacities
        ServiceCapacity::updateOrCreate(
            [
                'center_id' => $center1->id,
                'service_type' => 'appointment',
                'date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'max_capacity' => 20,
                'is_active' => true,
            ]
        );

        ServiceCapacity::updateOrCreate(
            [
                'center_id' => $center1->id,
                'service_type' => 'appointment',
                'date' => now()->addDays(2)->format('Y-m-d'),
            ],
            [
                'max_capacity' => 15,
                'is_active' => true,
            ]
        );

        ServiceCapacity::updateOrCreate(
            [
                'center_id' => $center2->id,
                'service_type' => 'appointment',
                'date' => now()->addDays(1)->format('Y-m-d'),
            ],
            [
                'max_capacity' => 10,
                'is_active' => true,
            ]
        );

        ServiceCapacity::updateOrCreate(
            [
                'center_id' => $center2->id,
                'service_type' => 'appointment',
                'date' => now()->addDays(2)->format('Y-m-d'),
            ],
            [
                'max_capacity' => 12,
                'is_active' => true,
            ]
        );
    }
}
