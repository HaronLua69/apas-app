<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\Prospectus;
use App\Models\Subject;
use App\Models\SubjectRequisite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create prospectuses and assign subjects to prospectus terms', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $otherCollege = College::factory()->create();
    $otherDepartment = Department::factory()->for($otherCollege)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $major = Major::factory()->for($course)->create([
        'name' => 'Network Technology',
    ]);
    $subjectOne = Subject::factory()->for($department)->create([
        'subject_code' => 'IT101',
        'credit_units' => 3,
        'counts_toward_gpa' => true,
    ]);
    $subjectTwo = Subject::factory()->for($otherDepartment)->create([
        'subject_code' => 'IT102',
        'credit_units' => 4,
        'counts_toward_gpa' => false,
    ]);

    $this->actingAs($admin)->post(route('admin.courses.prospectuses.store', $course), [
        'title' => 'BSIT Network Technology Prospectus',
        'major_id' => $major->id,
        'description' => 'Primary curriculum flow for the major.',
        'is_active' => '1',
    ])->assertRedirect();

    $prospectus = Prospectus::query()->where('title', 'BSIT Network Technology Prospectus')->firstOrFail();

    expect($prospectus->major_id)->toBe($major->id)
        ->and($prospectus->is_active)->toBeTrue();

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $prospectus), [
        'year_level' => 1,
        'term_name' => '1st Semester',
        'subject_ids' => [$subjectOne->id, $subjectTwo->id],
        'elective_names' => ['Foreign Language Elective', 'Technical Elective 1'],
        'elective_categories' => ['language-elective', 'technical-elective'],
    ])->assertRedirect(route('admin.prospectuses.show', $prospectus));

    $term = $prospectus->terms()->firstOrFail();

    $this->assertDatabaseHas('prospectus_terms', [
        'id' => $term->id,
        'prospectus_id' => $prospectus->id,
        'year_level' => 1,
        'term_name' => '1st Semester',
    ]);

    $this->assertDatabaseHas('prospectus_term_subject', [
        'prospectus_term_id' => $term->id,
        'subject_id' => $subjectOne->id,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('prospectus_term_subject', [
        'prospectus_term_id' => $term->id,
        'subject_id' => $subjectTwo->id,
        'display_order' => 2,
    ]);

    $this->assertDatabaseHas('prospectus_term_electives', [
        'prospectus_term_id' => $term->id,
        'name' => 'Foreign Language Elective',
        'category' => 'language-elective',
        'units' => 3,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('prospectus_term_electives', [
        'prospectus_term_id' => $term->id,
        'name' => 'Technical Elective 1',
        'category' => 'technical-elective',
        'units' => 3,
        'display_order' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.show', $prospectus))
        ->assertOk()
        ->assertSee('Program:')
        ->assertSee('Bachelor of Science in Information Technology')
        ->assertSee('Major:')
        ->assertSee('Major in Network Technology')
        ->assertSee('Semestral Distribution of Subjects')
        ->assertSee('First Year, 1st Semester')
        ->assertSee('Course Code')
        ->assertSee('Course Title')
        ->assertSee('Units')
        ->assertSee('Pre-Requisite')
        ->assertSee('Co-Requisite')
        ->assertSee('Edit')
        ->assertSee('Delete')
        ->assertSee('Total Units: 9 (13)')
        ->assertSee('(4)')
        ->assertSee('As the course requires')
        ->assertSee('>3<', false)
        ->assertSee('Elective 1')
        ->assertSee('Foreign Language Elective')
        ->assertSee('Elective 2')
        ->assertSee('Technical Elective 1')
        ->assertSee('IT101')
        ->assertSee('IT102');

    $this->actingAs($admin)
        ->get(route('admin.terms.edit', $term))
        ->assertOk()
        ->assertSee('Edit Semestral Distribution')
        ->assertSee('BSIT Network Technology Prospectus')
        ->assertSee('Search by subject code or subject title')
        ->assertSee('Add Elective')
        ->assertSee('Foreign Language Elective');

    $this->actingAs($admin)->put(route('admin.terms.update', $term), [
        'year_level' => 1,
        'term_name' => '2nd Semester',
        'subject_ids' => [$subjectTwo->id, $subjectOne->id],
        'elective_names' => ['Technical Elective 2'],
        'elective_categories' => ['technical-elective'],
    ])->assertRedirect(route('admin.prospectuses.show', $prospectus));

    $this->assertDatabaseHas('prospectus_terms', [
        'id' => $term->id,
        'prospectus_id' => $prospectus->id,
        'year_level' => 1,
        'term_name' => '2nd Semester',
    ]);

    $this->assertDatabaseHas('prospectus_term_electives', [
        'prospectus_term_id' => $term->id,
        'name' => 'Technical Elective 2',
        'category' => 'technical-elective',
        'units' => 3,
        'display_order' => 1,
    ]);

    $this->actingAs($admin)->put(route('admin.prospectuses.update', $prospectus), [
        'title' => 'Updated BSIT Network Technology Prospectus',
        'major_id' => $major->id,
        'description' => 'Updated curriculum flow.',
        'is_active' => '1',
    ])->assertRedirect(route('admin.prospectuses.show', $prospectus));

    $this->assertDatabaseHas('prospectuses', [
        'id' => $prospectus->id,
        'title' => 'Updated BSIT Network Technology Prospectus',
        'is_active' => true,
    ]);
});

test('prospectus page reports forward-looking prerequisite sequencing issues', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $prospectus = Prospectus::factory()->for($course)->create([
        'is_active' => true,
    ]);
    $prerequisite = Subject::factory()->for($department)->create([
        'subject_code' => 'CS101',
        'subject_title' => 'Programming 1',
    ]);
    $dependent = Subject::factory()->for($department)->create([
        'subject_code' => 'CS201',
        'subject_title' => 'Data Structures',
    ]);

    SubjectRequisite::query()->create([
        'subject_id' => $dependent->id,
        'requisite_subject_id' => $prerequisite->id,
        'type' => 'prerequisite',
    ]);

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $prospectus), [
        'year_level' => 1,
        'term_name' => '1st Semester',
        'subject_ids' => [$dependent->id],
    ])->assertRedirect(route('admin.prospectuses.show', $prospectus));

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $prospectus), [
        'year_level' => 1,
        'term_name' => '2nd Semester',
        'subject_ids' => [$prerequisite->id],
    ])->assertRedirect(route('admin.prospectuses.show', $prospectus));

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.show', $prospectus))
        ->assertOk()
        ->assertSee('Prerequisite Sequence Check')
        ->assertSee('CS201 requires CS101');
});

