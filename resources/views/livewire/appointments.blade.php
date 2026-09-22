<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Appointments</h1><p class="mt-1 text-sm text-slate-500">Schedule visits and update their status.</p></div>
        <x-button wire:click="create">Add appointment</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} appointment</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.patient_id" label="Patient"><x-select id="form.patient_id" wire:model="form.patient_id"><option value="">Select patient</option>@foreach ($patients as $patient)<option wire:key="patient-{{ $patient->id }}" value="{{ $patient->id }}">{{ $patient->name }} / {{ $patient->phone }}</option>@endforeach</x-select></x-field>
                <x-field name="form.doctor_id" label="Doctor"><x-select id="form.doctor_id" wire:model="form.doctor_id"><option value="">Select active doctor</option>@foreach ($doctors as $doctor)<option wire:key="doctor-{{ $doctor->id }}" value="{{ $doctor->id }}" @disabled($doctor->status !== 'active')>{{ $doctor->name }} ({{ $doctor->status }})</option>@endforeach</x-select></x-field>
                <x-field name="form.appointment_date" label="Appointment date"><x-input id="form.appointment_date" type="date" wire:model="form.appointment_date" /></x-field>
                <x-field name="form.appointment_time" label="Appointment time"><x-input id="form.appointment_time" type="time" wire:model="form.appointment_time" /></x-field>
                <x-field name="form.status" label="Status"><x-select id="form.status" wire:model="form.status"><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></x-select></x-field>
                <x-field name="form.reason" label="Reason"><x-textarea id="form.reason" wire:model="form.reason" /></x-field>
                <x-field name="form.notes" label="Notes"><x-textarea id="form.notes" wire:model="form.notes" /></x-field>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search appointments" placeholder="Search appointments…" />
        <x-input type="date" wire:model.live="dateFilter" aria-label="Date filter" />
        <x-select wire:model.live="doctorFilter" aria-label="Doctor filter"><option value="">All doctors</option>@foreach ($doctors as $doctor)<option wire:key="filter-doctor-{{ $doctor->id }}" value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</x-select>
        <x-select wire:model.live="statusFilter" aria-label="Status filter"><option value="">All statuses</option><option value="scheduled">Scheduled</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></x-select>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Patient</th><th class="p-4">Doctor</th><th class="p-4">Date / Time</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4 font-medium">{{ $record->patient->name }}<p class="text-xs font-normal text-slate-500">{{ Str::limit($record->reason, 60) }}</p></td><td class="p-4">{{ $record->doctor->name }}</td><td class="p-4 whitespace-nowrap">{{ $record->appointment_date->format('d M Y') }} / {{ $record->appointment_time }}</td><td class="p-4"><x-status-badge :status="$record->status" /></td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this appointment? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No appointments found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>