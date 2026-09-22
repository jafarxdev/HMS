<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicalRecord> */
class MedicalRecordFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['patient_id' => Patient::factory(), 'doctor_id' => Doctor::factory(), 'diagnosis' => 'Seasonal allergy', 'symptoms' => 'Sneezing', 'treatment' => 'Rest and follow-up', 'visit_date' => today()->toDateString()];
    }
}
