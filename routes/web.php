<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Departments;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Login::class)->middleware('guest')->name('home');
Route::livewire('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', Dashboard::class)->name('dashboard');
    Route::livewire('/departments', Departments::class)->name('departments');
    Route::post('/logout', LogoutController::class)->name('logout');
});
