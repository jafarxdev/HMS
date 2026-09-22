<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('the staff layout shows navigation and escapes staff names', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

    $this->actingAs($user)->get('/dashboard')
        ->assertSee('Main navigation')->assertSee('Sign out')
        ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});
