<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('department names must be unique', function () {
    DB::table('departments')->insert(['name' => 'Cardiology']);

    expect(fn () => DB::table('departments')->insert(['name' => 'Cardiology']))
        ->toThrow(QueryException::class);
});

test('a doctor cannot reference a missing department', function () {
    expect(fn () => DB::table('doctors')->insert([
        'department_id' => 999, 'name' => 'Doctor', 'email' => 'doctor@example.test',
        'phone' => '0700000000', 'specialization' => 'General', 'status' => 'active',
    ]))->toThrow(QueryException::class);
});
