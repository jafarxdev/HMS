<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function update(User $user, Prescription $prescription): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'doctor' && $user->doctor?->id === $prescription->doctor_id);
    }

    public function delete(User $user, Prescription $prescription): bool
    {
        return $this->update($user, $prescription);
    }
}
