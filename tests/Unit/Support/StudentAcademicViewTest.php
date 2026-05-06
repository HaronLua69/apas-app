<?php

use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Support\StudentAcademicView;

test('legacy enrollments without a status are normalized safely', function () {
    $service = new StudentAcademicView();

    $subject = new Subject([
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
        'credit_units' => 3,
    ]);

    $enrollment = new StudentSubjectEnrollment([
        'grade' => '1.50',
    ]);

    $enrollment->setRelation('subject', $subject);

    $programOfStudyStatus = \Closure::bind(
        fn (?StudentSubjectEnrollment $enrollment): string => $this->programOfStudyStatus($enrollment),
        $service,
        StudentAcademicView::class,
    );

    expect($programOfStudyStatus($enrollment))->toBe('confirmed');
    expect($service->isPassingEnrollment($enrollment))->toBeTrue();
});

test('gpa treats dropped and lapsed inc grades as 5.00', function () {
    $service = new StudentAcademicView();

    $highGradeSubject = new Subject([
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
        'credit_units' => 3,
    ]);

    $droppedSubject = new Subject([
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
        'credit_units' => 3,
    ]);

    $incSubject = new Subject([
        'grading_system' => 'numerical',
        'counts_toward_gpa' => true,
        'credit_units' => 3,
    ]);

    $highGradeEnrollment = new StudentSubjectEnrollment([
        'school_year' => '2024-2025',
        'term_name' => '1st Semester',
        'grade' => '1.00',
        'status' => 'confirmed',
    ]);
    $highGradeEnrollment->setRelation('subject', $highGradeSubject);

    $droppedEnrollment = new StudentSubjectEnrollment([
        'school_year' => '2024-2025',
        'term_name' => '1st Semester',
        'grade' => 'DRP',
        'status' => 'confirmed',
    ]);
    $droppedEnrollment->setRelation('subject', $droppedSubject);

    $lapsedIncEnrollment = new StudentSubjectEnrollment([
        'school_year' => '2024-2025',
        'term_name' => '1st Semester',
        'grade' => 'INC',
        'status' => 'confirmed',
    ]);
    $lapsedIncEnrollment->setRelation('subject', $incSubject);

    $enrollments = collect([
        $highGradeEnrollment,
        $droppedEnrollment,
        $lapsedIncEnrollment,
    ]);

    expect($service->calculateGpa($enrollments))->toBe(3.66667)
        ->and($service->academicTermGpa($enrollments, '2024-2025', '1st Semester'))->toBe(3.66667);
});