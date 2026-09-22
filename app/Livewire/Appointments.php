<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Appointments extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $dateFilter = '';

    #[Url(except: '')]
    public string $doctorFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Locked]
    public ?int $editingId = null;

    public bool $showForm = false;

    /** @var array<string, mixed> */
    public array $form = ['patient_id' => '', 'doctor_id' => '', 'appointment_date' => '', 'appointment_time' => '', 'status' => 'scheduled', 'reason' => '', 'notes' => ''];

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'dateFilter', 'doctorFilter', 'statusFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        Gate::authorize('manage-appointments');
        $this->reset('search', 'dateFilter', 'doctorFilter', 'statusFilter');
        $this->resetPage();
    }

    public function create(): void
    {
        Gate::authorize('manage-appointments');
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
        Gate::authorize('manage-appointments');
        $record = Appointment::findOrFail($id);
        $this->editingId = $record->id;
        $this->form = $record->only(['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'status', 'reason', 'notes']);
        $this->form['appointment_date'] = $record->appointment_date->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage-appointments');
        $data = $this->validate([
            'form.patient_id' => ['required', 'integer', 'exists:patients,id'],
            'form.doctor_id' => ['required', 'integer', Rule::exists('doctors', 'id')->where('status', 'active')],
            'form.appointment_date' => ['required', 'date_format:Y-m-d'],
            'form.appointment_time' => ['required', 'date_format:H:i'],
            'form.status' => ['required', Rule::in(Appointment::STATUSES)],
            'form.reason' => ['required', 'string', 'max:5000'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ])['form'];

        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        if ($data['status'] === 'scheduled' && Carbon::parse($data['appointment_date'].' '.$data['appointment_time'])->isPast()) {
            throw ValidationException::withMessages(['form.appointment_date' => 'Scheduled appointments must be in the future.']);
        }

        $duplicate = Appointment::where('doctor_id', $data['doctor_id'])
            ->where('appointment_date', $data['appointment_date'])
            ->where('appointment_time', $data['appointment_time'])
            ->where('status', '!=', 'cancelled')
            ->when($this->editingId, fn (Builder $query) => $query->whereKeyNot($this->editingId))
            ->exists();

        if ($data['status'] !== 'cancelled' && $duplicate) {
            throw ValidationException::withMessages(['form.appointment_time' => 'This doctor is already booked at that time.']);
        }

        try {
            if ($this->editingId) {
                Appointment::findOrFail($this->editingId)->update($data);
            } else {
                Appointment::create($data);
            }
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['form.appointment_time' => 'This doctor is already booked at that time.']);
        }

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Appointment saved successfully.');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-appointments');
        $record = Appointment::findOrFail($id);
        $record->delete();

        $this->cancel();
        $this->resetPage();
        session()->flash('success', 'Appointment deleted successfully.');
    }

    public function render(): View
    {
        Gate::authorize('manage-appointments');

        return view('livewire.appointments', [
            'records' => Appointment::query()->with(['patient', 'doctor'])
                ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query): void {
                    $query->where('reason', 'like', '%'.$this->search.'%')
                        ->orWhereHas('patient', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('doctor', fn (Builder $query) => $query->where('name', 'like', '%'.$this->search.'%'));
                }))
                ->when($this->dateFilter !== '', fn (Builder $query) => $query->whereDate('appointment_date', $this->dateFilter))
                ->when($this->doctorFilter !== '', fn (Builder $query) => $query->where('doctor_id', $this->doctorFilter))
                ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
                ->orderByDesc('id')->paginate(10),
            'patients' => Patient::orderBy('name')->get(),
            'doctors' => Doctor::orderBy('name')->get(),
        ]);
    }
}
