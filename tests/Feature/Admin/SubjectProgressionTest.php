<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can view subject progression graphs for a course', function () {
    $fixture = subjectProgressionFixture();

    $response = $this->actingAs($fixture['admin'])
        ->get(route('admin.subject-progression.index', ['course_id' => $fixture['course']->id]))
        ->assertOk()
        ->assertSee('Subject Progression')
        ->assertSee('Directed edge')
        ->assertSee('Undirected edge')
        ->assertSee('Graduation Goal')
        ->assertSee('CCC101')
        ->assertSee('CCC102')
        ->assertSee('STT071')
        ->assertSee('STT071.1')
        ->assertSee('ITE112')
        ->assertSee('ITE114')
        ->assertSee('MAT104')
        ->assertSee('3 units')
        ->assertSee('stroke-sky-500', false)
        ->assertSee('stroke-rose-500', false)
        ->assertSee('Bachelor of Science in Information Technology')
        ->assertSee('Tracks: Major in Database Systems | Major in Network Systems')
        ->assertSee('Major in Database Systems')
        ->assertSee('Major in Network Systems')
        ->assertDontSee('Computer Programming 1')
        ->assertDontSee('Network Fundamentals');

    expect(substr_count($response->getContent(), 'Graduation Goal'))->toBe(2);
});

test('major filters narrow subject progression branches', function () {
    $fixture = subjectProgressionFixture();

    $this->actingAs($fixture['admin'])
        ->get(route('admin.subject-progression.index', [
            'course_id' => $fixture['course']->id,
            'major_id' => $fixture['networkMajor']->id,
        ]))
        ->assertOk()
        ->assertSee('Graduation Goal')
        ->assertSee('Bachelor of Science in Information Technology')
        ->assertSee('Major in Network Systems')
        ->assertSee('ITE114')
        ->assertDontSee('ITE112')
        ->assertSee('MAT104')
        ->assertSee('Network Systems')
        ->assertSee('stroke-rose-500', false);
});

test('non administrators can not access the subject progression module', function () {
    $fixture = subjectProgressionFixture();
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.subject-progression.index', ['course_id' => $fixture['course']->id]))
        ->assertForbidden();
});

function subjectProgressionFixture(): array
{
    $admin = User::factory()->administrator()->create();
    $computingCollege = College::factory()->create([
        'name' => 'College of Computing Studies',
        'abbreviation' => 'CCS',
    ]);
    $scienceCollege = College::factory()->create([
        'name' => 'College of Science',
        'abbreviation' => 'COS',
    ]);
    $itDepartment = Department::factory()->for($computingCollege)->create([
        'name' => 'Information Technology',
        'abbreviation' => 'IT',
    ]);
    $mathDepartment = Department::factory()->for($scienceCollege)->create([
        'name' => 'Mathematics',
        'abbreviation' => 'MATH',
    ]);
    $course = Course::factory()->for($itDepartment)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $databaseMajor = Major::factory()->for($course)->create([
        'name' => 'Database Systems',
    ]);
    $networkMajor = Major::factory()->for($course)->create([
        'name' => 'Network Systems',
    ]);

    $ccc101 = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'CCC101',
        'subject_title' => 'Computer Programming 1',
        'credit_units' => 3,
    ]);
    $ccc102 = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'CCC102',
        'subject_title' => 'Computer Programming 2',
        'credit_units' => 3,
    ]);
    $stt071 = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'STT071',
        'subject_title' => 'Statistics',
        'credit_units' => 3,
    ]);
    $stt071Lab = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'STT071.1',
        'subject_title' => 'Statistics Laboratory',
        'credit_units' => 1,
    ]);
    $ite112 = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'ITE112',
        'subject_title' => 'Database Fundamentals',
        'credit_units' => 3,
    ]);
    $ite114 = Subject::factory()->for($itDepartment)->create([
        'subject_code' => 'ITE114',
        'subject_title' => 'Network Fundamentals',
        'credit_units' => 3,
    ]);
    $mat104 = Subject::factory()->for($mathDepartment)->create([
        'subject_code' => 'MAT104',
        'subject_title' => 'College Algebra',
        'credit_units' => 3,
    ]);

    $ccc102->requisites()->create([
        'requisite_subject_id' => $ccc101->id,
        'type' => 'prerequisite',
    ]);

    $stt071->requisites()->create([
        'requisite_subject_id' => $ccc102->id,
        'type' => 'prerequisite',
    ]);

    $stt071Lab->requisites()->create([
        'requisite_subject_id' => $stt071->id,
        'type' => 'corequisite',
    ]);

    $ite112->requisites()->create([
        'requisite_subject_id' => $ccc101->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $databaseMajor->id,
    ]);

    $ite114->requisites()->create([
        'requisite_subject_id' => $ccc101->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $networkMajor->id,
    ]);

    $ite112->requisites()->create([
        'requisite_subject_id' => $mat104->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $databaseMajor->id,
    ]);

    $ite114->requisites()->create([
        'requisite_subject_id' => $mat104->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $networkMajor->id,
    ]);

    return [
        'admin' => $admin,
        'course' => $course,
        'databaseMajor' => $databaseMajor,
        'networkMajor' => $networkMajor,
    ];
}