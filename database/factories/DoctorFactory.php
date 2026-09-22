<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Doctor> */
class DoctorFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('07########'),
            'specialization' => 'General medicine',
            'status' => 'active',
        ];
    }
}
