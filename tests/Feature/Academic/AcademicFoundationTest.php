<?php

use App\Enums\UserRole;
use App\Models\AdviserProfile;
use App\Models\AcademicTerm;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\Prospectus;
use App\Models\StudentAdviserBinding;
use App\Models\StudentAdmission;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('academic foundation tables support the core apas relationships', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();
    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'EEE130',
        'subject_title' => 'Electrical Circuit Theory 1',
        'subject_types' => ['Lecture', 'Laboratory'],
        'credit_units' => 4,
        'grading_system' => 'numerical',
    ]);
    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSEE Prospectus',
    ]);
    $studentProfile = StudentProfile::factory()->create([
        'year_level' => 3,
    ]);
    $adviserProfile = AdviserProfile::factory()->for($department)->create();

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 3,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 4,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        'prospectus_term_id' => $prospectusTermId,
        'subject_id' => $subject->id,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $evaluationTemplateId = DB::table('evaluation_templates')->insertGetId([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'title' => 'BSEE Evaluation',
        'description' => 'Default evaluation template',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $classificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplateId,
        'name' => 'Mathematics',
        'description' => 'Mathematics subjects',
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('evaluation_template_classification_subject')->insert([
        'evaluation_template_classification_id' => $classificationId,
        'subject_id' => $subject->id,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('student_admissions')->insert([
        'student_profile_id' => $studentProfile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2026-06-01',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('adviser_assignments')->insert([
        'adviser_profile_id' => $adviserProfile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('student_subject_enrollments')->insert([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject->id,
        'school_year' => '2026-2027',
        'year_level' => 3,
        'term_name' => '1st Semester',
        'grade' => '1.25',
        'recorded_by_user_id' => $adviserProfile->user_id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect($college->departments()->count())->toBe(1)
        ->and($department->courses()->count())->toBe(1)
        ->and($course->majors()->count())->toBe(1)
        ->and($department->subjects()->count())->toBe(1)
        ->and(DB::table('student_subject_enrollments')->count())->toBe(1)
        ->and(DB::table('evaluation_template_classifications')->count())->toBe(1);
});

test('database seeder creates the default administrator account', function () {
    $this->seed();

    $administrator = User::query()->where('username', 'HaronLua69')->first();
    $student = User::query()->where('id_number', '2022-3741')->first();
    $binding = StudentAdviserBinding::query()
        ->with('studentProfile.user', 'adviserAssignment.adviserProfile.user')
        ->first();

    expect($administrator)->not()->toBeNull()
        ->and($administrator->id_number)->toBe('2018-192')
        ->and($administrator->role)->toBe(UserRole::Administrator)
        ->and($administrator->secondary_role)->toBe(UserRole::Adviser)
        ->and($administrator->last_name)->toBe('Lua')
        ->and($administrator->adviserProfile)->not()->toBeNull()
        ->and($student)->not()->toBeNull()
        ->and($student->last_name)->toBe('Augosto')
        ->and($student->role)->toBe(UserRole::Student)
        ->and($student->studentProfile)->not()->toBeNull()
        ->and($binding)->not()->toBeNull()
        ->and($binding->studentProfile->user->id_number)->toBe('2022-3741')
        ->and($binding->adviserAssignment->adviserProfile->user->username)->toBe('HaronLua69')
        ->and(Schema::hasTable('student_profiles'))->toBeTrue()
        ->and(Schema::hasTable('prospectuses'))->toBeTrue();
});

test('database seeder reuses the existing wilson augosto record instead of creating a duplicate student', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create([
        'abbreviation' => 'DIT',
    ]);
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Network Systems',
    ]);

    $student = User::factory()->create([
        'id_number' => '2022-3741',
        'username' => 'wilson.augosto',
        'name' => 'Wilson B. Augosto',
        'first_name' => 'Wilson',
        'middle_name' => 'B.',
        'last_name' => 'Augosto',
        'email' => 'wilson.augosto@g.msuiit.edu.ph',
        'role' => UserRole::Student,
        'secondary_role' => null,
    ]);

    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
    ]);

    StudentAdmission::query()->create([
        'student_profile_id' => $studentProfile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-06-01',
        'is_active' => true,
    ]);

    $legacyStudent = User::factory()->create([
        'id_number' => '2022-001',
        'username' => 'WilsonStudent',
        'name' => 'Wilson Ramos Santos',
        'first_name' => 'Wilson',
        'middle_name' => 'Ramos',
        'last_name' => 'Santos',
        'email' => 'wilson.santos@g.msuiit.edu.ph',
        'role' => UserRole::Student,
        'secondary_role' => null,
    ]);

    $legacyStudentProfile = StudentProfile::factory()->for($legacyStudent)->create([
        'year_level' => 2,
    ]);

    $this->seed();

    $binding = StudentAdviserBinding::query()
        ->with('studentProfile.user', 'adviserAssignment')
        ->first();

    expect(User::query()->where('id_number', '2022-3741')->count())->toBe(1)
        ->and(User::query()->where('username', 'WilsonStudent')->exists())->toBeFalse()
        ->and(StudentProfile::query()->whereKey($legacyStudentProfile->id)->exists())->toBeFalse()
        ->and($binding)->not()->toBeNull()
        ->and($binding->student_profile_id)->toBe($studentProfile->id)
        ->and($binding->studentProfile->user->id)->toBe($student->id)
        ->and($binding->adviserAssignment->course_id)->toBe($course->id)
        ->and($binding->adviserAssignment->major_id)->toBe($major->id)
        ->and($binding->adviserAssignment->year_level)->toBe(4);
});

test('student subject enrollments support attempt lifecycle metadata', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();
    $student = User::factory()->create([
        'role' => UserRole::Student,
        'secondary_role' => null,
    ]);
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
    ]);
    StudentAdmission::query()->create([
        'student_profile_id' => $studentProfile->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-06-01',
        'is_active' => true,
    ]);
    $subject = Subject::factory()->for($department)->create();
    $term = AcademicTerm::factory()->create();

    $enrollment = StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject->id,
        'academic_term_id' => $term->id,
        'school_year' => $term->academic_year_start.'-'.$term->academicYearEnd(),
        'year_level' => 4,
        'term_name' => $term->term_name,
        'attempt_number' => 2,
        'status' => 'grade_submitted',
        'grade' => null,
        'submitted_grade' => '1.50',
        'submitted_at' => now(),
    ]);

    expect(Schema::hasColumn('student_subject_enrollments', 'academic_term_id'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_enrollments', 'attempt_number'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_enrollments', 'status'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_enrollments', 'submitted_grade'))->toBeTrue()
        ->and(Schema::hasColumn('student_subject_enrollments', 'completion_grade'))->toBeTrue()
        ->and($enrollment->academicTerm?->is($term))->toBeTrue()
        ->and($enrollment->attempt_number)->toBe(2)
        ->and($enrollment->status)->toBe('grade_submitted')
        ->and($enrollment->submitted_grade)->toBe('1.50')
        ->and($enrollment->confirmed_at)->toBeNull();
});
