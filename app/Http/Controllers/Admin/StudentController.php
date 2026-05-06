<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdviserAssignment;
use App\Models\Course;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(): View
    {
        return view('admin.users.students.index', [
            'students' => User::query()
                ->where('role', UserRole::Student)
                ->with([
                    'studentProfile.activeAdmission.course',
                    'studentProfile.activeAdmission.major',
                    'studentProfile.adviserBinding.adviserAssignment.adviserProfile.user',
                ])
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.students.create', $this->studentFormData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudent($request);
        $isGraduating = $request->boolean('is_graduating');
        $isWithdrawn = $request->boolean('is_withdrawn');
        $assignedByUserId = (int) $request->user()->id;

        DB::transaction(function () use ($validated, $isGraduating, $isWithdrawn, $assignedByUserId): void {
            $user = User::query()->create([
                ...$this->userPayload($validated, UserRole::Student),
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);

            $profile = $user->studentProfile()->create([
                'sex_at_birth' => $validated['sex_at_birth'],
                'year_level' => $validated['year_level'],
                'home_address' => $validated['home_address'],
                'is_graduating' => $isGraduating,
                'withdrawn_at' => $isWithdrawn ? now() : null,
            ]);

            $profile->admissions()->create([
                'course_id' => $validated['course_id'],
                'major_id' => $validated['major_id'] ?? null,
                'admission_date' => $validated['admission_date'],
                'is_active' => true,
            ]);

            $this->syncAdviserBinding(
                $profile,
                $validated['adviser_assignment_id'] ?? null,
                $assignedByUserId,
            );
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Student created successfully.');
    }

    public function edit(User $student): View
    {
        abort_unless($student->isStudent(), 404);

        return view('admin.users.students.edit', [
            ...$this->studentFormData(),
            'student' => $student->load([
                'studentProfile.activeAdmission',
                'studentProfile.adviserBinding.adviserAssignment.adviserProfile.user',
            ]),
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $validated = $this->validateStudent($request, $student);
        $isGraduating = $request->boolean('is_graduating');
        $isWithdrawn = $request->boolean('is_withdrawn');
        $assignedByUserId = (int) $request->user()->id;

        DB::transaction(function () use ($student, $validated, $isGraduating, $isWithdrawn, $assignedByUserId): void {
            $student->fill($this->userPayload($validated, UserRole::Student));
            $student->email_verified_at = now();

            if (! empty($validated['password'])) {
                $student->password = Hash::make($validated['password']);
            }

            $student->save();

            $existingProfile = $student->studentProfile;
            $existingActiveAdmission = $existingProfile?->activeAdmission;

            $profile = $student->studentProfile()->updateOrCreate([], [
                'sex_at_birth' => $validated['sex_at_birth'],
                'year_level' => $validated['year_level'],
                'home_address' => $validated['home_address'],
                'is_graduating' => $isGraduating,
                'withdrawn_at' => $isWithdrawn
                    ? ($existingProfile?->withdrawn_at ?? now())
                    : null,
            ]);

            $courseChanged = $existingActiveAdmission !== null
                && ((int) $existingActiveAdmission->course_id !== (int) $validated['course_id']
                    || (int) ($existingActiveAdmission->major_id ?? 0) !== (int) ($validated['major_id'] ?? 0));

            if ($existingActiveAdmission === null) {
                $profile->admissions()->create([
                    'course_id' => $validated['course_id'],
                    'major_id' => $validated['major_id'] ?? null,
                    'admission_date' => $validated['admission_date'],
                    'is_active' => true,
                ]);
            } elseif ($courseChanged) {
                $existingActiveAdmission->forceFill([
                    'is_active' => false,
                ])->save();

                $profile->admissions()->create([
                    'course_id' => $validated['course_id'],
                    'major_id' => $validated['major_id'] ?? null,
                    'admission_date' => $validated['admission_date'],
                    'is_active' => true,
                ]);
            } else {
                $existingActiveAdmission->forceFill([
                    'course_id' => $validated['course_id'],
                    'major_id' => $validated['major_id'] ?? null,
                    'admission_date' => $validated['admission_date'],
                ])->save();
            }

            $this->syncAdviserBinding(
                $profile,
                $validated['adviser_assignment_id'] ?? null,
                $assignedByUserId,
            );
        });

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Student updated successfully.');
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Student deleted successfully.');
    }

    private function studentFormData(): array
    {
        return [
            'courses' => Course::query()->with('majors')->orderBy('name')->get(),
            'majors' => Major::query()->with('course')->orderBy('name')->get(),
            'adviserAssignments' => AdviserAssignment::query()
                ->with(['course', 'major', 'adviserProfile.user'])
                ->orderBy('year_level')
                ->get(),
        ];
    }

    private function validateStudent(Request $request, ?User $student = null): array
    {
        $validated = $request->validate([
            'id_number' => ['required', 'string', 'max:255', Rule::unique(User::class, 'id_number')->ignore($student?->id)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'name_suffix' => ['nullable', 'string', 'max:255'],
            'sex_at_birth' => ['required', 'string', Rule::in(['Male', 'Female'])],
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'major_id' => ['nullable', 'integer', Rule::exists('majors', 'id')],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'adviser_assignment_id' => ['nullable', 'integer', Rule::exists('adviser_assignments', 'id')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($student?->id)],
            'home_address' => ['required', 'string'],
            'username' => [$student ? 'required' : 'nullable', 'string', 'max:255', Rule::unique(User::class, 'username')->ignore($student?->id)],
            'password' => [$student ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'admission_date' => ['required', 'date'],
            'is_graduating' => ['nullable', 'boolean'],
            'is_withdrawn' => ['nullable', 'boolean'],
        ]);

        if ($student === null) {
            $validated['username'] = User::uniqueUsernameFromEmail($validated['email']);
        }

        if (! empty($validated['major_id'])) {
            $major = Major::query()->findOrFail($validated['major_id']);
            abort_unless((int) $major->course_id === (int) $validated['course_id'], 422);
        }

        $this->ensureCompatibleAdviserAssignment(
            $validated['adviser_assignment_id'] ?? null,
            (int) $validated['course_id'],
            isset($validated['major_id']) ? (int) $validated['major_id'] : null,
            (int) $validated['year_level'],
        );

        return $validated;
    }

    private function ensureCompatibleAdviserAssignment(?int $adviserAssignmentId, int $courseId, ?int $majorId, int $yearLevel): void
    {
        if ($adviserAssignmentId === null) {
            return;
        }

        $assignment = AdviserAssignment::query()->findOrFail($adviserAssignmentId);

        $majorMatches = $assignment->major_id === null || $assignment->major_id === $majorId;

        if ($assignment->course_id !== $courseId || $assignment->year_level !== $yearLevel || ! $majorMatches) {
            throw ValidationException::withMessages([
                'adviser_assignment_id' => 'The selected adviser assignment is not compatible with the student\'s current course, major, and year level.',
            ]);
        }
    }

    private function syncAdviserBinding(StudentProfile $studentProfile, ?int $adviserAssignmentId, int $assignedByUserId): void
    {
        if ($adviserAssignmentId === null) {
            $studentProfile->adviserBinding()->delete();

            return;
        }

        $studentProfile->adviserBinding()->updateOrCreate([], [
            'adviser_assignment_id' => $adviserAssignmentId,
            'assigned_by_user_id' => $assignedByUserId,
        ]);
    }

    private function userPayload(array $validated, UserRole $role): array
    {
        $nameParts = collect([
            $validated['first_name'],
            $validated['middle_name'] ?? null,
            $validated['last_name'],
            $validated['name_suffix'] ?? null,
        ])->filter()->implode(' ');

        return [
            'id_number' => $validated['id_number'],
            'name' => $nameParts,
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'name_suffix' => $validated['name_suffix'] ?? null,
            'email' => $validated['email'],
            'username' => $validated['username'],
            'role' => $role,
        ];
    }
}
