<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (User::ROLES as $role) {
                User::firstOrCreate(
                    ['email' => $role.'@hospital.test'],
                    ['name' => ucfirst($role), 'role' => $role, 'password' => 'password'],
                );
            }

            if (Department::where('name', 'General Medicine')->exists()) {
                return;
            }

            $departments = collect(['General Medicine', 'Cardiology', 'Pediatrics'])
                ->map(fn (string $name) => Department::factory()->create(['name' => $name]));
            $doctors = $departments->map(fn (Department $department) => Doctor::factory()->for($department)->create());
            $doctors->first()->update(['user_id' => User::where('email', 'doctor@hospital.test')->value('id')]);
            $patients = Patient::factory()->count(18)->create();

            foreach ($patients as $index => $patient) {
                $doctor = $doctors[$index % $doctors->count()];
                Appointment::factory()->for($patient)->for($doctor)->create([
                    'appointment_date' => today()->addDays(intdiv($index, 9))->toDateString(),
                    'appointment_time' => sprintf('%02d:00', 9 + intdiv($index % 9, 3)),
                ]);

                $record = MedicalRecord::factory()->for($patient)->for($doctor)->create();
                Prescription::factory()->for($record)->has(PrescriptionItem::factory()->count(2), 'items')->create();
            }
        });
    }
}
