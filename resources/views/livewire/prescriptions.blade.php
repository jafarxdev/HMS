<div>
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold">Prescriptions</h1><p class="mt-1 text-sm text-slate-500">Create a prescription from a medical record and add its medicines.</p></div>
        <x-button wire:click="create">Add prescription</x-button>
    </div>
    <x-alert />
    @if ($showForm)
        <form wire:submit="save" class="mb-6 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">{{ $editingId ? 'Edit' : 'New' }} prescription</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <x-field name="form.medical_record_id" label="Medical record"><x-select id="form.medical_record_id" wire:model="form.medical_record_id"><option value="">Select medical record</option>@foreach ($medicalRecords as $medicalRecord)<option wire:key="medical-record-{{ $medicalRecord->id }}" value="{{ $medicalRecord->id }}">#{{ $medicalRecord->id }} · {{ $medicalRecord->patient->name }} / {{ $medicalRecord->doctor->name }} / {{ $medicalRecord->visit_date->format('d M Y') }}</option>@endforeach</x-select></x-field>
                <x-field name="form.prescription_date" label="Prescription date"><x-input id="form.prescription_date" type="date" wire:model="form.prescription_date" /></x-field>
                <x-field name="form.notes" label="Notes"><x-textarea id="form.notes" wire:model="form.notes" /></x-field>
                <div class="md:col-span-2">
                    <h3 class="mb-4 font-semibold">Medicines</h3>
                    @error('items')<p role="alert" class="mb-3 text-sm text-red-700">{{ $message }}</p>@enderror
                    <div class="grid gap-4">
                    @foreach ($items as $index => $item)
                        <fieldset wire:key="item-{{ $index }}" class="rounded-lg border border-slate-200 p-4">
                            <legend class="px-2 text-sm font-semibold">Medicine {{ $index + 1 }}</legend>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-field name="items.{{ $index }}.medicine_name" label="Medicine Name"><x-input id="items.{{ $index }}.medicine_name" wire:model="items.{{ $index }}.medicine_name" /></x-field>
                                <x-field name="items.{{ $index }}.dosage" label="Dosage"><x-input id="items.{{ $index }}.dosage" wire:model="items.{{ $index }}.dosage" /></x-field>
                                <x-field name="items.{{ $index }}.frequency" label="Frequency"><x-input id="items.{{ $index }}.frequency" wire:model="items.{{ $index }}.frequency" /></x-field>
                                <x-field name="items.{{ $index }}.duration" label="Duration"><x-input id="items.{{ $index }}.duration" wire:model="items.{{ $index }}.duration" /></x-field>
                                <x-field name="items.{{ $index }}.instructions" label="Instructions"><x-input id="items.{{ $index }}.instructions" wire:model="items.{{ $index }}.instructions" /></x-field>
                            </div>
                            <x-button class="mt-3" wire:click="removeItem({{ $index }})" variant="danger">Remove medicine</x-button>
                        </fieldset>
                    @endforeach
                    </div>
                    <x-button class="mt-4" wire:click="addItem" variant="secondary">Add medicine</x-button>
                </div>
            </div>
            <div class="mt-6 flex gap-3">
                <x-button type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Save</span><span wire:loading wire:target="save">Saving…</span></x-button>
                <x-button wire:click="cancel" variant="secondary">Cancel</x-button>
            </div>
        </form>
    @endif
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-input type="search" wire:model.live.debounce.300ms="search" aria-label="Search prescriptions" placeholder="Search prescriptions…" />
        <x-input type="date" wire:model.live="dateFilter" aria-label="Prescription date filter" />
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-600"><tr><th class="p-4">Patient / Doctor</th><th class="p-4">Date</th><th class="p-4">Medicines</th><th class="p-4 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($records as $record)
                    <tr wire:key="record-{{ $record->id }}">
                        <td class="p-4 font-medium">{{ $record->patient->name }}<p class="text-xs font-normal text-slate-500">{{ $record->doctor->name }}</p></td>
                        <td class="p-4 whitespace-nowrap">{{ $record->prescription_date->format('d M Y') }}</td>
                        <td class="p-4"><ul class="grid gap-2">@foreach ($record->items as $item)<li wire:key="saved-item-{{ $item->id }}"><span class="font-medium">{{ $item->medicine_name }}</span> — {{ $item->dosage }}, {{ $item->frequency }}, {{ $item->duration }}<p class="text-xs text-slate-500">{{ $item->instructions }}</p></li>@endforeach</ul>@if ($record->notes)<p class="mt-2 text-xs text-slate-500">{{ $record->notes }}</p>@endif</td>
                        <td class="p-4"><div class="flex justify-end gap-2">
                            <x-button wire:click="edit({{ $record->id }})" variant="secondary">Edit</x-button>
                            <x-button wire:click="delete({{ $record->id }})" wire:confirm="Delete this prescription? This cannot be undone." wire:loading.attr="disabled" variant="danger">Delete</x-button>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-10 text-center text-slate-500">No prescriptions found. Try another search or add a new entry.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $records->links() }}</div>
</div>