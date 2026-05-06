<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\EvaluationTemplate;
use App\Models\Major;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create evaluation templates and assign subjects to classifications', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Computer Science',
        'abbreviation' => 'BSCS',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Data Science',
    ]);
    $subjectOne = Subject::factory()->for($department)->create([
        'subject_code' => 'CS101',
        'credit_units' => 3,
    ]);
    $subjectTwo = Subject::factory()->for($department)->create([
        'subject_code' => 'CS102',
        'credit_units' => 4,
    ]);

    $this->actingAs($admin)->post(route('admin.courses.evaluation-templates.store', $course), [
        'title' => 'BSCS Data Science Evaluation',
        'major_id' => $major->id,
        'description' => 'Default advising checklist for the major.',
    ])->assertRedirect();

    $evaluationTemplate = EvaluationTemplate::query()
        ->where('title', 'BSCS Data Science Evaluation')
        ->firstOrFail();

    expect($evaluationTemplate->major_id)->toBe($major->id);

    $this->actingAs($admin)->post(route('admin.evaluation-templates.classifications.store', $evaluationTemplate), [
        'name' => 'Core Computing',
        'description' => 'Core departmental subjects',
        'display_order' => 1,
        'subject_ids' => [$subjectOne->id, $subjectTwo->id],
    ])->assertRedirect(route('admin.evaluation-templates.show', $evaluationTemplate));

    $classification = $evaluationTemplate->classifications()->firstOrFail();

    $this->assertDatabaseHas('evaluation_template_classifications', [
        'id' => $classification->id,
        'evaluation_template_id' => $evaluationTemplate->id,
        'name' => 'Core Computing',
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $subjectOne->id,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $subjectTwo->id,
        'display_order' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.evaluation-templates.show', $evaluationTemplate))
        ->assertOk()
        ->assertSee('Core Computing')
        ->assertSee('CS101')
        ->assertSee('CS102');

    $this->actingAs($admin)->put(route('admin.evaluation-templates.update', $evaluationTemplate), [
        'title' => 'Updated BSCS Data Science Evaluation',
        'major_id' => $major->id,
        'description' => 'Updated advising checklist.',
    ])->assertRedirect(route('admin.evaluation-templates.show', $evaluationTemplate));

    $this->assertDatabaseHas('evaluation_templates', [
        'id' => $evaluationTemplate->id,
        'title' => 'Updated BSCS Data Science Evaluation',
    ]);
});

test('administrators can update classification details without losing assigned subjects', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $evaluationTemplate = EvaluationTemplate::query()->create([
        'course_id' => $course->id,
        'title' => 'BSCS Evaluation',
        'description' => 'Original description',
    ]);
    $subjectOne = Subject::factory()->for($department)->create([
        'subject_code' => 'GEC101',
    ]);
    $subjectTwo = Subject::factory()->for($department)->create([
        'subject_code' => 'GEC102',
    ]);

    $classification = $evaluationTemplate->classifications()->create([
        'name' => 'General Education Courses',
        'description' => null,
        'display_order' => 1,
    ]);

    $classification->subjects()->sync([
        $subjectOne->id => ['display_order' => 1],
        $subjectTwo->id => ['display_order' => 2],
    ]);

    $this->actingAs($admin)->put(route('admin.classifications.update', $classification), [
        'name' => 'General Education Courses',
        'description' => 'Updated general education description.',
        'display_order' => 1,
    ])->assertRedirect(route('admin.evaluation-templates.show', $evaluationTemplate));

    $this->assertDatabaseHas('evaluation_template_classifications', [
        'id' => $classification->id,
        'description' => 'Updated general education description.',
    ]);

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $subjectOne->id,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $subjectTwo->id,
        'display_order' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.evaluation-templates.show', $evaluationTemplate))
        ->assertOk()
        ->assertSee('General Education Courses')
        ->assertSee('Updated general education description.')
        ->assertSee('GEC101')
        ->assertSee('GEC102');
});

test('administrators can assign classification subjects from other departments and colleges', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create(['name' => 'College of Computing', 'abbreviation' => 'CCS']);
    $otherCollege = College::factory()->create(['name' => 'College of Arts and Sciences', 'abbreviation' => 'CAS']);
    $department = Department::factory()->for($college)->create(['name' => 'Computer Studies', 'abbreviation' => 'CS']);
    $otherDepartment = Department::factory()->for($otherCollege)->create(['name' => 'General Education', 'abbreviation' => 'GED']);
    $course = Course::factory()->for($department)->create();
    $evaluationTemplate = EvaluationTemplate::query()->create([
        'course_id' => $course->id,
        'title' => 'BSCS Evaluation',
        'description' => 'Evaluation template',
    ]);
    $departmentSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'CS101',
    ]);
    $generalEducationSubject = Subject::factory()->for($otherDepartment)->create([
        'subject_code' => 'GEC101',
        'subject_title' => 'Understanding the Self',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.evaluation-templates.classifications.create', $evaluationTemplate))
        ->assertOk()
        ->assertSee('Search by subject code or subject title')
        ->assertSee('including those offered by other colleges and departments')
        ->assertSee('GEC101');

    $this->actingAs($admin)->post(route('admin.evaluation-templates.classifications.store', $evaluationTemplate), [
        'name' => 'General Education Courses',
        'description' => 'General education requirements',
        'display_order' => 1,
        'subject_ids' => [$departmentSubject->id, $generalEducationSubject->id],
    ])->assertRedirect(route('admin.evaluation-templates.show', $evaluationTemplate));

    $classification = $evaluationTemplate->classifications()->firstOrFail();

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $departmentSubject->id,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('evaluation_template_classification_subject', [
        'evaluation_template_classification_id' => $classification->id,
        'subject_id' => $generalEducationSubject->id,
        'display_order' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.evaluation-templates.show', $evaluationTemplate))
        ->assertOk()
        ->assertSee('General Education Courses')
        ->assertSee('CS101')
        ->assertSee('GEC101');
});

test('non administrators can not access evaluation template management screens', function () {
    $student = User::factory()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();

    $this->actingAs($student)
        ->get(route('admin.courses.evaluation-templates.create', $course))
        ->assertForbidden();
});
