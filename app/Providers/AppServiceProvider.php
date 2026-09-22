<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-departments', fn (User $user): bool => $user->role === 'admin');
        Gate::define('manage-doctors', fn (User $user): bool => $user->role === 'admin');
        Gate::define('manage-patients', fn (User $user): bool => in_array($user->role, ['admin', 'receptionist']));
        Gate::define('manage-appointments', fn (User $user): bool => in_array($user->role, ['admin', 'receptionist']));
        Gate::define('manage-medical-records', fn (User $user): bool => in_array($user->role, ['admin', 'doctor']));
        Gate::define('manage-prescriptions', fn (User $user): bool => in_array($user->role, ['admin', 'doctor']));
    }
}
