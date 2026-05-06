<?php

use App\Enums\UserRole;
use App\Http\Controllers\Adviser\AssignedStudentController;
use App\Http\Controllers\Adviser\ApapReportController;
use App\Http\Controllers\Admin\AdviserController;
use App\Http\Controllers\Admin\AdviserAssignmentController;
use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\CollegeController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EvaluationTemplateClassificationController;
use App\Http\Controllers\Admin\EvaluationTemplateController;
use App\Http\Controllers\Admin\MajorController;
use App\Http\Controllers\Admin\ProspectusController;
use App\Http\Controllers\Admin\ProspectusTermController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SubjectRequisiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\StudentViewController;
use App\Http\Controllers\Admin\SubjectProgressionController;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        return redirect()->route($request->user()->dashboardRouteName());
    })->name('dashboard');

    Route::post('role-switch', function (Request $request) {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $role = UserRole::tryFrom($validated['role']);

        abort_unless($role !== null && $request->user()->ownsRole($role), 403);

        $request->session()->put('active_role', $role->value);

        return redirect()->route($request->user()->dashboardRouteName());
    })->name('role.switch');

    Route::prefix('admin')->name('admin.')->middleware('role:administrator')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::resource('academic-terms', AcademicTermController::class)->except('show');
        Route::resource('colleges', CollegeController::class);
        Route::resource('colleges.departments', DepartmentController::class)->shallow()->except('index');
        Route::resource('departments.courses', CourseController::class)->shallow()->except(['index']);
        Route::resource('courses.majors', MajorController::class)->shallow()->except(['index', 'show']);
        Route::resource('departments.subjects', SubjectController::class)->shallow()->except(['index']);
        Route::put('subjects/{subject}/elective-scopes', [SubjectController::class, 'updateElectiveScopes'])->name('subjects.elective-scopes.update');
        Route::get('subjects/{subject}/requisites', [SubjectRequisiteController::class, 'index'])->name('subjects.requisites.index');
        Route::post('subjects/{subject}/requisites', [SubjectRequisiteController::class, 'store'])->name('subjects.requisites.store');
        Route::delete('subject-requisites/{subjectRequisite}', [SubjectRequisiteController::class, 'destroy'])->name('subject-requisites.destroy');
        Route::get('subject-progression', [SubjectProgressionController::class, 'index'])->name('subject-progression.index');
        Route::resource('courses.prospectuses', ProspectusController::class)->shallow()->except('index');
        Route::get('prospectuses/{prospectus}/duplicate', [ProspectusController::class, 'duplicate'])->name('prospectuses.duplicate');
        Route::post('prospectuses/{prospectus}/duplicate', [ProspectusController::class, 'storeDuplicate'])->name('prospectuses.duplicate.store');
        Route::resource('prospectuses.terms', ProspectusTermController::class)
            ->parameters(['terms' => 'prospectusTerm'])
            ->shallow()
            ->except(['index', 'show']);
        Route::resource('courses.evaluation-templates', EvaluationTemplateController::class)->shallow()->except('index');
        Route::resource('evaluation-templates.classifications', EvaluationTemplateClassificationController::class)->shallow()->except(['index', 'show']);
        Route::resource('students', StudentController::class)->except('show');
        Route::resource('advisers', AdviserController::class)->except('show');
        Route::get('advisers/{adviser}/assignments', [AdviserAssignmentController::class, 'index'])->name('advisers.assignments.index');
        Route::post('advisers/{adviser}/assignments', [AdviserAssignmentController::class, 'store'])->name('advisers.assignments.store');
        Route::delete('adviser-assignments/{adviserAssignment}', [AdviserAssignmentController::class, 'destroy'])->name('adviser-assignments.destroy');
    });

    Route::prefix('adviser')->name('adviser.')->middleware('role:adviser')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::get('reports/apap/export', [ApapReportController::class, 'export'])->name('reports.apap.export');
        Route::get('reports/apap', ApapReportController::class)->name('reports.apap');
        Route::get('students', [AssignedStudentController::class, 'index'])->name('students.index');
        Route::get('students/{studentProfile}', [AssignedStudentController::class, 'show'])->name('students.show');
        Route::post('students/{studentProfile}/graduating', [AssignedStudentController::class, 'markGraduating'])->name('students.graduating.store');
        Route::post('students/{studentProfile}/current-term-enrollments', [AssignedStudentController::class, 'storeCurrentTermEnrollment'])->name('students.current-term-enrollments.store');
        Route::post('students/{studentProfile}/current-term-enrollments/{studentSubjectEnrollment}/submit-grade', [AssignedStudentController::class, 'submitCurrentTermGrade'])->name('students.current-term-enrollments.submit-grade');
        Route::post('students/{studentProfile}/current-term-enrollments/{studentSubjectEnrollment}/confirm-grade', [AssignedStudentController::class, 'confirmCurrentTermGrade'])->name('students.current-term-enrollments.confirm-grade');
        Route::post('students/{studentProfile}/enrollments', [AssignedStudentController::class, 'storeEnrollment'])->name('students.enrollments.store');
    });

    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('dashboard', [StudentViewController::class, 'dashboard'])->name('dashboard');
        Route::get('subject-progression', [StudentViewController::class, 'subjectProgression'])->name('subject-progression');
        Route::get('program-of-study', [StudentViewController::class, 'programOfStudy'])->name('program-of-study');
        Route::get('prospectus', [StudentViewController::class, 'prospectus'])->name('prospectus');
        Route::get('evaluation', [StudentViewController::class, 'evaluation'])->name('evaluation');
    });
});

require __DIR__.'/settings.php';
