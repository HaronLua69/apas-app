<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\EvaluationTemplate;
use App\Models\Major;
use App\Models\Prospectus;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\SubjectRequisite;
use App\Models\User;
use App\Support\StudentAcademicView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('students can view their dashboard, program of study, prospectus, and evaluation', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $completedSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT101',
        'subject_title' => 'Introduction to Computing',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $pendingSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT102',
        'subject_title' => 'Computer Programming 1',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $pendingSubject->id,
        'requisite_subject_id' => $completedSubject->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $major->id,
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 1,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 6,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $completedSubject->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $pendingSubject->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $evaluationTemplate = EvaluationTemplate::query()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Evaluation',
        'description' => 'Evaluation summary',
    ]);

    $classificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'Major Core',
        'description' => 'Core subjects',
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('evaluation_template_classification_subject')->insert([
        [
            'evaluation_template_classification_id' => $classificationId,
            'subject_id' => $completedSubject->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'evaluation_template_classification_id' => $classificationId,
            'subject_id' => $pendingSubject->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $student = User::factory()->create([
        'id_number' => '2022-3741',
    ]);

    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 1,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $completedSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'grade' => '1.25',
        'recorded_by_user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Academic Dashboard')
        ->assertSee('Program of Study')
        ->assertSee('Prospectus')
        ->assertSee('Evaluation')
        ->assertSee('Earned Units')
        ->assertSee('1.25');

    $this->actingAs($student)
        ->get(route('student.subject-progression'))
        ->assertOk()
        ->assertSee('Subject Progression')
        ->assertSee('Bachelor of Science in Information Technology')
        ->assertSee('Major in Software Engineering')
        ->assertSee('IT101')
        ->assertSee('IT102')
        ->assertSee('1.25')
        ->assertSee('3 units')
        ->assertDontSee('Introduction to Computing')
        ->assertDontSee('Computer Programming 1');

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('Academic Year 2022-2023')
        ->assertSee('Year Level: 1')
        ->assertSee('IT101')
        ->assertSee('IT102')
        ->assertSee('1.25');

    $this->actingAs($student)
        ->get(route('student.prospectus'))
        ->assertOk()
        ->assertSee('BSIT Software Engineering Prospectus')
        ->assertSee('IT101')
        ->assertSee('Completed');

    $this->actingAs($student)
        ->get(route('student.evaluation'))
        ->assertOk()
        ->assertSee('BSIT Software Engineering Evaluation')
        ->assertSee('Major Core')
        ->assertSee('Completed Subjects')
        ->assertSee('Summary of Units')
        ->assertSee('Required')
        ->assertSee('Earned')
        ->assertSee('6')
        ->assertSee('3');
});

test('non students can not access student view routes', function () {
    $adviser = User::factory()->adviser()->create();

    $this->actingAs($adviser)
        ->get(route('student.dashboard'))
        ->assertForbidden();

    $this->actingAs($adviser)
        ->get(route('student.subject-progression'))
        ->assertForbidden();
});

test('graduating students see their graduating badge across academic views', function () {
    $student = User::factory()->create();
    StudentProfile::factory()->for($student)->create([
        'is_graduating' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Graduating');

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('Graduating');

    $this->actingAs($student)
        ->get(route('student.prospectus'))
        ->assertOk()
        ->assertSee('Graduating');

    $this->actingAs($student)
        ->get(route('student.evaluation'))
        ->assertOk()
        ->assertSee('Graduating');

    $this->actingAs($student)
        ->get(route('student.subject-progression'))
        ->assertOk()
        ->assertSee('Graduating');
});

test('students fall back to inactive prospectus data and same term elective enrollments when scope data is missing', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Network Systems',
    ]);

    $requiredSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE131',
        'subject_title' => 'Computer Architecture and Operating Systems',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $languageSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'NIH001',
        'subject_title' => 'Basic Nihonggo',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Networks Prospectus',
        'is_active' => false,
    ]);

    $prospectusTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 2,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 6,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        'prospectus_term_id' => $prospectusTermId,
        'subject_id' => $requiredSubject->id,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_electives')->insert([
        'prospectus_term_id' => $prospectusTermId,
        'name' => 'Foreign Language Elective',
        'category' => 'language-elective',
        'units' => 3,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2023-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $requiredSubject->id,
        'school_year' => '2023-2024',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '2.00',
        'recorded_by_user_id' => $student->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $languageSubject->id,
        'school_year' => '2023-2024',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'grade' => '1.00',
        'recorded_by_user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.prospectus'))
        ->assertOk()
        ->assertSee('BSIT Networks Prospectus')
        ->assertSee('Elective 1')
        ->assertSee('NIH001')
        ->assertSee('Completed');
});

