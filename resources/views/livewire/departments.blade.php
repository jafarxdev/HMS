<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Departments</h1><p class="mt-1 text-sm text-slate-500">Organize hospital departments and clinical teams.</p></div>
        <x-button wire:click="create">Add department</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} department</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.name" label="Name"><x-input id="form.name" wire:model="form.name" required maxlength="255" /></x-field>
                <x-field name="form.description" label="Description"><x-textarea id="form.description" wire:model="form.description" /></x-field>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search departments" placeholder="Search departments…" />

    </div>
    <div class="mb-4 flex items-center gap-4">
        <x-button wire:click="resetFilters" variant="secondary">Clear filters</x-button>
        <span wire:loading.delay role="status" class="text-sm text-slate-500">Updating…</span>
        <span class="text-sm text-slate-500">{{ $records->total() }} results</span>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Name</th><th class="p-4">Description</th><th class="p-4">Doctors</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4 font-medium">{{ $record->name }}</td><td class="p-4">{{ $record->description ?: '—' }}</td><td class="p-4">{{ $record->doctors_count }}</td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this department? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No departments found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>
