<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Prescription> */
class PrescriptionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'doctor_id' => fn (array $attributes) => MedicalRecord::findOrFail($attributes['medical_record_id'])->doctor_id,
            'patient_id' => fn (array $attributes) => MedicalRecord::findOrFail($attributes['medical_record_id'])->patient_id,
            'prescription_date' => today()->toDateString(),
            'notes' => 'Follow the prescribed instructions.',
        ];
    }
}
