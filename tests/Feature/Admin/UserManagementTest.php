<?php

use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrators can create and update student accounts', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();
    $major = Major::factory()->for($course)->create();

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'id_number' => '2026-1001',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'name_suffix' => null,
        'sex_at_birth' => 'Male',
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 1,
        'email' => 'juan@example.com',
        'home_address' => 'Iligan City',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'admission_date' => '2026-06-01',
        'is_graduating' => false,
    ])->assertRedirect(route('admin.students.index'));

    $student = User::query()->where('username', 'juan')->firstOrFail();

    expect($student->studentProfile)->not()->toBeNull()
        ->and($student->studentProfile->activeAdmission)->not()->toBeNull()
        ->and($student->studentProfile->activeAdmission->course_id)->toBe($course->id)
        ->and($student->username)->toBe('juan');

    $this->actingAs($admin)->put(route('admin.students.update', $student), [
        'id_number' => '2026-1001',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'name_suffix' => 'Jr.',
        'sex_at_birth' => 'Male',
        'course_id' => $course->id,
        'major_id' => $major->id,
        'year_level' => 2,
        'email' => 'juan.updated@example.com',
        'home_address' => 'MSU-IIT',
        'username' => 'juan.cruz',
        'password' => '',
        'password_confirmation' => '',
        'admission_date' => '2026-06-01',
        'is_graduating' => '1',
    ])->assertRedirect(route('admin.students.index'));

    $student->refresh();

    expect($student->email)->toBe('juan.updated@example.com')
        ->and($student->username)->toBe('juan.cruz')
        ->and($student->name_suffix)->toBe('Jr.')
        ->and($student->studentProfile->year_level)->toBe(2)
        ->and($student->studentProfile->is_graduating)->toBeTrue();
});

    test('student create page shows required password fields', function () {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
        ->get(route('admin.students.create'))
        ->assertOk()
        ->assertSee('Login Credentials')
        ->assertSee('Username will be generated automatically from the email address.')
        ->assertSee('Password')
        ->assertSee('Confirm Password')
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false)
        ->assertDontSee('Set the username and initial password for the new student account.');
});

test('student usernames generated from email remain unique during admin registration', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create();
    $course = Course::factory()->for($department)->create();

    User::factory()->create([
        'username' => 'juan',
        'email' => 'existing@example.com',
    ]);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'id_number' => '2026-1002',
        'first_name' => 'Juan',
        'middle_name' => null,
        'last_name' => 'Rivera',
        'name_suffix' => null,
        'sex_at_birth' => 'Male',
        'course_id' => $course->id,
        'major_id' => null,
        'year_level' => 1,
        'email' => 'juan@example.com',
        'home_address' => 'Iligan City',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'admission_date' => '2026-06-01',
        'is_graduating' => false,
    ])->assertRedirect(route('admin.students.index'));

    $student = User::query()->where('email', 'juan@example.com')->firstOrFail();

    expect($student->username)->toBe('juan2');
    });

test('adviser create page shows actual password input fields', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)
        ->get(route('admin.advisers.create'))
        ->assertOk()
        ->assertSee('Password')
        ->assertSee('Confirm Password')
        ->assertSee('name="password"', false)
        ->assertSee('name="password_confirmation"', false);
});

test('administrators can create, update, and delete adviser accounts', function () {
    $admin = User::factory()->administrator()->create();
    $college = College::factory()->create();
    $department = Department::factory()->for($college)->create([
        'name' => 'Department of Information Technology',
    ]);

    $this->actingAs($admin)->post(route('admin.advisers.store'), [
        'id_number' => '2020-321',
        'first_name' => 'Maria',
        'middle_name' => 'Lopez',
        'last_name' => 'Santos',
        'name_suffix' => null,
        'sex_at_birth' => 'Female',
        'department_id' => $department->id,
        'rank' => 'Instructor I',
        'email' => 'maria@example.com',
        'home_address' => 'Pala-o, Iligan City',
        'username' => 'mariasantos',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('admin.advisers.index'));

    $adviser = User::query()->where('username', 'mariasantos')->firstOrFail();

    $this->actingAs($admin)->put(route('admin.advisers.update', $adviser), [
        'id_number' => '2020-321',
        'first_name' => 'Maria',
        'middle_name' => 'Lopez',
        'last_name' => 'Santos',
        'name_suffix' => 'III',
        'sex_at_birth' => 'Female',
        'department_id' => $department->id,
        'rank' => 'Assistant Professor I',
        'email' => 'maria.updated@example.com',
        'home_address' => 'Iligan City',
        'username' => 'mariasantos',
        'password' => '',
        'password_confirmation' => '',
    ])->assertRedirect(route('admin.advisers.index'));

    $adviser->refresh();

    expect($adviser->email)->toBe('maria.updated@example.com')
        ->and($adviser->adviserProfile->rank)->toBe('Assistant Professor I')
        ->and($adviser->name_suffix)->toBe('III');

    $this->actingAs($admin)
        ->delete(route('admin.advisers.destroy', $adviser))
        ->assertRedirect(route('admin.advisers.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $adviser->id,
    ]);
});

test('non administrators can not access user manager create screens', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('admin.students.create'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('admin.advisers.create'))
        ->assertForbidden();
});
