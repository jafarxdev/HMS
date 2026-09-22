<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class MedicalRecords extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $patientFilter = '';

    #[Url(except: '')]
    public string $doctorFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [
        'patient_id' => '',
        'doctor_id' => '',
        'diagnosis' => '',
        'symptoms' => '',
        'treatment' => '',
        'notes' => '',
        'visit_date' => '',
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'patientFilter', 'doctorFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        Gate::authorize('manage-medical-records');
        $this->reset('search', 'patientFilter', 'doctorFilter');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('manage-medical-records');
        $this->reset('form', 'editingId');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->reset('form', 'editingId', 'showForm');
        $this->resetValidation();
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage-medical-records');
        $record = MedicalRecord::findOrFail($id);
        Gate::authorize('update', $record);
        $this->editingId = $record->id;
        $this->form = $record->only(['patient_id', 'doctor_id', 'diagnosis', 'symptoms', 'treatment', 'notes', 'visit_date']);
        $this->form['visit_date'] = $record->visit_date->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-medical-records');
        if ($this->editingId) {
            Gate::authorize('update', MedicalRecord::findOrFail($this->editingId));
        }

        $data = $this->validate([
            'form.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'form.doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'form.diagnosis' => ['required', 'string', 'max:5000'],
            'form.symptoms' => ['nullable', 'string', 'max:5000'],
            'form.treatment' => ['nullable', 'string', 'max:5000'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
            'form.visit_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ])['form'];

        if (auth()->user()->role === 'doctor') {
            abort_unless((int) $data['doctor_id'] === (auth()->user()->doctor?->id ?? 0), 403);
            abort_unless($this->patientsQuery()->whereKey($data['patient_id'])->exists(), 403);
        }

        if ($this->editingId) {
            $record = MedicalRecord::findOrFail($this->editingId);
            if ($record->prescriptions()->exists() && ((int) $data['doctor_id'] !== $record->doctor_id || (int) $data['patient_id'] !== $record->patient_id)) {
                throw ValidationException::withMessages(['form.patient_id' => 'Patient and doctor cannot change while prescriptions exist.']);
            }
        }

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->editingId) {
            MedicalRecord::findOrFail($this->editingId)->update($data);
        } else {
            MedicalRecord::create($data);
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Medical record saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-medical-records');
        $record = MedicalRecord::findOrFail($id);
        Gate::authorize('delete', $record);
        if ($record->prescriptions()->exists()) {
            session()->flash('error', 'This medical record has prescriptions and cannot be deleted.');

            return;
        }

        try {
            $record->delete();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw $exception;
            }
            session()->flash('error', 'This medical record has prescriptions and cannot be deleted.');

            return;
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Medical record deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-medical-records');

        return view('livewire.medical-records', [
            'records' => MedicalRecord::query()->with(['patient', 'doctor'])
                ->when(auth()->user()->role === 'doctor', fn (Builder $query) => $query->where('doctor_id', auth()->user()->doctor?->id ?? 0))
                ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->where('diagnosis', 'like', '%'.$this->search.'%')
                        ->orWhereHas('patient', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'));
                }))
                ->when($this->patientFilter !== '', fn (Builder $query) => $query->where('patient_id', $this->patientFilter))
                ->when($this->doctorFilter !== '', fn (Builder $query) => $query->where('doctor_id', $this->doctorFilter))
                ->orderByDesc('id')->paginate(10),
            'patients' => $this->patientsQuery()->orderBy('name')->get(),
            'doctors' => Doctor::when(auth()->user()->role === 'doctor', fn (Builder $query) => $query->where('user_id', auth()->id()))->orderBy('name')->get(),
        ]);
    }

    protected function patientsQuery(): Builder
    {
        $query = Patient::query();

        if (auth()->user()->role === 'doctor') {
            $doctorId = auth()->user()->doctor?->id ?? 0;
            $query->where(function (Builder $query) use ($doctorId): void {
                $query->whereHas('appointments', fn (Builder $query) => $query->where('doctor_id', $doctorId))
                    ->orWhereHas('medicalRecords', fn (Builder $query) => $query->where('doctor_id', $doctorId));
            });
        }

        return $query;
    }
}
