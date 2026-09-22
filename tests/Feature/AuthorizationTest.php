<?php

use App\Livewire\MedicalRecords;
use App\Livewire\Patients;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('staff roles have exactly the intended module permissions', function (string $role, array $allowed) {
    $user = User::factory()->create(['role' => $role]);
    $this->actingAs($user);

    foreach (['departments', 'doctors', 'patients', 'appointments', 'medical-records', 'prescriptions'] as $module) {
        expect(Gate::forUser($user)->allows('manage-'.$module))->toBe(in_array($module, $allowed));
        $response = $this->get('/'.$module);
        if (in_array($module, $allowed)) {
            $response->assertOk();
        } else {
            $response->assertForbidden();
        }
    }
})->with([
    'admin' => ['admin', ['departments', 'doctors', 'patients', 'appointments', 'medical-records', 'prescriptions']],
    'receptionist' => ['receptionist', ['patients', 'appointments']],
    'doctor' => ['doctor', ['medical-records', 'prescriptions']],
]);

test('clinical policies allow administrators and owners only', function (string $modelClass) {
    $owner = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->for($owner)->create();
    $record = MedicalRecord::factory()->for($doctor)->create();
    $model = $modelClass === MedicalRecord::class ? $record : Prescription::factory()->for($record)->create();

    foreach (['update', 'delete'] as $ability) {
        expect(Gate::forUser($owner)->allows($ability, $model))->toBeTrue();
        expect(Gate::forUser(User::factory()->admin()->create())->allows($ability, $model))->toBeTrue();
        expect(Gate::forUser(User::factory()->receptionist()->create())->allows($ability, $model))->toBeFalse();
        expect(Gate::forUser(User::factory()->doctor()->create())->allows($ability, $model))->toBeFalse();
    }
})->with([
    'medical record' => [MedicalRecord::class],
    'prescription' => [Prescription::class],
]);

test('guests cannot access any module', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));
})->with(['/dashboard', '/departments', '/doctors', '/patients', '/appointments', '/medical-records', '/prescriptions']);

test('doctor cannot select or submit an unrelated patient', function () {
    $user = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->for($user)->create();
    $patient = Patient::factory()->create(['name' => 'Unrelated Patient']);
    Livewire::actingAs($user)->test(MedicalRecords::class)->call('create')->assertDontSee($patient->name)
        ->set('form.patient_id', $patient->id)->set('form.doctor_id', $doctor->id)
        ->set('form.diagnosis', 'Test diagnosis')->set('form.visit_date', today()->toDateString())
        ->call('save')->assertForbidden();
    $this->assertDatabaseCount('medical_records', 0);
});

test('permission changes are enforced again when a livewire action runs', function () {
    $user = User::factory()->receptionist()->create();
    $patient = Patient::factory()->create();
    $component = Livewire::actingAs($user)->test(Patients::class);
    $user->forceFill(['role' => 'doctor'])->save();

    $component->call('delete', $patient->id)->assertForbidden();
    $this->assertModelExists($patient);
});

test('user role cannot be escalated through mass assignment', function () {
    $user = User::factory()->receptionist()->create();
    $user->fill(['name' => 'New name', 'role' => 'admin'])->save();

    expect($user->fresh()->role)->toBe('receptionist');
});
