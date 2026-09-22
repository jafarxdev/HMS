<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Appointments;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Departments;
use App\Livewire\Doctors;
use App\Livewire\MedicalRecords;
use App\Livewire\Patients;
use App\Livewire\Prescriptions;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Login::class)->middleware('guest')->name('home');
Route::livewire('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('/prescriptions', Prescriptions::class)->middleware('can:manage-prescriptions')->name('prescriptions');
    Route::livewire('/medical-records', MedicalRecords::class)->middleware('can:manage-medical-records')->name('medical-records');
    Route::livewire('/appointments', Appointments::class)->middleware('can:manage-appointments')->name('appointments');
    Route::livewire('/patients', Patients::class)->middleware('can:manage-patients')->name('patients');
    Route::livewire('/doctors', Doctors::class)->middleware('can:manage-doctors')->name('doctors');
    Route::livewire('/departments', Departments::class)->middleware('can:manage-departments')->name('departments');
    Route::post('/logout', LogoutController::class)->name('logout');
});
