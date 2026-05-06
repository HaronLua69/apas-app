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

test('administrators can create students with a compatible adviser binding', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();
    $adviser = User::factory()->adviser()->create();

    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.students.store'), [
            'id_number' => '2022-3741',
            'first_name' => 'Wilson',
            'middle_name' => 'B',
            'last_name' => 'Augosto',
            'name_suffix' => null,
            'sex_at_birth' => 'Male',
            'course_id' => $course->id,
            'major_id' => $major->id,
            'year_level' => 2,
            'adviser_assignment_id' => $assignment->id,
            'email' => 'wilson@example.com',
            'home_address' => 'Iligan City',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'admission_date' => '2026-06-01',
        ])
        ->assertRedirect(route('admin.students.index'));

    $student = User::query()->where('id_number', '2022-3741')->firstOrFail();

    $this->assertDatabaseHas('student_adviser_bindings', [
        'student_profile_id' => $student->studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
        'assigned_by_user_id' => $admin->id,
    ]);
});

test('administrators can not bind students to incompatible adviser assignments', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();
    $adviser = User::factory()->adviser()->create();

    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => null,
        'year_level' => 4,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.students.create'))
        ->post(route('admin.students.store'), [
            'id_number' => '2023-1111',
            'first_name' => 'Casey',
            'middle_name' => null,
            'last_name' => 'Santos',
            'name_suffix' => null,
            'sex_at_birth' => 'Female',
            'course_id' => $course->id,
            'major_id' => $major->id,
            'year_level' => 2,
            'adviser_assignment_id' => $assignment->id,
            'email' => 'casey@example.com',
            'home_address' => 'Iligan City',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'admission_date' => '2026-06-01',
        ])
        ->assertRedirect(route('admin.students.create'))
        ->assertSessionHasErrors(['adviser_assignment_id']);

    expect(StudentAdviserBinding::query()->count())->toBe(0);
});

test('administrators preserve admission history when changing program and can mark a student as withdrawn', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $otherDepartment = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();
    $otherCourse = Course::factory()->for($otherDepartment)->create();

    $student = User::factory()->create();
    $profile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);
    $profile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2024-06-01',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.students.update', $student), [
            'id_number' => $student->id_number,
            'first_name' => $student->first_name,
            'middle_name' => $student->middle_name,
            'last_name' => $student->last_name,
            'name_suffix' => $student->name_suffix,
            'sex_at_birth' => $profile->sex_at_birth,
            'course_id' => $otherCourse->id,
            'major_id' => null,
            'year_level' => 2,
            'adviser_assignment_id' => null,
            'email' => $student->email,
            'home_address' => $profile->home_address,
            'username' => $student->username,
            'password' => null,
            'password_confirmation' => null,
            'admission_date' => '2025-06-01',
            'is_withdrawn' => '1',
        ])
        ->assertRedirect(route('admin.students.index'));

    expect($profile->fresh()->withdrawn_at)->not->toBeNull();

    $this->assertDatabaseHas('student_admissions', [
        'student_profile_id' => $profile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('student_admissions', [
        'student_profile_id' => $profile->id,
        'course_id' => $otherCourse->id,
        'major_id' => null,
        'is_active' => true,
    ]);
});
