<?php

use App\Livewire\Patients;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('receptionist can create edit and delete patients', function () {
    $user = User::factory()->receptionist()->create();
    $component = Livewire::actingAs($user)->test(Patients::class)->call('create')
        ->set('form.name', 'Amina')->set('form.gender', 'female')->set('form.date_of_birth', '1995-04-12')
        ->set('form.phone', '0700000000')->call('save')->assertHasNoErrors();
    $patient = Patient::where('name', 'Amina')->firstOrFail();
    $component->call('edit', $patient->id)->set('form.name', 'Amina Noor')->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('patients', ['id' => $patient->id, 'name' => 'Amina Noor']);
    $component->call('delete', $patient->id);
    $this->assertModelMissing($patient);
});

test('patient validation rejects future births and invalid values', function () {
    Livewire::actingAs(User::factory()->receptionist()->create())->test(Patients::class)
        ->set('form.date_of_birth', today()->addDay()->toDateString())->set('form.gender', 'invalid')
        ->set('form.email', 'invalid')->call('save')
        ->assertHasErrors(['form.name', 'form.phone', 'form.date_of_birth', 'form.gender', 'form.email']);
    $this->assertDatabaseCount('patients', 0);
});

test('patient search and gender filters work together', function () {
    $patient = Patient::factory()->create(['name' => 'Amina Noor', 'gender' => 'female']);
    $other = Patient::factory()->create(['name' => 'Different Person', 'gender' => 'male']);
    Livewire::actingAs(User::factory()->receptionist()->create())->test(Patients::class)
        ->set('search', 'Amina')->set('genderFilter', 'female')
        ->assertSee($patient->name)->assertDontSee($other->name);
});

test('patients with appointments cannot be deleted', function () {
    $appointment = Appointment::factory()->create();
    Livewire::actingAs(User::factory()->admin()->create())->test(Patients::class)
        ->call('delete', $appointment->patient_id)->assertSee('This patient has clinical history');
    $this->assertModelExists($appointment->patient);
});
