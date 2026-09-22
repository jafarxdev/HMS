<div>
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <p class="mt-2 text-sm text-slate-500">A quick overview of hospital activity. {{ today()->format('d M Y') }}</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (['Total Patients' => $totalPatients, 'Total Doctors' => $totalDoctors, 'Total Appointments' => $totalAppointments, "Today's Appointments" => $todayAppointments] as $label => $count)
            <article wire:key="stat-{{ $loop->index }}" class="rounded-xl border border-slate-200 bg-white p-6">
                <p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-3 text-3xl font-bold text-teal-700">{{ $count }}</p>
            </article>
        @endforeach
    </div>
    @if (auth()->user()->role === 'doctor')<p class="mt-4 text-sm text-slate-500">Appointment figures show your assigned appointments.</p>@endif
    <section class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white">
        <h2 class="border-b border-slate-200 p-5 font-semibold">Recent appointments</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500"><tr><th class="p-4">Patient</th><th class="p-4">Doctor</th><th class="p-4">Date / Time</th><th class="p-4">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentAppointments as $appointment)
                        <tr wire:key="recent-{{ $appointment->id }}"><td class="p-4">{{ $appointment->patient->name }}</td><td class="p-4">{{ $appointment->doctor->name }}</td><td class="p-4 whitespace-nowrap">{{ $appointment->appointment_date->format('d M Y') }} / {{ $appointment->appointment_time }}</td><td class="p-4"><x-status-badge :status="$appointment->status" /></td></tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-500">No appointments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>