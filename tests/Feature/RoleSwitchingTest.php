<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dual-role users can switch active roles and are redirected to the selected dashboard', function () {
    $user = User::factory()
        ->administrator()
        ->secondaryRole(UserRole::Adviser)
        ->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Colleges')
        ->assertDontSee('Advisees');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard', absolute: false));

    $this->actingAs($user)
        ->post(route('role.switch'), ['role' => UserRole::Adviser->value])
        ->assertRedirect(route('adviser.dashboard', absolute: false));

    expect(session('active_role'))->toBe(UserRole::Adviser->value);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('adviser.dashboard', absolute: false));

    $this->actingAs($user)
        ->get(route('adviser.dashboard'))
        ->assertOk()
        ->assertSee('Advisees')
        ->assertDontSee('Colleges');
});

test('users can not switch to roles they do not own', function () {
    $user = User::factory()->administrator()->create();

    $this->actingAs($user)
        ->post(route('role.switch'), ['role' => UserRole::Student->value])
        ->assertForbidden();

    expect(session('active_role'))->toBeNull();
});
