<?php

use App\Livewire\Appointments;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('existing appointments can be cancelled after their doctor becomes inactive', function () {
    $appointment = Appointment::factory()->create();
    $appointment->doctor->update(['status' => 'inactive']);

    Livewire::actingAs(User::factory()->receptionist()->create())->test(Appointments::class)
        ->call('edit', $appointment->id)->set('form.status', 'cancelled')->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'cancelled']);
});

test('a cancelled appointment cannot be reactivated into an occupied slot', function () {
    $appointment = Appointment::factory()->create();
    $cancelled = Appointment::factory()->create([
        'doctor_id' => $appointment->doctor_id,
        'appointment_date' => $appointment->appointment_date,
        'appointment_time' => $appointment->appointment_time,
        'status' => 'cancelled',
    ]);

    Livewire::actingAs(User::factory()->admin()->create())->test(Appointments::class)
        ->call('edit', $cancelled->id)->set('form.status', 'scheduled')->call('save')
        ->assertHasErrors('form.appointment_time');

    $this->assertDatabaseHas('appointments', ['id' => $cancelled->id, 'status' => 'cancelled']);
});

test('receptionist can book update status filter and delete an appointment', function () {
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $date = today()->addDay()->toDateString();
    $component = Livewire::actingAs(User::factory()->receptionist()->create())->test(Appointments::class)
        ->call('create')->set('form.patient_id', $patient->id)->set('form.doctor_id', $doctor->id)
        ->set('form.appointment_date', $date)->set('form.appointment_time', '10:00')
        ->set('form.reason', 'Annual checkup')->call('save')->assertHasNoErrors();
    $appointment = Appointment::sole();
    $component->set('dateFilter', $date)->set('doctorFilter', (string) $doctor->id)
        ->set('statusFilter', 'scheduled')->set('search', $patient->name)
        ->assertViewHas('records', fn ($records) => $records->total() === 1);
    $component->call('edit', $appointment->id)->set('form.status', 'completed')->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completed']);
    $component->assertViewHas('records', fn ($records) => $records->total() === 0)->call('delete', $appointment->id);
    $this->assertModelMissing($appointment);
});

test('duplicate bookings are rejected and cancellation releases the slot', function () {
    $appointment = Appointment::factory()->create();
    $form = $appointment->only(['patient_id', 'doctor_id', 'appointment_time', 'status', 'reason', 'notes']);
    $form['appointment_date'] = $appointment->appointment_date->toDateString();
    $component = Livewire::actingAs(User::factory()->admin()->create())->test(Appointments::class)
        ->call('create')->set('form', $form)->call('save')->assertHasErrors('form.appointment_time')
        ->assertSee('This doctor is already booked at that time.');
    $this->assertDatabaseCount('appointments', 1);
    $appointment->update(['status' => 'cancelled']);
    $component->call('save')->assertHasNoErrors();
    $this->assertDatabaseCount('appointments', 2);
});

test('the database also rejects duplicate active slots', function () {
    $appointment = Appointment::factory()->create();
    expect(fn () => Appointment::factory()->create($appointment->only(['doctor_id', 'patient_id', 'appointment_date', 'appointment_time'])))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('invalid appointment selections and times are rejected', function () {
    $inactive = Doctor::factory()->create(['status' => 'inactive']);
    Livewire::actingAs(User::factory()->admin()->create())->test(Appointments::class)
        ->set('form.patient_id', 9999)->set('form.doctor_id', $inactive->id)
        ->set('form.appointment_date', 'invalid')->set('form.appointment_time', '25:00')
        ->set('form.status', 'invalid')->call('save')
        ->assertHasErrors(['form.patient_id', 'form.doctor_id', 'form.appointment_date', 'form.appointment_time', 'form.status', 'form.reason']);
    $this->assertDatabaseCount('appointments', 0);
});

test('scheduled appointments in the past are rejected', function () {
    $appointment = Appointment::factory()->make();
    $form = $appointment->only(['patient_id', 'doctor_id', 'appointment_time', 'status', 'reason', 'notes']);
    $form['appointment_date'] = today()->subDay()->toDateString();
    Livewire::actingAs(User::factory()->admin()->create())->test(Appointments::class)
        ->set('form', $form)->call('save')->assertHasErrors('form.appointment_date');
    $this->assertDatabaseCount('appointments', 0);
});
