<?php

namespace App\Livewire;

use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Prescriptions extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array{medical_record_id: int|string, prescription_date: string, notes: ?string} */
    public array $form = ['medical_record_id' => '', 'prescription_date' => '', 'notes' => ''];

    /** @var list<array{medicine_name: string, dosage: string, frequency: string, duration: string, instructions: ?string}> */
    public array $items = [];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'dateFilter'])) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        Gate::authorize('manage-prescriptions');
        $this->cancel();
        $this->form['prescription_date'] = today()->toDateString();
        $this->addItem();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset('form', 'items', 'editingId', 'showForm');
        $this->resetValidation();
    }

    public function addItem(): void
    {
        Gate::authorize('manage-prescriptions');
        if (count($this->items) < 20) {
            $this->items[] = ['medicine_name' => '', 'dosage' => '', 'frequency' => '', 'duration' => '', 'instructions' => ''];
        }
    }

    public function removeItem(int $index): void
    {
        Gate::authorize('manage-prescriptions');
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage-prescriptions');
        $prescription = Prescription::with('items')->findOrFail($id);
        Gate::authorize('update', $prescription);
        $this->editingId = $prescription->id;
        $this->form = [
            'medical_record_id' => $prescription->medical_record_id,
            'prescription_date' => $prescription->prescription_date->toDateString(),
            'notes' => $prescription->notes,
        ];
        $this->items = $prescription->items->map(fn ($item) => $item->only(['medicine_name', 'dosage', 'frequency', 'duration', 'instructions']))->all();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-prescriptions');
        if ($this->editingId) {
            Gate::authorize('update', Prescription::findOrFail($this->editingId));
        }

        $data = $this->validate([
            'form.medical_record_id' => ['required', 'integer', 'exists:medical_records,id'],
            'form.prescription_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*' => ['array:medicine_name,dosage,frequency,duration,instructions'],
            'items.*.medicine_name' => ['required', 'string', 'max:255'],
            'items.*.dosage' => ['required', 'string', 'max:255'],
            'items.*.frequency' => ['required', 'string', 'max:255'],
            'items.*.duration' => ['required', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($data): void {
            $record = MedicalRecord::findOrFail($data['form']['medical_record_id']);
            Gate::authorize('update', $record);
            if ($data['form']['prescription_date'] < $record->visit_date->toDateString()) {
                throw ValidationException::withMessages(['form.prescription_date' => 'Prescription date cannot be before the visit date.']);
            }
            $attributes = [
                'medical_record_id' => $record->id,
                'patient_id' => $record->patient_id,
                'doctor_id' => $record->doctor_id,
                'prescription_date' => $data['form']['prescription_date'],
                'notes' => $data['form']['notes'] ?: null,
            ];

            if ($this->editingId) {
                $prescription = Prescription::findOrFail($this->editingId);
                Gate::authorize('update', $prescription);
                $prescription->update($attributes);
                $prescription->items()->delete();
            } else {
                $prescription = Prescription::create($attributes);
            }

            $prescription->items()->createMany($data['items']);
        });

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Prescription saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-prescriptions');
        $prescription = Prescription::findOrFail($id);
        Gate::authorize('delete', $prescription);
        $prescription->delete();
        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Prescription and its items deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-prescriptions');
        $doctorId = auth()->user()->doctor?->id ?? 0;
        $isDoctor = auth()->user()->role === 'doctor';

        return view('livewire.prescriptions', [
            'records' => Prescription::with(['patient', 'doctor', 'items'])
                ->when($isDoctor, fn (Builder $query) => $query->where('doctor_id', $doctorId))
                ->when($this->dateFilter !== '', fn (Builder $query) => $query->whereDate('prescription_date', $this->dateFilter))
                ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->whereHas('patient', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('items', fn (Builder $query) => $query->where('medicine_name', 'like', '%'.$this->search.'%'));
                }))
                ->latest('id')->paginate(10),
            'medicalRecords' => MedicalRecord::with(['patient', 'doctor'])
                ->when($isDoctor, fn (Builder $query) => $query->where('doctor_id', $doctorId))
                ->latest('id')->get(),
        ]);
    }
}
