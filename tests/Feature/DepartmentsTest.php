<?php

use App\Livewire\Departments;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('admin can create edit search and delete departments', function () {
    $admin = User::factory()->admin()->create();
    $component = Livewire::actingAs($admin)->test(Departments::class)->call('create')
        ->set('form.name', 'Cardiology')->call('save')->assertHasNoErrors();
    $department = Department::where('name', 'Cardiology')->firstOrFail();
    $component->call('edit', $department->id)->set('form.name', 'Heart Care')->call('save');
    $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Heart Care']);
    $component->set('search', 'Heart')->assertSee('Heart Care')->set('search', 'Missing')->assertDontSee('Heart Care');
    $component->call('delete', $department->id);
    $this->assertModelMissing($department);
});

test('department name is required and unique', function () {
    $admin = User::factory()->admin()->create();
    $department = Department::factory()->create();
    Livewire::actingAs($admin)->test(Departments::class)->call('save')->assertHasErrors(['form.name' => 'required'])
        ->set('form.name', $department->name)->call('save')->assertHasErrors(['form.name' => 'unique']);
    $this->assertDatabaseCount('departments', 1);
});

test('departments with doctors cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $doctor = Doctor::factory()->create();
    Livewire::actingAs($admin)->test(Departments::class)->call('delete', $doctor->department_id)
        ->assertSee('This department has doctors and cannot be deleted.');
    $this->assertModelExists($doctor->department);
});

test('receptionist cannot access department management', function () {
    $this->actingAs(User::factory()->receptionist()->create())->get('/departments')->assertForbidden();
});
