<?php

use App\Livewire\Doctors;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('doctor email and linked login account must be unique', function () {
    $doctorUser = User::factory()->doctor()->create();
    $existing = Doctor::factory()->for($doctorUser)->create();
    $doctor = Doctor::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())->test(Doctors::class)
        ->call('edit', $doctor->id)->set('form.email', $existing->email)->set('form.user_id', $doctorUser->id)
        ->call('save')->assertHasErrors(['form.email' => 'unique', 'form.user_id' => 'unique']);

    expect($doctor->fresh()->email)->toBe($doctor->email);
});

test('admin can create update and delete a doctor in a department', function () {
    $admin = User::factory()->admin()->create();
    $department = Department::factory()->create();
    $form = Doctor::factory()->for($department)->make()->only(['department_id', 'name', 'email', 'phone', 'specialization', 'status']);
    $component = Livewire::actingAs($admin)->test(Doctors::class)->call('create')->set('form', $form)
        ->call('save')->assertHasNoErrors();
    $doctor = Doctor::where('email', $form['email'])->firstOrFail();
    expect($doctor->department->is($department))->toBeTrue();
    $component->call('edit', $doctor->id)->set('form.status', 'inactive')->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'status' => 'inactive']);
    $component->call('delete', $doctor->id);
    $this->assertModelMissing($doctor);
});

test('doctor filters combine department and status', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    $inactive = Doctor::factory()->create(['status' => 'inactive']);
    Livewire::actingAs($admin)->test(Doctors::class)->set('departmentFilter', (string) $doctor->department_id)
        ->set('statusFilter', 'active')->assertSee($doctor->email)->assertDontSee($inactive->email);
});

test('invalid doctor data cannot be saved', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test(Doctors::class)
        ->set('form.status', 'unknown')->set('form.email', 'invalid')
        ->call('save')->assertHasErrors(['form.name', 'form.department_id', 'form.email', 'form.status']);
    $this->assertDatabaseCount('doctors', 0);
});
