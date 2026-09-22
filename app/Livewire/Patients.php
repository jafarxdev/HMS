<?php

namespace App\Livewire;

use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Patients extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $genderFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = ['name' => '', 'gender' => '', 'date_of_birth' => '', 'phone' => '', 'email' => '', 'address' => '', 'blood_group' => '', 'emergency_contact' => ''];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'genderFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        Gate::authorize('manage-patients');
        $this->reset('search', 'genderFilter');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('manage-patients');
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
        Gate::authorize('manage-patients');
        $record = Patient::findOrFail($id);
        $this->editingId = $record->id;
        $this->form = $record->only(['name', 'gender', 'date_of_birth', 'phone', 'email', 'address', 'blood_group', 'emergency_contact']);
        $this->form['date_of_birth'] = $record->date_of_birth->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-patients');
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.gender' => ['required', Rule::in(Patient::GENDERS)],
            'form.date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'form.phone' => ['required', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:5000'],
            'form.blood_group' => ['nullable', Rule::in(Patient::BLOOD_GROUPS)],
            'form.emergency_contact' => ['nullable', 'string', 'max:255'],
        ])['form'];

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->editingId) {
            Patient::findOrFail($this->editingId)->update($data);
        } else {
            Patient::create($data);
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Patient saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-patients');
        $record = Patient::findOrFail($id);
        if ($record->appointments()->exists() || $record->medicalRecords()->exists() || $record->prescriptions()->exists()) {
            session()->flash('error', 'This patient has clinical history and cannot be deleted.');

            return;
        }

        try {
            $record->delete();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw $exception;
            }
            session()->flash('error', 'This patient has clinical history and cannot be deleted.');

            return;
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Patient deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-patients');

        return view('livewire.patients', [
            'records' => Patient::query()
                ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->when($this->genderFilter !== '', fn (Builder $query) => $query->where('gender', $this->genderFilter))
                ->orderByDesc('id')->paginate(10),

        ]);
    }
}
