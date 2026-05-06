<?php

use App\Enums\UserRole;
use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\StudentAdviserBinding;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated students are redirected to their dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('student.dashboard', absolute: false));
});

test('administrators can visit the administrator dashboard', function () {
    $user = User::factory()->administrator()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk()->assertSee('Administrator Dashboard');
});

test('advisers can visit the adviser dashboard', function () {
    $user = User::factory()->adviser()->create();

    $response = $this->actingAs($user)->get(route('adviser.dashboard'));

    $response->assertOk()->assertSee('Adviser Dashboard');
});

test('students can visit the student dashboard', function () {
    $user = User::factory()->create([
        'role' => UserRole::Student,
    ]);
    StudentProfile::factory()->for($user)->create();

    $response = $this->actingAs($user)->get(route('student.dashboard'));

    $response->assertOk()->assertSee('Student Dashboard');
});

test('students can see their bound adviser on the student dashboard', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();

    $student = User::factory()->create([
        'role' => UserRole::Student,
    ]);
    $studentProfile = StudentProfile::factory()->for($student)->create();

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => null,
        'year_level' => $studentProfile->year_level,
    ]);

    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    $response = $this->actingAs($student)->get(route('student.dashboard'));

    $response
        ->assertOk()
        ->assertSee('Program Adviser')
        ->assertSee($adviser->fullName())
        ->assertSee($adviser->id_number);
});

test('users can not access another roles dashboard', function () {
    $student = User::factory()->create();
    $adviser = User::factory()->adviser()->create();

    $this->actingAs($student)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($adviser)
        ->get(route('student.dashboard'))
        ->assertForbidden();
});