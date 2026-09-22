<?php

namespace App\Policies;

use App\Models\MedicalRecord;
use App\Models\User;

class MedicalRecordPolicy
{
    public function update(User $user, MedicalRecord $medicalRecord): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'doctor' && $user->doctor?->id === $medicalRecord->doctor_id);
    }

    public function delete(User $user, MedicalRecord $medicalRecord): bool
    {
        return $this->update($user, $medicalRecord);
    }
}