test('prospectus prerequisite checks respect major scoped requisites', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $databaseMajor = Major::factory()->for($course)->create([
        'name' => 'Database Systems',
    ]);
    $networkMajor = Major::factory()->for($course)->create([
        'name' => 'Network Systems',
    ]);

    $specialTopics = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE193',
        'subject_title' => 'Special Topics in IT',
    ]);
    $advancedDatabases = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE152',
        'subject_title' => 'Advanced Databases',
    ]);
    $advancedNetworks = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE133',
        'subject_title' => 'Advanced Computer Networks',
    ]);

    $this->actingAs($admin)->post(route('admin.subjects.requisites.store', $specialTopics), [
        'requisite_subject_id' => $advancedDatabases->id,
        'type' => 'prerequisite',
        'scope_key' => 'major:'.$databaseMajor->id,
    ])->assertRedirect(route('admin.subjects.requisites.index', $specialTopics));

    $this->actingAs($admin)->post(route('admin.subjects.requisites.store', $specialTopics), [
        'requisite_subject_id' => $advancedNetworks->id,
        'type' => 'prerequisite',
        'scope_key' => 'major:'.$networkMajor->id,
    ])->assertRedirect(route('admin.subjects.requisites.index', $specialTopics));

    $databaseProspectus = Prospectus::factory()->for($course)->create([
        'major_id' => $databaseMajor->id,
        'title' => 'BSIT Database Systems Prospectus',
        'is_active' => true,
    ]);
    $networkProspectus = Prospectus::factory()->for($course)->create([
        'major_id' => $networkMajor->id,
        'title' => 'BSIT Network Systems Prospectus',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $databaseProspectus), [
        'year_level' => 2,
        'term_name' => '1st Semester',
        'subject_ids' => [$advancedDatabases->id],
    ])->assertRedirect(route('admin.prospectuses.show', $databaseProspectus));

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $databaseProspectus), [
        'year_level' => 2,
        'term_name' => '2nd Semester',
        'subject_ids' => [$specialTopics->id],
    ])->assertRedirect(route('admin.prospectuses.show', $databaseProspectus));

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $networkProspectus), [
        'year_level' => 2,
        'term_name' => '1st Semester',
        'subject_ids' => [$advancedNetworks->id],
    ])->assertRedirect(route('admin.prospectuses.show', $networkProspectus));

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $networkProspectus), [
        'year_level' => 2,
        'term_name' => '2nd Semester',
        'subject_ids' => [$specialTopics->id],
    ])->assertRedirect(route('admin.prospectuses.show', $networkProspectus));

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.show', $databaseProspectus))
        ->assertOk()
        ->assertSee('ITE152')
        ->assertDontSee('ITE133 requires')
        ->assertDontSee('ITE133 as a prerequisite');

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.show', $networkProspectus))
        ->assertOk()
        ->assertSee('ITE133')
        ->assertDontSee('ITE152 requires')
        ->assertDontSee('ITE152 as a prerequisite');
});

