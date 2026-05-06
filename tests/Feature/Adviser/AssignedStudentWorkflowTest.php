<?php

use App\Models\AcademicTerm;
use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\EvaluationTemplate;
use App\Models\Major;
use App\Models\Prospectus;
use App\Models\StudentAdviserBinding;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('advisers can view only their assigned students and open the evaluation view', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);
    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT301',
        'subject_title' => 'Software Architecture',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 2,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 3,
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

    $evaluationTemplate = EvaluationTemplate::query()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Evaluation',
        'description' => 'Advising checklist',
    ]);

    $classificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'Major Core',
        'description' => 'Core major subjects',
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

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    $assignedStudent = User::factory()->create();
    $assignedProfile = StudentProfile::factory()->for($assignedStudent)->create([
        'year_level' => 2,
    ]);
    $assignedProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2026-06-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $assignedProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    $otherStudent = User::factory()->create();
    $otherProfile = StudentProfile::factory()->for($otherStudent)->create([
        'year_level' => 1,
    ]);
    $otherProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2026-06-01',
        'is_active' => true,
    ]);

    $academicTerm = AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '2nd Semester',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->addWeek()->toDateString(),
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.index'))
        ->assertOk()
        ->assertSee($academicTerm->fullLabel())
        ->assertSee($assignedStudent->adviserDisplayName())
        ->assertDontSee($otherStudent->adviserDisplayName());

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $assignedProfile))
        ->assertOk()
        ->assertSee('Program of Study')
        ->assertSee('Evaluation')
        ->assertSee('Subject Progression')
        ->assertSee('No enrolled or taken subjects are recorded on this student ledger yet.')
        ->assertDontSee('Software Architecture')
        ->assertSee('Cumulative GPA')
        ->assertDontSee('Academic Record Entry');

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', ['studentProfile' => $assignedProfile, 'tab' => 'evaluation']))
        ->assertOk()
        ->assertSee('BSIT Software Engineering Evaluation')
        ->assertSee('Major Core')
        ->assertSee('IT301');

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', ['studentProfile' => $assignedProfile, 'tab' => 'subject-progression']))
        ->assertOk()
        ->assertSee('Subject Progression')
        ->assertSee('Node Details');

    $this->actingAs($adviser)->post(route('adviser.students.enrollments.store', $assignedProfile), [
        'subject_id' => $subject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '1.25',
    ])->assertRedirect(route('adviser.students.show', $assignedProfile));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'student_profile_id' => $assignedProfile->id,
        'subject_id' => $subject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '1.25',
        'recorded_by_user_id' => $adviser->id,
    ]);

    $enrollment = StudentSubjectEnrollment::query()->firstOrFail();

    expect($enrollment->grade)->toBe('1.25');

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $otherProfile))
        ->assertNotFound();
});

