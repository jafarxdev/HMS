<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render(): View
    {
        $appointments = Appointment::query();
        if (auth()->user()->role === 'doctor') {
            $appointments->where('doctor_id', auth()->user()->doctor?->id ?? 0);
        }

        return view('livewire.dashboard', [
            'totalPatients' => Patient::count(),
            'totalDoctors' => Doctor::count(),
            'totalAppointments' => (clone $appointments)->count(),
            'todayAppointments' => (clone $appointments)->whereDate('appointment_date', today())->count(),
            'recentAppointments' => $appointments->with(['patient', 'doctor'])->latest('id')->limit(5)->get(),
        ]);
    }
}
