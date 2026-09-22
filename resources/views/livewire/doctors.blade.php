<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Doctors</h1><p class="mt-1 text-sm text-slate-500">Manage clinical staff, departments and availability.</p></div>
        <x-button wire:click="create">Add doctor</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} doctor</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.department_id" label="Department"><x-select id="form.department_id" wire:model="form.department_id"><option value="">Select department</option>@foreach ($departments as $department)<option wire:key="department-{{ $department->id }}" value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</x-select></x-field>
                <x-field name="form.user_id" label="Doctor login account (optional)"><x-select id="form.user_id" wire:model="form.user_id"><option value="">No linked account</option>@foreach ($doctorUsers as $doctorUser)<option wire:key="user-{{ $doctorUser->id }}" value="{{ $doctorUser->id }}">{{ $doctorUser->email }}</option>@endforeach</x-select></x-field>
                <x-field name="form.name" label="Name"><x-input id="form.name" type="text" wire:model="form.name" required /></x-field>
                <x-field name="form.email" label="Email"><x-input id="form.email" type="email" wire:model="form.email" required /></x-field>
                <x-field name="form.phone" label="Phone"><x-input id="form.phone" type="text" wire:model="form.phone" required /></x-field>
                <x-field name="form.specialization" label="Specialization"><x-input id="form.specialization" type="text" wire:model="form.specialization" required /></x-field>
                <x-field name="form.status" label="Status"><x-select id="form.status" wire:model="form.status"><option value="active">Active</option><option value="inactive">Inactive</option></x-select></x-field>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search doctors" placeholder="Search doctors…" />
        <x-select wire:model.live="departmentFilter" aria-label="Department filter"><option value="">All departments</option>@foreach ($departments as $department)<option wire:key="filter-department-{{ $department->id }}" value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</x-select>
        <x-select wire:model.live="statusFilter" aria-label="Status filter"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></x-select>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Doctor</th><th class="p-4">Department</th><th class="p-4">Specialization</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4"><p class="font-medium">{{ $record->name }}</p><p class="text-xs text-slate-500">{{ $record->email }} / {{ $record->phone }}</p></td><td class="p-4">{{ $record->department->name }}</td><td class="p-4">{{ $record->specialization }}</td><td class="p-4"><x-status-badge :status="$record->status" /></td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this doctor? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-10 text-center text-slate-500">No doctors found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>