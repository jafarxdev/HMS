<?php

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('database protects departments doctors and patients with clinical history', function () {
    $appointment = Appointment::factory()->create();

    expect(fn () => $appointment->doctor->department->delete())->toThrow(QueryException::class);
    expect(fn () => $appointment->doctor->delete())->toThrow(QueryException::class);
    expect(fn () => $appointment->patient->delete())->toThrow(QueryException::class);

    $this->assertModelExists($appointment);
});

test('a prescription cannot name a different patient or doctor from its medical record', function (string $foreignKey, string $modelClass) {
    $record = MedicalRecord::factory()->create();
    $other = $modelClass::factory()->create();

    expect(fn () => Prescription::factory()->for($record)->create([$foreignKey => $other->id]))
        ->toThrow(QueryException::class);
    $this->assertDatabaseCount('prescriptions', 0);
})->with([
    'patient mismatch' => ['patient_id', Patient::class],
    'doctor mismatch' => ['doctor_id', Doctor::class],
]);

test('a department rejects an unexpected mass assigned attribute', function () {
    $department = Department::factory()->create();
    $department->fill(['name' => 'Updated department', 'id' => $department->id + 1000])->save();

    expect($department->id)->toBe(1);
    $this->assertDatabaseHas('departments', ['id' => 1, 'name' => 'Updated department']);
});
