<?php

use App\Livewire\Prescriptions;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('doctor can create and edit a prescription with multiple medicines', function () {
    $user = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->for($user)->create();
    $record = MedicalRecord::factory()->for($doctor)->create();
    $item = ['medicine_name' => 'Example A', 'dosage' => '1 tablet', 'frequency' => 'Daily', 'duration' => '3 days', 'instructions' => 'Demo only'];
    $component = Livewire::actingAs($user)->test(Prescriptions::class)->call('create')
        ->set('form.medical_record_id', $record->id)->set('items', [$item, array_merge($item, ['medicine_name' => 'Example B'])])
        ->call('save')->assertHasNoErrors();
    $prescription = Prescription::sole();
    expect($prescription->patient_id)->toBe($record->patient_id);
    expect($prescription->doctor_id)->toBe($doctor->id);
    $this->assertDatabaseCount('prescription_items', 2);
    $component->call('edit', $prescription->id)->call('removeItem', 1)
        ->set('items.0.dosage', '2 tablets')->call('save')->assertHasNoErrors();
    $this->assertDatabaseCount('prescription_items', 1);
    $this->assertDatabaseHas('prescription_items', ['prescription_id' => $prescription->id, 'dosage' => '2 tablets']);
    $component->call('delete', $prescription->id);
    $this->assertDatabaseCount('prescriptions', 0);
    $this->assertDatabaseCount('prescription_items', 0);
});

test('invalid items do not create a prescription', function () {
    $record = MedicalRecord::factory()->create();
    Livewire::actingAs(User::factory()->admin()->create())->test(Prescriptions::class)->call('create')
        ->set('form.medical_record_id', $record->id)->call('save')
        ->assertHasErrors(['items.0.medicine_name', 'items.0.dosage', 'items.0.frequency', 'items.0.duration']);
    $this->assertDatabaseCount('prescriptions', 0);
    $this->assertDatabaseCount('prescription_items', 0);
});

test('a database failure on an item rolls back the entire prescription', function () {
    $record = MedicalRecord::factory()->create();
    $component = Livewire::actingAs(User::factory()->admin()->create())->test(Prescriptions::class)->call('create')
        ->set('form.medical_record_id', $record->id)->set('items', [
            ['medicine_name' => 'Valid', 'dosage' => '1', 'frequency' => 'Daily', 'duration' => '3 days', 'instructions' => ''],
            ['medicine_name' => 'Fail', 'dosage' => '1', 'frequency' => 'Daily', 'duration' => '3 days', 'instructions' => ''],
        ]);
    DB::unprepared("CREATE TRIGGER reject_test_item BEFORE INSERT ON prescription_items WHEN NEW.medicine_name = 'Fail' BEGIN SELECT RAISE(ABORT, 'Test item failure'); END");

    try {
        expect(fn () => $component->call('save'))->toThrow(QueryException::class);
    } finally {
        DB::unprepared('DROP TRIGGER reject_test_item');
    }

    $this->assertDatabaseCount('prescriptions', 0);
    $this->assertDatabaseCount('prescription_items', 0);
});

test('doctor cannot access another doctors prescription or use their record', function () {
    $user = User::factory()->doctor()->create();
    Doctor::factory()->for($user)->create();
    $prescription = Prescription::factory()->has(PrescriptionItem::factory()->state(['medicine_name' => 'Private medicine']), 'items')->create();
    Livewire::actingAs($user)->test(Prescriptions::class)->assertDontSee('Private medicine')
        ->call('edit', $prescription->id)->assertForbidden();
    $item = ['medicine_name' => 'Example', 'dosage' => '1', 'frequency' => 'Daily', 'duration' => '3 days'];
    Livewire::actingAs($user)->test(Prescriptions::class)->call('create')
        ->set('form.medical_record_id', $prescription->medical_record_id)->set('items', [$item])
        ->call('save')->assertForbidden();
    $this->assertDatabaseCount('prescriptions', 1);
});
