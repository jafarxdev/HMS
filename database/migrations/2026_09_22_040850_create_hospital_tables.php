<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 30);
            $table->string('specialization');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('blood_group', 3)->nullable();
            $table->string('emergency_contact')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->index()->constrained()->restrictOnDelete();
            $table->date('appointment_date')->index();
            $table->time('appointment_time');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement("CREATE UNIQUE INDEX appointments_doctor_slot_unique ON appointments (doctor_id, appointment_date, appointment_time) WHERE status != 'cancelled'");

        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('doctor_id')->index()->constrained()->restrictOnDelete();
            $table->text('diagnosis');
            $table->text('symptoms')->nullable();
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->date('visit_date')->index();
            $table->timestamps();
            $table->unique(['id', 'doctor_id', 'patient_id']);
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_record_id');
            $table->foreignId('doctor_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->index()->constrained()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->date('prescription_date')->index();
            $table->timestamps();
            $table->index(['medical_record_id', 'doctor_id', 'patient_id']);
            $table->foreign(['medical_record_id', 'doctor_id', 'patient_id'])
                ->references(['id', 'doctor_id', 'patient_id'])->on('medical_records')->restrictOnDelete();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->index()->constrained()->cascadeOnDelete();
            $table->string('medicine_name');
            $table->string('dosage');
            $table->string('frequency');
            $table->string('duration');
            $table->text('instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('departments');
    }
};
