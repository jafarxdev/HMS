<?php

use App\Models\Appointment;
use App\Models\PrescriptionItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('appointments connect patients doctors and departments', function () {
    $appointment = Appointment::factory()->create();

    expect($appointment->patient->appointments->modelKeys())->toContain($appointment->id);
    expect($appointment->doctor->appointments->modelKeys())->toContain($appointment->id);
    expect($appointment->doctor->department->doctors->modelKeys())->toContain($appointment->doctor_id);
    expect($appointment->appointment_date->toDateString())->toBe(today()->addDay()->toDateString());
});

test('prescription items share the record patient and doctor', function () {
    $item = PrescriptionItem::factory()->create();
    $prescription = $item->prescription;

    expect($prescription->patient_id)->toBe($prescription->medicalRecord->patient_id);
    expect($prescription->doctor_id)->toBe($prescription->medicalRecord->doctor_id);
    expect($prescription->items->modelKeys())->toContain($item->id);
    expect($prescription->doctor->medicalRecords->modelKeys())->toContain($prescription->medical_record_id);
    expect($prescription->patient->prescriptions->modelKeys())->toContain($prescription->id);
});
