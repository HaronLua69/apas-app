<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Subject;
use App\Models\SubjectRequisite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create departments, programs, majors, and subjects through the apas manager', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create([
        'name' => 'College of Engineering',
    ]);

    $this->actingAs($admin)->post(route('admin.colleges.departments.store', $college), [
        'name' => 'Department of Electrical Engineering',
        'abbreviation' => 'DEE',
        'chairperson' => 'Ana Rivera',
        'description' => 'Electrical engineering department',
    ])->assertRedirect(route('admin.colleges.show', $college));

    $department = Department::query()->where('name', 'Department of Electrical Engineering')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.departments.courses.store', $department), [
        'name' => 'Bachelor of Science in Electrical Engineering',
        'abbreviation' => 'BSEE',
        'description' => 'Electrical engineering program',
    ])->assertRedirect(route('admin.departments.show', ['department' => $department, 'section' => 'programs']));

    $course = $department->courses()->where('abbreviation', 'BSEE')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.courses.majors.store', $course), [
        'name' => 'Power Systems',
        'description' => 'Major in power systems',
    ])->assertRedirect(route('admin.courses.show', $course));

    $this->actingAs($admin)->post(route('admin.departments.subjects.store', $department), [
        'subject_code' => 'EEE130',
        'subject_title' => 'Electrical Circuit Theory 1',
        'subject_types' => ['Lecture', 'Laboratory'],
        'credit_units' => 4,
        'grading_system' => 'numerical',
        'description' => 'Core circuits subject',
        'counts_toward_gpa' => 'on',
    ])->assertRedirect(route('admin.departments.show', ['department' => $department, 'section' => 'subjects']));

    $this->assertDatabaseHas('departments', [
        'college_id' => $college->id,
        'name' => 'Department of Electrical Engineering',
    ]);

    $this->assertDatabaseHas('courses', [
        'department_id' => $department->id,
        'abbreviation' => 'BSEE',
    ]);

    $this->assertDatabaseHas('majors', [
        'course_id' => $course->id,
        'name' => 'Power Systems',
    ]);

    $this->assertDatabaseHas('subjects', [
        'department_id' => $department->id,
        'subject_code' => 'EEE130',
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
    ]);
});

test('subject checkbox submissions map checked to true and unchecked to false', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();

    $this->actingAs($admin)->post(route('admin.departments.subjects.store', $department), [
        'subject_code' => 'IT201',
        'subject_title' => 'Algorithms',
        'subject_types' => ['Lecture'],
        'credit_units' => 3,
        'grading_system' => 'numerical',
        'description' => 'Counts toward GPA when checked.',
        'counts_toward_gpa' => 'on',
    ])->assertRedirect(route('admin.departments.show', ['department' => $department, 'section' => 'subjects']));

    $this->actingAs($admin)->post(route('admin.departments.subjects.store', $department), [
        'subject_code' => 'IT202',
        'subject_title' => 'Seminar in Computing',
        'subject_types' => ['Seminar'],
        'credit_units' => 1,
        'grading_system' => 'letter',
        'description' => 'Does not count toward GPA when unchecked.',
    ])->assertRedirect(route('admin.departments.show', ['department' => $department, 'section' => 'subjects']));

    $this->assertDatabaseHas('subjects', [
        'department_id' => $department->id,
        'subject_code' => 'IT201',
        'counts_toward_gpa' => true,
    ]);

    $this->assertDatabaseHas('subjects', [
        'department_id' => $department->id,
        'subject_code' => 'IT202',
        'counts_toward_gpa' => false,
    ]);
});

test('administrators can view the college and department management pages', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create([
        'name' => 'Department of Information Technology',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.colleges.show', $college))
        ->assertOk()
        ->assertSee($department->name);

    $this->actingAs($admin)
        ->get(route('admin.departments.show', $department))
        ->assertOk()
        ->assertSee('Back to College')
        ->assertSee('Department Sections')
        ->assertSee('Programs')
        ->assertSee('Subjects');

    $this->actingAs($admin)
        ->get(route('admin.departments.subjects.create', $department))
        ->assertOk()
        ->assertSee('Back to Department');
});

test('department tabs limit row actions and defer program and subject actions to their detail pages', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Electrical Engineering',
        'abbreviation' => 'BSEE',
        'description' => 'Electrical engineering program',
    ]);
    $major = $course->majors()->create([
        'name' => 'Power Systems',
        'description' => 'Major in power systems',
    ]);
    $prerequisite = Subject::factory()->for($department)->create([
        'subject_code' => 'EEE110',
    ]);
    $corequisite = Subject::factory()->for($department)->create([
        'subject_code' => 'EEE120',
    ]);
    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'EEE130',
        'subject_title' => 'Electrical Circuit Theory 1',
        'credit_units' => 4,
        'description' => 'Core circuits subject',
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $subject->id,
        'requisite_subject_id' => $prerequisite->id,
        'type' => 'prerequisite',
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $subject->id,
        'requisite_subject_id' => $corequisite->id,
        'type' => 'corequisite',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.departments.show', ['department' => $department, 'section' => 'programs']))
        ->assertOk()
        ->assertSee('BSEE')
        ->assertSee('Bachelor of Science in Electrical Engineering')
        ->assertDontSee('Electrical engineering program')
        ->assertDontSee(route('admin.courses.edit', $course))
        ->assertDontSee('Delete');

    $this->actingAs($admin)
        ->get(route('admin.departments.show', ['department' => $department, 'section' => 'subjects']))
        ->assertOk()
        ->assertSee('EEE130')
        ->assertSee('Electrical Circuit Theory 1')
        ->assertSee('EEE110')
        ->assertSee('EEE120')
        ->assertDontSee('Core circuits subject')
        ->assertDontSee(route('admin.subjects.edit', $subject))
        ->assertDontSee('Delete');

    $this->actingAs($admin)
        ->get(route('admin.courses.show', $course))
        ->assertOk()
        ->assertSee('Electrical engineering program')
        ->assertSee('Power Systems')
        ->assertSee(route('admin.courses.edit', $course))
        ->assertSee(route('admin.majors.edit', $major));

    $this->actingAs($admin)
        ->get(route('admin.subjects.show', $subject))
        ->assertOk()
        ->assertSee('Core circuits subject')
        ->assertSee(route('admin.subjects.edit', $subject))
        ->assertSee(route('admin.subjects.requisites.index', $subject));
});

test('administrators can bind a subject as an elective option for a course or major from the subject view', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = $course->majors()->create([
        'name' => 'Database Systems',
        'description' => 'Major in database systems',
    ]);
    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITD104',
        'subject_title' => 'Database Security, Administration, & Management',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.subjects.show', $subject))
        ->assertOk()
        ->assertSee('Elective Scope')
        ->assertSee('BSIT')
        ->assertSee('Database Systems');

    $this->actingAs($admin)->put(route('admin.subjects.elective-scopes.update', $subject), [
        'is_elective' => 'on',
        'elective_scope_categories' => [
            $course->id.':'.$major->id => 'technical-elective',
        ],
    ])->assertRedirect(route('admin.subjects.show', $subject));

    $this->assertDatabaseHas('subject_elective_scopes', [
        'subject_id' => $subject->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'category' => 'technical-elective',
    ]);
});

test('non administrators can not access the apas manager hierarchy', function () {
    $student = User::factory()->create();
    $college = College::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.colleges.show', $college))
        ->assertForbidden();
});