test('submitted but unconfirmed grades stay out of prospectus evaluation and gpa views', function () {
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
        'subject_code' => 'IT201',
        'subject_title' => 'Data Structures',
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
        'description' => 'Evaluation summary',
    ]);

    $classificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'Major Core',
        'description' => 'Core subjects',
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

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 2,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2023-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject->id,
        'school_year' => '2024-2025',
        'year_level' => 2,
        'term_name' => '1st Semester',
        'attempt_number' => 1,
        'status' => 'grade_submitted',
        'grade' => null,
        'submitted_grade' => '1.50',
        'submitted_at' => now(),
        'recorded_by_user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('IT201')
        ->assertDontSeeText('1.50');

    $this->actingAs($student)
        ->get(route('student.prospectus'))
        ->assertOk()
        ->assertSee('IT201')
        ->assertDontSee('Completed');

    $this->actingAs($student)
        ->get(route('student.evaluation'))
        ->assertOk()
        ->assertSee('0/1')
        ->assertDontSeeText('1.50');
});

test('program of study only includes retake history for retaken subjects', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $passedFirstTakeSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT101',
        'subject_title' => 'Introduction to Computing',
        'credit_units' => 3,
        'grading_system' => 'numerical',
    ]);

    $retakenSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT102',
        'subject_title' => 'Computer Programming 1',
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
        'year_level' => 1,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 6,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $passedFirstTakeSubject->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'prospectus_term_id' => $prospectusTermId,
            'subject_id' => $retakenSubject->id,
            'display_order' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 1,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $passedFirstTakeSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'attempt_number' => 1,
        'status' => 'confirmed',
        'grade' => '1.25',
        'confirmed_at' => now(),
        'recorded_by_user_id' => $student->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $retakenSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'attempt_number' => 1,
        'status' => 'confirmed',
        'grade' => '5.00',
        'confirmed_at' => now(),
        'recorded_by_user_id' => $student->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $retakenSubject->id,
        'school_year' => '2023-2024',
        'year_level' => 2,
        'term_name' => '2nd Semester',
        'attempt_number' => 2,
        'status' => 'confirmed',
        'grade' => '2.00',
        'confirmed_at' => now(),
        'recorded_by_user_id' => $student->id,
    ]);

    $academicView = app(StudentAcademicView::class)->build($studentProfile->fresh());
    $rows = $academicView['programOfStudy']
        ->flatMap(fn (array $schoolYearGroup) => collect($schoolYearGroup['terms']))
        ->flatMap(fn (array $termData) => collect($termData['rows']))
        ->keyBy('code');

    expect($rows['IT101']['isRetaken'])->toBeFalse();
    expect($rows['IT101']['attemptHistory'])->toBe([]);
    expect($rows['IT102']['isRetaken'])->toBeTrue();
    expect($rows['IT102']['attemptNumber'])->toBe(2);
    expect($rows['IT102']['attemptHistory'])->toHaveCount(2);
    expect(collect($rows['IT102']['attemptHistory'])->pluck('result')->all())->toBe(['5.00', '2.00']);

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('Retake #2')
        ->assertSee('Attempt 1')
        ->assertSee('5.00')
        ->assertSee('2.00');
});

