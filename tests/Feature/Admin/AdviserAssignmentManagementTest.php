<?php

use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentAdviserBinding;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create and remove adviser assignments', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Computer Engineering',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Embedded Systems',
    ]);
    $adviser = User::factory()->adviser()->create();

    AdviserProfile::factory()->for($adviser)->for($department)->create();

    $this->actingAs($admin)
        ->get(route('admin.advisers.assignments.index', $adviser))
        ->assertOk()
        ->assertSee($course->name);

    $this->actingAs($admin)->post(route('admin.advisers.assignments.store', $adviser), [
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 3,
    ])->assertRedirect(route('admin.advisers.assignments.index', $adviser));

    $assignment = $adviser->adviserProfile->fresh()->adviserAssignments()->firstOrFail();

    $this->assertDatabaseHas('adviser_assignments', [
        'id' => $assignment->id,
        'adviser_profile_id' => $adviser->adviserProfile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 3,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.adviser-assignments.destroy', $assignment))
        ->assertRedirect(route('admin.advisers.assignments.index', $adviser));

    $this->assertDatabaseMissing('adviser_assignments', [
        'id' => $assignment->id,
    ]);
});

test('non administrators can not access adviser assignment management', function () {
    $student = User::factory()->create();
    $adviser = User::factory()->adviser()->create();

    $this->actingAs($student)
        ->get(route('admin.advisers.assignments.index', $adviser))
        ->assertForbidden();
});

test('administrators can not remove adviser assignments that still have bound students', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $adviser = User::factory()->adviser()->create();

    AdviserProfile::factory()->for($adviser)->for($department)->create();

    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => null,
        'year_level' => 2,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => null,
        'admission_date' => '2026-06-01',
        'is_active' => true,
    ]);

    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
        'assigned_by_user_id' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.advisers.assignments.index', $adviser))
        ->delete(route('admin.adviser-assignments.destroy', $assignment))
        ->assertRedirect(route('admin.advisers.assignments.index', $adviser))
        ->assertSessionHasErrors(['adviser_assignment']);

    $this->assertDatabaseHas('adviser_assignments', [
        'id' => $assignment->id,
    ]);
});
