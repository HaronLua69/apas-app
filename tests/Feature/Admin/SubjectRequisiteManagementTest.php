<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create and remove subject requisites', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Database Systems',
    ]);
    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'CS201',
    ]);
    $requisite = Subject::factory()->for($department)->create([
        'subject_code' => 'CS101',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.subjects.requisites.index', $subject))
        ->assertOk()
        ->assertSee('Current Requisites')
        ->assertSee('Applies To')
        ->assertSee('Database Systems');

    $this->actingAs($admin)->post(route('admin.subjects.requisites.store', $subject), [
        'requisite_subject_id' => $requisite->id,
        'type' => 'prerequisite',
        'scope_key' => 'major:'.$major->id,
    ])->assertRedirect(route('admin.subjects.requisites.index', $subject));

    $this->assertDatabaseHas('subject_requisites', [
        'subject_id' => $subject->id,
        'requisite_subject_id' => $requisite->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $major->id,
    ]);

    $subjectRequisite = $subject->requisites()->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('admin.subject-requisites.destroy', $subjectRequisite))
        ->assertRedirect(route('admin.subjects.requisites.index', $subject));

    $this->assertDatabaseMissing('subject_requisites', [
        'id' => $subjectRequisite->id,
    ]);
});

test('non administrators can not access subject requisite management', function () {
    $student = User::factory()->create();
    $subject = Subject::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.subjects.requisites.index', $subject))
        ->assertForbidden();
});
