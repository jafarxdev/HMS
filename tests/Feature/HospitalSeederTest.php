<?php

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Database\Seeders\HospitalSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('demo data is connected and can be seeded twice', function () {
    $this->seed(HospitalSeeder::class);
    $this->seed(HospitalSeeder::class);

    $this->assertDatabaseCount('departments', 3);
    $this->assertDatabaseCount('doctors', 3);
    $this->assertDatabaseCount('patients', 18);
    $this->assertDatabaseCount('appointments', 18);
    $this->assertDatabaseCount('prescriptions', 18);
    $this->assertDatabaseCount('prescription_items', 36);
    expect(Appointment::whereDate('appointment_date', today())->count())->toBe(9);
    expect(Doctor::whereNotNull('user_id')->first()->user->role)->toBe('doctor');

    foreach (User::ROLES as $role) {
        $user = User::where('email', $role.'@hospital.test')->firstOrFail();
        expect($user->role)->toBe($role);
        expect(Hash::check('password', $user->password))->toBeTrue();
    }
});
