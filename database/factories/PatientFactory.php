<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['name' => fake()->name(), 'gender' => fake()->randomElement(['male', 'female', 'other']), 'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'), 'phone' => fake()->numerify('07########'), 'email' => fake()->safeEmail(), 'address' => fake()->address(), 'blood_group' => 'O+', 'emergency_contact' => fake()->numerify('07########')];
    }
}