test('student program of study shows GPA summaries and resolved elective requisites', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $numericSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'IT101',
        'subject_title' => 'Introduction to Computing',
        'credit_units' => 3,
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
    ]);

    $electivePrerequisite = Subject::factory()->for($department)->create([
        'subject_code' => 'IT100',
        'subject_title' => 'Computer Fundamentals',
        'credit_units' => 3,
    ]);

    $electiveCorequisite = Subject::factory()->for($department)->create([
        'subject_code' => 'IT100L',
        'subject_title' => 'Computer Fundamentals Laboratory',
        'credit_units' => 1,
    ]);

    $electiveSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITEL201',
        'subject_title' => 'Applied Software Design',
        'credit_units' => 3,
        'grading_system' => 'letter',
        'counts_toward_gpa' => true,
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $electiveSubject->id,
        'requisite_subject_id' => $electivePrerequisite->id,
        'type' => 'prerequisite',
        'course_id' => $course->id,
        'major_id' => $major->id,
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $electiveSubject->id,
        'requisite_subject_id' => $electiveCorequisite->id,
        'type' => 'corequisite',
        'course_id' => $course->id,
        'major_id' => $major->id,
    ]);

    $prospectus = Prospectus::factory()->for($course)->create([
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Prospectus',
        'is_active' => true,
    ]);

    $firstSemesterTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 1,
        'term_name' => '1st Semester',
        'display_order' => 1,
        'total_units' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $secondSemesterTermId = DB::table('prospectus_terms')->insertGetId([
        'prospectus_id' => $prospectus->id,
        'year_level' => 1,
        'term_name' => '2nd Semester',
        'display_order' => 2,
        'total_units' => 3,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('prospectus_term_subject')->insert([
        'prospectus_term_id' => $firstSemesterTermId,
        'subject_id' => $numericSubject->id,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $electiveId = DB::table('prospectus_term_electives')->insertGetId([
        'prospectus_term_id' => $secondSemesterTermId,
        'name' => 'Technical Elective 1',
        'category' => 'technical-elective',
        'units' => 3,
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('subject_elective_scopes')->insert([
        'subject_id' => $electiveSubject->id,
        'course_id' => $course->id,
        'major_id' => $major->id,
        'category' => 'technical-elective',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $student = User::factory()->create([
        'id_number' => '2022-3741',
    ]);

    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 1,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $numericSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'grade' => '1.25',
        'recorded_by_user_id' => $student->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $electiveSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '2nd Semester',
        'grade' => 'A',
        'recorded_by_user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('Cumulative GPA: 1.12500')
        ->assertSee('Semester GPA: 1.25000')
        ->assertSee('Semester GPA: 1.00000')
        ->assertSee('IT100')
        ->assertSee('IT100L');

    $this->actingAs($student)
        ->get(route('student.prospectus'))
        ->assertOk()
        ->assertSee('ITEL201')
        ->assertSee('IT100')
        ->assertSee('IT100L')
        ->assertDontSee('As the course requires');
});

test('student program of study fills lapsed inc rows red and counts them as 5.00 in gpa', function () {
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
        'subject_code' => 'IT201',
        'subject_title' => 'Data Structures',
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
        'year_level' => 1,
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

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create([
        'year_level' => 1,
    ]);

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2024-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $subject->id,
        'school_year' => '2024-2025',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'status' => 'confirmed',
        'grade' => 'INC',
        'recorded_by_user_id' => $student->id,
    ]);

    $this->actingAs($student)
        ->get(route('student.program-of-study'))
        ->assertOk()
        ->assertSee('INC lapsed')
        ->assertSee('Cumulative GPA: 5.00000')
        ->assertSee('Semester GPA: 5.00000')
        ->assertSeeInOrder(['bg-rose-50/80 dark:bg-rose-500/10', 'IT201'], false);
});

test('evaluation summary places nstp last and excludes it from totals', function () {
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Software Engineering',
    ]);

    $generalEducationSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'GE101',
        'subject_title' => 'Understanding the Self',
        'credit_units' => 3,
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
    ]);

    $nstpSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'NSTP101',
        'subject_title' => 'National Service Training Program 1',
        'credit_units' => 3,
        'grading_system' => 'letter',
        'counts_toward_gpa' => false,
    ]);

    $evaluationTemplate = EvaluationTemplate::query()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'title' => 'BSIT Software Engineering Evaluation',
        'description' => 'Evaluation summary',
    ]);

    $generalEducationClassificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'General Education Courses',
        'description' => 'General education requirements',
        'display_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $nstpClassificationId = DB::table('evaluation_template_classifications')->insertGetId([
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'NSTP',
        'description' => 'National Service Training Program',
        'display_order' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('evaluation_template_classification_subject')->insert([
        [
            'evaluation_template_classification_id' => $generalEducationClassificationId,
            'subject_id' => $generalEducationSubject->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'evaluation_template_classification_id' => $nstpClassificationId,
            'subject_id' => $nstpSubject->id,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $student = User::factory()->create();
    $studentProfile = StudentProfile::factory()->for($student)->create();

    $studentProfile->admissions()->create([
        'course_id' => $course->id,
        'major_id' => $major->id,
        'admission_date' => '2022-08-01',
        'is_active' => true,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $generalEducationSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'grade' => '1.50',
        'recorded_by_user_id' => $student->id,
    ]);

    StudentSubjectEnrollment::query()->create([
        'student_profile_id' => $studentProfile->id,
        'subject_id' => $nstpSubject->id,
        'school_year' => '2022-2023',
        'year_level' => 1,
        'term_name' => '1st Semester',
        'grade' => 'A',
        'recorded_by_user_id' => $student->id,
    ]);

    $response = $this->actingAs($student)
        ->get(route('student.evaluation'));

    $response
        ->assertOk()
        ->assertSee('Summary of Units')
        ->assertSee('General Education Courses')
        ->assertSee('NSTP')
        ->assertSee('(3)')
        ->assertSee('Total')
        ->assertSee('3');

    $content = $response->getContent();

    expect($content)->not->toBeFalse();
    expect(strpos($content, 'General Education Courses'))->toBeLessThan(strpos($content, 'NSTP'));
    expect(strpos($content, 'NSTP'))->toBeLessThan(strpos($content, 'Total'));
});