test('non advisers can not access adviser student workflow routes', function () {
    $admin = User::factory()->administrator()->create();
    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create();

    $this->actingAs($admin)
        ->get(route('adviser.students.index'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('adviser.students.graduating.store', $studentProfile), [
            'tab' => 'program-of-study',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('adviser.students.current-term-enrollments.store', $studentProfile), [
            'subject_id' => 999,
        ])
        ->assertForbidden();
});

test('advisers can record elective subjects that satisfy scoped prospectus elective placeholders', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $otherCollege = College::factory()->create();
    $otherDepartment = Department::factory()->for($otherCollege)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Database Systems',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Database Systems Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 2,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_electives')->insert([
        'prospectus_term_id' => $prospectusTermId,
        'category' => 'language-elective',
        'name' => 'Foreign Language Elective',
        'units' => 3,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_electives')->insert([
        'prospectus_term_id' => $prospectusTermId,
        'name' => 'Technical Elective 1',
        'category' => 'technical-elective',
        'units' => 3,
        'display_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $languageSubject = Subject::factory()->for($otherDepartment)->create([
        'subject_code' => 'SPAN101',
        'subject_title' => 'Conversational Spanish',
        'subject_types' => ['Lecture'],
        'credit_units' => 3,
        'grading_system' => 'letter',
    ]);

    $electiveSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITD104',
        'subject_title' => 'Database Security, Administration, & Management',
        'subject_types' => ['Lecture', 'Laboratory'],
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    DB::table('subject_elective_scopes')->insert([
        'subject_id' => $electiveSubject->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'category' => 'technical-elective',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('subject_elective_scopes')->insert([
        'subject_id' => $languageSubject->id,
        'course_id' => $course->id,
        'major_id' => null,
        'category' => 'language-elective',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2026-06-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('No enrolled or taken subjects are recorded on this student ledger yet.')
        ->assertDontSee('Foreign Language Elective')
        ->assertDontSee('Technical Elective 1');

    $this->actingAs($adviser)->post(route('adviser.students.enrollments.store', $studentProfile), [
        'subject_id' => $languageSubject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => 'A',
    ])->assertRedirect(route('adviser.students.show', $studentProfile));

    $this->actingAs($adviser)->post(route('adviser.students.enrollments.store', $studentProfile), [
        'subject_id' => $electiveSubject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '1.25',
    ])->assertRedirect(route('adviser.students.show', $studentProfile));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $languageSubject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => 'A',
        'recorded_by_user_id' => $adviser->id,
    ]);

    $this->assertDatabaseHas('student_subject_enrollments', [
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $electiveSubject->id,
        'school_year' => '2026-2027',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '1.25',
        'recorded_by_user_id' => $adviser->id,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Foreign Language Elective')
        ->assertSee('SPAN101')
        ->assertSee('Conversational Spanish')
        ->assertSee('Technical Elective 1')
        ->assertSee('ITD104')
        ->assertSee('Database Security, Administration, & Management');
});

test('advisers can mark an eligible viewed advisee as graduating', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject197 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT197',
        'subject_title' => 'Thesis Writing 1',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $subject198 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT198',
        'subject_title' => 'Thesis Writing 2',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $subject199 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT199',
        'subject_title' => 'Thesis Defense',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 4,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject197->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject198->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject199->id,
            'display_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 4,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
        'is_graduating' => false,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    foreach ([$subject197, $subject198, $subject199] as $subject) {
        StudentSubjectEnrollment::query()->create([
            'student_profile_id' => $studentProfile->id,
            'subject_id' => $subject->id,
            'school_year' => '2025-2026',
            'year_level' => 4,
            'term_name' => '1st Semester',
            'attempt_number' => 1,
            'status' => 'confirmed',
            'grade' => '1.25',
            'confirmed_at' => now(),
            'recorded_by_user_id' => $adviser->id,
        ]);
    }

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Graduation Recommendation')
        ->assertSee('Mark as Graduating')
        ->assertSee('Fourth-year standing')
        ->assertSee('Passed IT198')
        ->assertSee('Passed or currently taking IT197');

    $this->actingAs($adviser)
        ->post(route('adviser.students.graduating.store', $studentProfile), [
            'tab' => 'program-of-study',
        ])
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_profiles', [
        'id' => $studentProfile->id,
        'is_graduating' => true,
    ]);
});

test('advisers can add current-term enrolled subjects for a viewed advisee', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject197 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT197',
        'subject_title' => 'Thesis Writing 1',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $subject198 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT198',
        'subject_title' => 'Thesis Writing 2',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $subject499 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT499',
        'subject_title' => 'Internship',
        'credit_units' => 8,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 4,
        'term_name' => '2nd Semester',
        'display_order' => 1,
        'total_units' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject197->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject198->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject499->id,
            'display_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $activeTerm = AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '2nd Semester',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->addWeek()->toDateString(),
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 4,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
        'is_graduating' => false,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    foreach ([$subject198, $subject499] as $subject) {
        StudentSubjectEnrollment::query()->create([
            'student_profile_id' => $studentProfile->id,
            'subject_id' => $subject->id,
            'school_year' => '2025-2026',
            'year_level' => 4,
            'term_name' => '2nd Semester',
            'attempt_number' => 1,
            'status' => 'confirmed',
            'grade' => '1.25',
            'confirmed_at' => now(),
            'recorded_by_user_id' => $adviser->id,
        ]);
    }

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Add Actual Enrolled Subjects')
        ->assertSee($activeTerm->fullLabel())
        ->assertSee('No extra search-select subjects are available for this active term right now.')
        ->assertSee('Start Current-Term Enrollment')
        ->assertSee('IT197')
        ->assertDontSee('&quot;code&quot;:&quot;IT197&quot;', false);

    $this->actingAs($adviser)
        ->post(route('adviser.students.current-term-enrollments.store', $studentProfile), [
            'subject_id' => $subject197->id,
        ])
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject197->id,
        'academic_term_id' => $activeTerm->id,
        'school_year' => '2025-2026',
        'year_level' => 4,
        'term_name' => '2nd Semester',
        'status' => 'in_progress',
        'grade' => null,
        'recorded_by_user_id' => $adviser->id,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('In Progress')
        ->assertSee('Submit Grade')
        ->assertSee('Mark as Graduating');

    $inProgressEnrollment = StudentSubjectEnrollment::query()
        ->where('student_profile_id', $studentProfile->id)
        ->where('subject_id', $subject197->id)
        ->where('academic_term_id', $activeTerm->id)
        ->firstOrFail();

    $this->actingAs($adviser)
        ->post(route('adviser.students.current-term-enrollments.submit-grade', [
            'studentProfile' => $studentProfile,
            'studentSubjectEnrollment' => $inProgressEnrollment,
        ]), [
            'submitted_grade' => '1.50',
        ])
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'id' => $inProgressEnrollment->id,
        'status' => 'grade_submitted',
        'submitted_grade' => '1.50',
        'grade' => null,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Submitted: 1.50')
        ->assertSee('Revise Submitted Grade')
        ->assertSee('Confirm Grade');

    $this->actingAs($adviser)
        ->post(route('adviser.students.current-term-enrollments.submit-grade', [
            'studentProfile' => $studentProfile,
            'studentSubjectEnrollment' => $inProgressEnrollment->fresh(),
        ]), [
            'submitted_grade' => '1.25',
        ])
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'id' => $inProgressEnrollment->id,
        'status' => 'grade_submitted',
        'submitted_grade' => '1.25',
        'grade' => null,
    ]);

    $this->actingAs($adviser)
        ->post(route('adviser.students.current-term-enrollments.confirm-grade', [
            'studentProfile' => $studentProfile,
            'studentSubjectEnrollment' => $inProgressEnrollment->fresh(),
        ]))
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_subject_enrollments', [
        'id' => $inProgressEnrollment->id,
        'status' => 'confirmed',
        'submitted_grade' => '1.25',
        'grade' => '1.25',
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('1.25')
        ->assertDontSee('Confirm Grade');
});

