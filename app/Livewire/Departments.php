<?php

namespace App\Livewire;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Departments extends Component
{
    use WithPagination;

    public string $search = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = ['name' => '', 'description' => ''];

    public function updated(string $property): void
    {
        if (in_array($property, ['search'])) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        Gate::authorize('manage-departments');
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
        Gate::authorize('manage-departments');
        $record = Department::findOrFail($id);
        $this->editingId = $record->id;
        $this->form = $record->only(['name', 'description']);

        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-departments');
        $data = $this->validate([
            'form.name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($this->editingId)],
            'form.description' => ['nullable', 'string', 'max:5000'],
        ])['form'];

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($this->editingId) {
            Department::findOrFail($this->editingId)->update($data);
        } else {
            Department::create($data);
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Department saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-departments');
        $record = Department::findOrFail($id);
        if ($record->doctors()->exists()) {
            session()->flash('error', 'This department has doctors and cannot be deleted.');

            return;
        }

        try {
            $record->delete();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed')) {
                throw $exception;
            }
            session()->flash('error', 'This department has doctors and cannot be deleted.');

            return;
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Department deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-departments');

        return view('livewire.departments', [
            'records' => Department::query()->withCount('doctors')
                ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderByDesc('id')->paginate(10),

        ]);
    }
}
