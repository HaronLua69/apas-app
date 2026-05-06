<?php

use App\Models\AcademicTerm;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create academic terms and resolve the active term from the current date', function () {
    Carbon::setTestNow('2026-05-03 09:00:00');

    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->post(route('admin.academic-terms.store'), [
            'academic_year_start' => 2025,
            'term_name' => '2nd Semester',
            'date_from' => '2026-01-15',
            'date_to' => '2026-05-31',
        ])
        ->assertRedirect(route('admin.academic-terms.index'));

    $academicTerm = AcademicTerm::query()->firstOrFail();

    expect($academicTerm->academicYearLabel())->toBe('A.Y. 2025-2026');
    expect($academicTerm->fullLabel())->toBe('2nd Semester, A.Y. 2025-2026');
    expect(AcademicTerm::active()?->is($academicTerm))->toBeTrue();

    $this->actingAs($admin)
        ->get(route('admin.academic-terms.index'))
        ->assertOk()
        ->assertSee('Active Term')
        ->assertSee('2nd Semester, A.Y. 2025-2026');

    Carbon::setTestNow();
});

test('administrators can not create overlapping academic terms', function () {
    $admin = User::factory()->administrator()->create();

    AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '2nd Semester',
        'date_from' => '2026-01-15',
        'date_to' => '2026-05-31',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.academic-terms.create'))
        ->post(route('admin.academic-terms.store'), [
            'academic_year_start' => 2026,
            'term_name' => 'Summer Term',
            'date_from' => '2026-05-01',
            'date_to' => '2026-06-15',
        ])
        ->assertRedirect(route('admin.academic-terms.create'))
        ->assertSessionHasErrors(['date_from']);
});

test('non administrators can not access academic term management', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.academic-terms.index'))
        ->assertForbidden();
});