test('advisers can mark advisees as graduating when the required 197 subject is in the active prospectus term', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject197 = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE197',
        'subject_title' => 'Thesis Writing 1',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $subject198 = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE198',
        'subject_title' => 'Thesis Writing 2',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $subject499 = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE499',
        'subject_title' => 'Internship',
        'credit_units' => 8,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 4,
        'term_name' => '2nd Semester',
        'display_order' => 1,
        'total_units' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject197->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject198->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject499->id,
            'display_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '2nd Semester',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->addWeek()->toDateString(),
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 4,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
        'is_graduating' => false,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    foreach ([$subject198, $subject499] as $subject) {
        StudentSubjectEnrollment::query()->create([
            'student_profile_id' => $studentProfile->id,
            'subject_id' => $subject->id,
            'school_year' => '2025-2026',
            'year_level' => 4,
            'term_name' => '1st Semester',
            'attempt_number' => 1,
            'status' => 'confirmed',
            'grade' => '1.25',
            'confirmed_at' => now(),
            'recorded_by_user_id' => $adviser->id,
        ]);
    }

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Mark as Graduating')
        ->assertSee('Passed or currently taking ITE197')
        ->assertSee('ITE197 is currently in progress.');

    $this->actingAs($adviser)
        ->post(route('adviser.students.graduating.store', $studentProfile), [
            'tab' => 'program-of-study',
        ])
        ->assertRedirect(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']));

    $this->assertDatabaseHas('student_profiles', [
        'id' => $studentProfile->id,
        'is_graduating' => true,
    ]);
});

