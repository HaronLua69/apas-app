<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AdviserAssignment;
use App\Models\AdviserProfile;
use App\Models\College;
use App\Models\Course;
use App\Models\Department;
use App\Models\Major;
use App\Models\StudentAdviserBinding;
use App\Models\StudentAdmission;
use App\Models\StudentProfile;
use App\Models\StudentSubjectEnrollment;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $administrator = User::query()->updateOrCreate(
            ['username' => 'HaronLua69'],
            [
                'id_number' => '2018-192',
                'name' => 'Haron Hakeen Dapac Lua',
                'first_name' => 'Haron Hakeen',
                'middle_name' => 'Dapac',
                'last_name' => 'Lua',
                'name_suffix' => null,
                'email' => 'haronhakeen.lua@g.msuiit.edu.ph',
                'role' => UserRole::Administrator,
                'secondary_role' => UserRole::Adviser,
                'password' => Hash::make('admin12345'),
            ],
        );

        $administrator->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $student = User::query()->updateOrCreate(
            ['id_number' => '2022-3741'],
            [
                'name' => 'Wilson B. Augosto',
                'first_name' => 'Wilson',
                'middle_name' => 'B.',
                'last_name' => 'Augosto',
                'name_suffix' => null,
                'email' => 'wilson.augosto@g.msuiit.edu.ph',
                'username' => 'wilson.augosto',
                'role' => UserRole::Student,
                'secondary_role' => null,
                'password' => Hash::make('student12345'),
            ],
        );

        if ($student->wasRecentlyCreated) {
            $student->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        $legacyStudent = User::query()
            ->where('username', 'WilsonStudent')
            ->whereKeyNot($student->id)
            ->first();

        if ($legacyStudent !== null) {
            $legacyStudentProfile = $legacyStudent->studentProfile;

            if ($legacyStudentProfile !== null) {
                StudentAdviserBinding::query()
                    ->where('student_profile_id', $legacyStudentProfile->id)
                    ->delete();

                StudentSubjectEnrollment::query()
                    ->where('student_profile_id', $legacyStudentProfile->id)
                    ->delete();

                StudentAdmission::query()
                    ->where('student_profile_id', $legacyStudentProfile->id)
                    ->delete();

                $legacyStudentProfile->delete();
            }

            $legacyStudent->delete();
        }

        $studentProfile = StudentProfile::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'sex_at_birth' => 'Male',
                'year_level' => 4,
                'home_address' => 'Iligan City',
                'is_graduating' => false,
            ],
        );

        $studentProfile->loadMissing('activeAdmission.course');
        $activeAdmission = $studentProfile->activeAdmission;

        if ($activeAdmission === null) {
            $college = College::query()->firstOrCreate(
                ['abbreviation' => 'CCS'],
                [
                    'name' => 'College of Computing Studies',
                    'dean' => 'Office of the Dean',
                    'description' => 'Seeded development college for APAS fixture data.',
                ],
            );

            $department = Department::query()->firstOrCreate(
                [
                    'college_id' => $college->id,
                    'abbreviation' => 'DIT',
                ],
                [
                    'name' => 'Department of Information Technology',
                    'chairperson' => 'Office of the Chairperson',
                    'description' => 'Seeded development department for APAS fixture data.',
                ],
            );

            $course = Course::query()->firstOrCreate(
                [
                    'department_id' => $department->id,
                    'abbreviation' => 'BSIT',
                ],
                [
                    'name' => 'Bachelor of Science in Information Technology',
                    'description' => 'Seeded development course for APAS fixture data.',
                ],
            );

            $major = Major::query()->firstOrCreate(
                [
                    'course_id' => $course->id,
                    'name' => 'Network Systems',
                ],
                [
                    'description' => 'Seeded development major for APAS fixture data.',
                ],
            );

            $activeAdmission = StudentAdmission::query()->updateOrCreate(
                [
                    'student_profile_id' => $studentProfile->id,
                    'course_id' => $course->id,
                    'major_id' => $major->id,
                ],
                [
                    'admission_date' => '2022-06-01',
                    'is_active' => true,
                ],
            );

            StudentAdmission::query()
                ->where('student_profile_id', $studentProfile->id)
                ->whereKeyNot($activeAdmission->id)
                ->update(['is_active' => false]);
        }

        $activeAdmission->loadMissing('course');

        $adviserProfile = AdviserProfile::query()->updateOrCreate(
            ['user_id' => $administrator->id],
            [
                'department_id' => $activeAdmission->course->department_id,
                'sex_at_birth' => 'Male',
                'rank' => 'Instructor I',
                'home_address' => 'Iligan City',
            ],
        );

        $adviserAssignment = AdviserAssignment::query()->updateOrCreate(
            [
                'adviser_profile_id' => $adviserProfile->id,
                'course_id' => $activeAdmission->course_id,
                'major_id' => $activeAdmission->major_id,
                'year_level' => $studentProfile->year_level,
            ],
            [],
        );

        StudentAdviserBinding::query()->updateOrCreate(
            ['student_profile_id' => $studentProfile->id],
            [
                'adviser_assignment_id' => $adviserAssignment->id,
                'assigned_by_user_id' => $administrator->id,
            ],
        );
    }
}
