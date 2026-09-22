<?php

use App\Livewire\Dashboard;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('dashboard displays accurate counts and recent appointments', function () {
    $this->freezeTime();
    $user = User::factory()->admin()->create();
    $appointment = Appointment::factory()->create(['appointment_date' => today()]);
    Appointment::factory()->create();

    Livewire::actingAs($user)->test(Dashboard::class)
        ->assertViewHas('totalPatients', 2)->assertViewHas('totalDoctors', 2)
        ->assertViewHas('totalAppointments', 2)->assertViewHas('todayAppointments', 1)
        ->assertSee($appointment->patient->name)->assertSee($appointment->doctor->name);
});

test('doctor dashboard excludes appointments assigned to other doctors', function () {
    $user = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->for($user)->create();
    $own = Appointment::factory()->for($doctor)->create();
    $other = Appointment::factory()->create();

    Livewire::actingAs($user)->test(Dashboard::class)
        ->assertViewHas('totalAppointments', 1)->assertSee($own->patient->name)
        ->assertDontSee($other->patient->name);
});
