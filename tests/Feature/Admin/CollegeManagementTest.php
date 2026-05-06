<?php

use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can view the college management screen', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create([
        'name' => 'College of Engineering',
        'dean' => 'Elena Cruz',
        'description' => 'Engineering programs',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.colleges.index'));

    $response->assertOk()
        ->assertSee('College of Engineering')
        ->assertDontSee($college->dean)
        ->assertDontSee($college->description);
});

test('administrators can create colleges', function () {
    $admin = User::factory()->administrator()->create();

    $response = $this->actingAs($admin)->post(route('admin.colleges.store'), [
        'name' => 'College of Computer Studies',
        'abbreviation' => 'CCS',
        'dean' => 'Maria Santos',
        'description' => 'Academic unit for computing programs.',
    ]);

    $response->assertRedirect(route('admin.colleges.index'));

    $this->assertDatabaseHas('colleges', [
        'name' => 'College of Computer Studies',
        'abbreviation' => 'CCS',
        'dean' => 'Maria Santos',
    ]);
});

test('administrators can update colleges', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create([
        'name' => 'College of Technology',
        'dean' => 'Jane Doe',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.colleges.update', $college), [
        'name' => 'College of Industrial Technology',
        'abbreviation' => 'CIT',
        'dean' => 'John Doe',
        'description' => 'Updated description',
    ]);

    $response->assertRedirect(route('admin.colleges.index'));

    $this->assertDatabaseHas('colleges', [
        'id' => $college->id,
        'name' => 'College of Industrial Technology',
        'abbreviation' => 'CIT',
        'dean' => 'John Doe',
    ]);
});

test('administrators can delete colleges', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();

    $response = $this->actingAs($admin)->delete(route('admin.colleges.destroy', $college));

    $response->assertRedirect(route('admin.colleges.index'));

    $this->assertDatabaseMissing('colleges', [
        'id' => $college->id,
    ]);
});

test('non administrators can not manage colleges', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.colleges.index'))
        ->assertForbidden();
});

test('administrators can review student and adviser lists', function () {
    $admin = User::factory()->administrator()->create();
    $studentProfile = StudentProfile::factory()->create();
    $adviserProfile = AdviserProfile::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.students.index'))
        ->assertOk()
        ->assertSee($studentProfile->user->username);

    $this->actingAs($admin)
        ->get(route('admin.advisers.index'))
        ->assertOk()
        ->assertSee($adviserProfile->user->email);
});

test('college detail page does not show chairperson names in the department table', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = \App\Models\Department::factory()->for($college)->create([
        'chairperson' => 'Chair Name',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.colleges.show', $college))
        ->assertOk()
        ->assertSee('Back to Colleges')
        ->assertSee($department->name)
        ->assertDontSee('Chair Name');
});
