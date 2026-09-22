<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('guests are redirected from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('staff can log in and access the dashboard', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'password')
        ->call('login')->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    $this->get('/dashboard')->assertSee('Dashboard');
});

test('incorrect credentials are rejected', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)->set('email', $user->email)->set('password', 'wrong')
        ->call('login')->assertHasErrors('email')->assertSee('The provided credentials are incorrect.');

    $this->assertGuest();
});

test('login requires valid input', function () {
    Livewire::test(Login::class)->call('login')->assertHasErrors(['email', 'password']);
    $this->assertGuest();
});

test('repeated failed login attempts are throttled', function () {
    $user = User::factory()->create();
    $component = Livewire::test(Login::class)->set('email', $user->email);

    foreach (range(1, 5) as $attempt) {
        $component->set('password', 'wrong')->call('login');
    }

    $component->set('password', 'password')->call('login')
        ->assertHasErrors('email')->assertSee('Too many login attempts.');
    $this->assertGuest();
});

test('staff can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
});
