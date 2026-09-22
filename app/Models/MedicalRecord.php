<?php

namespace App\Models;

use Database\Factories\MedicalRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    /** @use HasFactory<MedicalRecordFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'diagnosis',
        'symptoms',
        'treatment',
        'notes',
        'visit_date',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['visit_date' => 'date'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