test('administrators can duplicate a prospectus to another major and keep its semestral distributions', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create([
        'name' => 'Bachelor of Science in Information Technology',
        'abbreviation' => 'BSIT',
    ]);
    $databaseMajor = Major::factory()->for($course)->create([
        'name' => 'Database Systems',
    ]);
    $networkMajor = Major::factory()->for($course)->create([
        'name' => 'Network Systems',
    ]);
    $databaseSubject = Subject::factory()->for($department)->create([
        'subject_code' => 'ITE152',
        'subject_title' => 'Advanced Databases',
        'credit_units' => 3,
    ]);

    $sourceProspectus = Prospectus::factory()->for($course)->create([
        'major_id' => $databaseMajor->id,
        'title' => 'BSIT Database Systems Prospectus',
        'description' => 'Source prospectus for duplication.',
        'is_active' => true,
    ]);

    $this->actingAs($admin)->post(route('admin.prospectuses.terms.store', $sourceProspectus), [
        'year_level' => 3,
        'term_name' => '1st Semester',
        'subject_ids' => [$databaseSubject->id],
        'elective_names' => ['Technical Elective 1'],
        'elective_categories' => ['technical-elective'],
    ])->assertRedirect(route('admin.prospectuses.show', $sourceProspectus));

    $sourceTerm = $sourceProspectus->terms()->firstOrFail();

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.duplicate', $sourceProspectus))
        ->assertOk()
        ->assertSee('Duplicate Prospectus')
        ->assertSee('BSIT Database Systems Prospectus')
        ->assertSee('Network Systems');

    $this->actingAs($admin)->post(route('admin.prospectuses.duplicate.store', $sourceProspectus), [
        'title' => 'BSIT Network Systems Prospectus',
        'major_id' => $networkMajor->id,
        'description' => 'Copied from the database systems prospectus.',
        'is_active' => '1',
    ])->assertRedirect();

    $duplicateProspectus = Prospectus::query()
        ->where('title', 'BSIT Network Systems Prospectus')
        ->firstOrFail();

    expect($duplicateProspectus->major_id)->toBe($networkMajor->id)
        ->and($duplicateProspectus->course_id)->toBe($course->id)
        ->and($duplicateProspectus->id)->not->toBe($sourceProspectus->id)
        ->and($duplicateProspectus->is_active)->toBeTrue();

    $duplicateTerm = $duplicateProspectus->terms()->with(['subjects', 'electives'])->firstOrFail();

    expect($duplicateTerm->year_level)->toBe($sourceTerm->year_level)
        ->and($duplicateTerm->term_name)->toBe($sourceTerm->term_name)
        ->and($duplicateTerm->subjects->pluck('id')->all())->toBe([$databaseSubject->id])
        ->and($duplicateTerm->electives->pluck('name')->all())->toBe(['Technical Elective 1']);

    $this->assertDatabaseHas('prospectus_term_subject', [
        'prospectus_term_id' => $duplicateTerm->id,
        'subject_id' => $databaseSubject->id,
        'display_order' => 1,
    ]);

    $this->assertDatabaseHas('prospectus_term_electives', [
        'prospectus_term_id' => $duplicateTerm->id,
        'name' => 'Technical Elective 1',
        'category' => 'technical-elective',
        'units' => 3,
        'display_order' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.prospectuses.show', $duplicateProspectus))
        ->assertOk()
        ->assertSee('BSIT Network Systems Prospectus')
        ->assertSee('Major in Network Systems')
        ->assertSee('ITE152')
        ->assertSee('Technical Elective 1');
});

test('non administrators can not access prospectus management screens', function () {
    $student = User::factory()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();

    $this->actingAs($student)
        ->get(route('admin.courses.prospectuses.create', $course))
        ->assertForbidden();
});
