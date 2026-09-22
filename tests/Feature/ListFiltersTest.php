<?php

use App\Livewire\Appointments;
use App\Livewire\Departments;
use App\Livewire\Doctors;
use App\Livewire\MedicalRecords;
use App\Livewire\Patients;
use App\Livewire\Prescriptions;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('lists paginate and reset to page one after searching or clearing filters', function (string $componentClass, string $modelClass) {
    $admin = User::factory()->admin()->create();
    $modelClass::factory()->count(11)->create();

    Livewire::actingAs($admin)->test($componentClass)
        ->assertViewHas('records', fn ($records) => $records->count() === 10 && $records->total() === 11)
        ->call('setPage', 2)->assertViewHas('records', fn ($records) => $records->count() === 1)
        ->set('search', 'no-match-unique-search')
        ->assertSet('paginators.page', 1)
        ->assertViewHas('records', fn ($records) => $records->total() === 0)
        ->call('resetFilters')->assertSet('search', '')->assertSet('paginators.page', 1)
        ->assertViewHas('records', fn ($records) => $records->total() === 11);
})->with([
    'departments' => [Departments::class, Department::class],
    'doctors' => [Doctors::class, Doctor::class],
    'patients' => [Patients::class, Patient::class],
    'appointments' => [Appointments::class, Appointment::class],
    'medical records' => [MedicalRecords::class, MedicalRecord::class],
    'prescriptions' => [Prescriptions::class, Prescription::class],
]);

test('search is restored from the URL', function () {
    Department::factory()->create(['name' => 'Cardiology']);
    Department::factory()->create(['name' => 'Pediatrics']);

    Livewire::actingAs(User::factory()->admin()->create())->withQueryParams(['search' => 'Cardiology'])
        ->test(Departments::class)->assertSet('search', 'Cardiology')
        ->assertSee('Cardiology')->assertDontSee('Pediatrics');
});
