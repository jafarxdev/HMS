<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
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
class Doctors extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $departmentFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = [
        'department_id' => '',
        'user_id' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'specialization' => '',
        'status' => 'active',
    ];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'departmentFilter', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        Gate::authorize('manage-doctors');
        $this->reset('search', 'departmentFilter', 'statusFilter');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('manage-doctors');
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
        Gate::authorize('manage-doctors');
        $record = Doctor::findOrFail($id);
        $this->editingId = $record->id;
        $this->form = $record->only(['department_id', 'user_id', 'name', 'email', 'phone', 'specialization', 'status']);

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-doctors');
        $data = $this->validate([
            'form.department_id' => ['required', 'integer', 'exists:departments,id'],
            'form.user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'doctor'), Rule::unique('doctors', 'user_id')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.email' => ['required', 'email', 'max:255', Rule::unique('doctors', 'email')->ignore($this->editingId)],
            'form.phone' => ['required', 'string', 'max:30'],
            'form.specialization' => ['required', 'string', 'max:255'],
            'form.status' => ['required', Rule::in(Doctor::STATUSES)],
        ])['form'];

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->editingId) {
            Doctor::findOrFail($this->editingId)->update($data);
        } else {
            Doctor::create($data);
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Doctor saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-doctors');
        $record = Doctor::findOrFail($id);
        if ($record->appointments()->exists() || $record->medicalRecords()->exists() || $record->prescriptions()->exists()) {
            session()->flash('error', 'This doctor has clinical history and cannot be deleted.');

            return;
        }

        try {
            $record->delete();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw $exception;
            }
            session()->flash('error', 'This doctor has clinical history and cannot be deleted.');

            return;
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Doctor deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-doctors');

        return view('livewire.doctors', [
            'records' => Doctor::query()->with('department')
                ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')->orWhere('email', 'like', '%'.$this->search.'%')->orWhere('specialization', 'like', '%'.$this->search.'%');
                }))
                ->when($this->departmentFilter !== '', fn (Builder $query) => $query->where('department_id', $this->departmentFilter))
                ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
                ->orderByDesc('id')->paginate(10),
            'departments' => Department::orderBy('name')->get(),
            'doctorUsers' => User::where('role', 'doctor')->orderBy('name')->get(),
        ]);
    }
}
