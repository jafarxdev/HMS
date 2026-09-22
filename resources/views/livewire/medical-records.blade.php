<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Medical Records</h1><p class="mt-1 text-sm text-slate-500">Document diagnoses, symptoms and treatment.</p></div>
        <x-button wire:click="create">Add medical record</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} medical record</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.patient_id" label="Patient"><x-select id="form.patient_id" wire:model="form.patient_id"><option value="">Select patient</option>@foreach ($patients as $patient)<option wire:key="patient-{{ $patient->id }}" value="{{ $patient->id }}">{{ $patient->name }} / {{ $patient->phone }}</option>@endforeach</x-select></x-field>
                <x-field name="form.doctor_id" label="Doctor"><x-select id="form.doctor_id" wire:model="form.doctor_id"><option value="">Select doctor</option>@foreach ($doctors as $doctor)<option wire:key="doctor-{{ $doctor->id }}" value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</x-select></x-field>
                <x-field name="form.visit_date" label="Visit date"><x-input id="form.visit_date" type="date" wire:model="form.visit_date" /></x-field>
                <x-field name="form.diagnosis" label="Diagnosis"><x-textarea id="form.diagnosis" wire:model="form.diagnosis" /></x-field>
                <x-field name="form.symptoms" label="Symptoms"><x-textarea id="form.symptoms" wire:model="form.symptoms" /></x-field>
                <x-field name="form.treatment" label="Treatment"><x-textarea id="form.treatment" wire:model="form.treatment" /></x-field>
                <x-field name="form.notes" label="Notes"><x-textarea id="form.notes" wire:model="form.notes" /></x-field>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search medical records" placeholder="Search medical records…" />
        <x-select wire:model.live="patientFilter" aria-label="Patient filter"><option value="">All patients</option>@foreach ($patients as $patient)<option wire:key="filter-patient-{{ $patient->id }}" value="{{ $patient->id }}">{{ $patient->name }}</option>@endforeach</x-select>
        <x-select wire:model.live="doctorFilter" aria-label="Doctor filter"><option value="">All doctors</option>@foreach ($doctors as $doctor)<option wire:key="filter-doctor-{{ $doctor->id }}" value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</x-select>
    </div>
    <div class="mb-4 flex items-center gap-4">
        <x-button wire:click="resetFilters" variant="secondary">Clear filters</x-button>
        <span wire:loading.delay role="status" class="text-sm text-slate-500">Updating…</span>
        <span class="text-sm text-slate-500">{{ $records->total() }} results</span>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Patient</th><th class="p-4">Doctor</th><th class="p-4">Diagnosis</th><th class="p-4">Visit date</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4 font-medium">{{ $record->patient->name }}</td><td class="p-4">{{ $record->doctor->name }}</td><td class="p-4">{{ Str::limit($record->diagnosis, 80) }}</td><td class="p-4 whitespace-nowrap">{{ $record->visit_date->format('d M Y') }}</td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this medical record? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No medical records found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>