test('adviser program of study fills failing rows red and uses them in semester gpa', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE210',
        'subject_title' => 'Systems Analysis and Design',
        'credit_units' => 3,
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 2,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 3,
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

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2024-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject->id,
        'school_year' => '2024-2025',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'attempt_number' => 1,
        'status' => 'confirmed',
        'grade' => '5.00',
        'confirmed_at' => now(),
        'recorded_by_user_id' => $adviser->id,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Cumulative GPA: 5.00000')
        ->assertSee('Semester GPA: 5.00000')
        ->assertSeeInOrder(['bg-rose-50/80 dark:bg-rose-500/10', 'ITE210'], false);
});

test('advisers current-term subject picker excludes subjects already recorded for the active term even when academic term id is missing', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject197 = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE197',
        'subject_title' => 'Thesis Writing 1',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $subject198 = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE198',
        'subject_title' => 'Thesis Writing 2',
        'credit_units' => 1,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 4,
        'term_name' => '2nd Semester',
        'display_order' => 1,
        'total_units' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject197->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject198->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    AcademicTerm::factory()->create([
        'academic_year_start' => 2025,
        'term_name' => '2nd Semester',
        'date_from' => now()->subWeek()->toDateString(),
        'date_to' => now()->addWeek()->toDateString(),
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 4,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 4,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject197->id,
        'academic_term_id' => null,
        'school_year' => '2025-2026',
        'year_level' => 4,
        'term_name' => '2nd Semester',
        'attempt_number' => 1,
        'status' => 'in_progress',
        'grade' => null,
        'recorded_by_user_id' => $adviser->id,
    ]);

    $response = $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile));

    $response
        ->assertOk()
        ->assertSee('ITE198')
        ->assertSee('Start Current-Term Enrollment')
        ->assertDontSee('&quot;code&quot;:&quot;ITE197&quot;', false);

    $this->actingAs($adviser)
        ->post(route('adviser.students.current-term-enrollments.store', $studentProfile), [
            'subject_id' => $subject197->id,
        ])
        ->assertSessionHasErrors(['current_term_subject_id']);
});

test('advisers can not mark ineligible advisees as graduating', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $subject197 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT197',
        'subject_title' => 'Thesis Writing 1',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $subject198 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT198',
        'subject_title' => 'Thesis Writing 2',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $subject199 = Subject::factory()->for($department)->create([
        'subject_code' => 'IT199',
        'subject_title' => 'Thesis Defense',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 4,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 9,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject197->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject198->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $subject199->id,
            'display_order' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $adviser = User::factory()->adviser()->create();
    AdviserProfile::factory()->for($adviser)->for($department)->create();
    $assignment = $adviser->adviserProfile->adviserAssignments()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 3,
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 3,
        'is_graduating' => false,
    ]);
    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2023-08-01',
        'is_active' => true,
    ]);
    StudentAdviserBinding::query()->create([
        'student_profile_id' => $studentProfile->id,
        'adviser_assignment_id' => $assignment->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject197->id,
        'school_year' => '2025-2026',
        'year_level' => 3,
        'term_name' => '2nd Semester',
        'attempt_number' => 1,
        'status' => 'confirmed',
        'grade' => '1.50',
        'confirmed_at' => now(),
        'recorded_by_user_id' => $adviser->id,
    ]);

    $this->actingAs($adviser)
        ->get(route('adviser.students.show', $studentProfile))
        ->assertOk()
        ->assertSee('Graduation Recommendation')
        ->assertSee('Not Eligible Yet')
        ->assertSee('Current year level: 3');

    $this->actingAs($adviser)
        ->from(route('adviser.students.show', ['studentProfile' => $studentProfile, 'tab' => 'program-of-study']))
        ->post(route('adviser.students.graduating.store', $studentProfile), [
            'tab' => 'program-of-study',
        ])
        ->assertSessionHasErrors('graduation');

    $this->assertDatabaseHas('student_profiles', [
        'id' => $studentProfile->id,
        'is_graduating' => false,
    ]);
});
