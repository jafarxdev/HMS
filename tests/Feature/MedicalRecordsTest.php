<?php

use App\Livewire\MedicalRecords;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('doctor can create update and delete their own medical record', function () {
    $user = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->for($user)->create();
    $patient = Patient::factory()->create();
    Appointment::factory()->for($patient)->for($doctor)->create();
    $component = Livewire::actingAs($user)->test(MedicalRecords::class)->call('create')
        ->set('form.patient_id', $patient->id)->set('form.doctor_id', $doctor->id)
        ->set('form.visit_date', today()->toDateString())->set('form.diagnosis', 'Allergy')
        ->call('save')->assertHasNoErrors();
    $record = MedicalRecord::sole();
    $component->call('edit', $record->id)->set('form.treatment', 'Follow-up')->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('medical_records', ['id' => $record->id, 'treatment' => 'Follow-up']);
    $component->call('delete', $record->id);
    $this->assertModelMissing($record);
});

test('doctor cannot read or change another doctors medical record', function () {
    $user = User::factory()->doctor()->create();
    Doctor::factory()->for($user)->create();
    $record = MedicalRecord::factory()->create(['diagnosis' => 'Private diagnosis']);
    Livewire::actingAs($user)->test(MedicalRecords::class)->assertDontSee('Private diagnosis')
        ->call('edit', $record->id)->assertForbidden();
    Livewire::actingAs($user)->test(MedicalRecords::class)->call('delete', $record->id)->assertForbidden();
    $this->assertModelExists($record);
});

test('medical records require diagnosis and reject future visits', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test(MedicalRecords::class)
        ->set('form.visit_date', today()->addDay()->toDateString())->call('save')
        ->assertHasErrors(['form.patient_id', 'form.doctor_id', 'form.diagnosis', 'form.visit_date']);
    $this->assertDatabaseCount('medical_records', 0);
});

test('medical records with prescriptions preserve their patient and cannot be deleted', function () {
    $prescription = Prescription::factory()->create();
    $patient = Patient::factory()->create();
    $component = Livewire::actingAs(User::factory()->admin()->create())->test(MedicalRecords::class)
        ->call('edit', $prescription->medical_record_id)->set('form.patient_id', $patient->id)
        ->call('save')->assertHasErrors('form.patient_id');
    $component->call('delete', $prescription->medical_record_id)->assertSee('This medical record has prescriptions');
    $this->assertModelExists($prescription->medicalRecord);
});
