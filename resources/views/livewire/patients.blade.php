<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Patients</h1><p class="mt-1 text-sm text-slate-500">Patient details and emergency contacts.</p></div>
        <x-button wire:click="create">Add patient</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} patient</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.name" label="Name"><x-input id="form.name" type="text" wire:model="form.name" /></x-field>
                <x-field name="form.date_of_birth" label="Date Of Birth"><x-input id="form.date_of_birth" type="date" wire:model="form.date_of_birth" /></x-field>
                <x-field name="form.phone" label="Phone"><x-input id="form.phone" type="text" wire:model="form.phone" /></x-field>
                <x-field name="form.email" label="Email"><x-input id="form.email" type="email" wire:model="form.email" /></x-field>
                <x-field name="form.emergency_contact" label="Emergency Contact"><x-input id="form.emergency_contact" type="text" wire:model="form.emergency_contact" /></x-field>
                <x-field name="form.gender" label="Gender"><x-select id="form.gender" wire:model="form.gender"><option value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></x-select></x-field>
                <x-field name="form.blood_group" label="Blood group"><x-select id="form.blood_group" wire:model="form.blood_group"><option value="">Unknown</option>@foreach (App\Models\Patient::BLOOD_GROUPS as $group)<option wire:key="blood-{{ $group }}" value="{{ $group }}">{{ $group }}</option>@endforeach</x-select></x-field>
                <x-field name="form.address" label="Address"><x-textarea id="form.address" wire:model="form.address" /></x-field>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search patients" placeholder="Search patients…" />
        <x-select wire:model.live="genderFilter" aria-label="Gender filter"><option value="">All genders</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></x-select>
    </div>
    <div class="mb-4 flex items-center gap-4">
        <x-button wire:click="resetFilters" variant="secondary">Clear filters</x-button>
        <span wire:loading.delay role="status" class="text-sm text-slate-500">Updating…</span>
        <span class="text-sm text-slate-500">{{ $records->total() }} results</span>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Patient</th><th class="p-4">Gender</th><th class="p-4">Date of birth</th><th class="p-4">Contact</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4 font-medium">{{ $record->name }}<p class="text-xs font-normal text-slate-500">{{ $record->blood_group ?: 'Blood group unknown' }}</p></td><td class="p-4 capitalize">{{ $record->gender }}</td><td class="p-4">{{ $record->date_of_birth->format('d M Y') }}</td><td class="p-4">{{ $record->phone }}<p class="text-xs text-slate-500">{{ $record->email }}</p></td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this patient? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No patients found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>
