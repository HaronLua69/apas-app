@php($admission = $student?->studentProfile?->activeAdmission)
@php($isCreate = $student === null)
@php($selectedCourseId = old('course_id', $admission?->course_id))
@php($selectedMajorId = old('major_id', $admission?->major_id))
@php($selectedYearLevel = old('year_level', $student?->studentProfile?->year_level))
@php($compatibleAdviserAssignments = $adviserAssignments->filter(function ($assignment) use ($selectedCourseId, $selectedMajorId, $selectedYearLevel) {
    if ((string) $selectedCourseId === '' || (string) $selectedYearLevel === '') {
        return false;
    }

    if ((int) $assignment->course_id !== (int) $selectedCourseId || (int) $assignment->year_level !== (int) $selectedYearLevel) {
        return false;
    }

    return $assignment->major_id === null || (string) $assignment->major_id === (string) $selectedMajorId;
})->values())

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="id_number" :label="__('ID Number')" :value="old('id_number', $student?->id_number)" required autofocus />
        <flux:input name="first_name" :label="__('First Name')" :value="old('first_name', $student?->first_name)" required />
        <flux:input name="middle_name" :label="__('Middle Name')" :value="old('middle_name', $student?->middle_name)" />
        <flux:input name="last_name" :label="__('Last Name')" :value="old('last_name', $student?->last_name)" required />
        <flux:input name="name_suffix" :label="__('Name Prefix / Suffix')" :value="old('name_suffix', $student?->name_suffix)" />
        <flux:select name="sex_at_birth" :label="__('Sex at Birth')" required>
            <option value="Male" @selected(old('sex_at_birth', $student?->studentProfile?->sex_at_birth) === 'Male')>{{ __('Male') }}</option>
            <option value="Female" @selected(old('sex_at_birth', $student?->studentProfile?->sex_at_birth) === 'Female')>{{ __('Female') }}</option>
        </flux:select>
        <flux:input name="year_level" :label="__('Year Level')" :value="old('year_level', $student?->studentProfile?->year_level)" type="number" min="1" max="4" required />
        <flux:input name="admission_date" :label="__('Admission Date')" :value="old('admission_date', $admission?->admission_date?->format('Y-m-d'))" type="date" required />
    </div>

    <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Login Credentials') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                {{ $isCreate
                    ? __('Set the email and initial password for the new student account. The username will default to the local-part of the email address, and the student can change it later in Profile settings.')
                    : __('Update the username or reset the password here. Leave the password fields blank to keep the current password.') }}
            </p>
        </div>

        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <flux:input name="email" :label="__('Email Address')" :value="old('email', $student?->email)" type="email" required />
            @if (! $isCreate)
                <flux:input name="username" :label="__('Username')" :value="old('username', $student?->username)" required />
            @else
                <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                    {{ __('Username will be generated automatically from the email address.') }}
                </div>
            @endif
            <flux:input name="password" :label="__('Password')" type="password" :required="$isCreate" />
            <flux:input name="password_confirmation" :label="__('Confirm Password')" type="password" :required="$isCreate" />
        </div>
    </section>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:select name="course_id" :label="__('Course')" required>
            <option value="">{{ __('Select a course') }}</option>
            @foreach ($courses as $course)
                <option value="{{ $course->id }}" @selected((string) old('course_id', $admission?->course_id) === (string) $course->id)>
                    {{ $course->name }}
                </option>
            @endforeach
        </flux:select>

        <flux:select name="major_id" :label="__('Major')">
            <option value="">{{ __('No major') }}</option>
            @foreach ($majors as $major)
                <option value="{{ $major->id }}" @selected((string) old('major_id', $admission?->major_id) === (string) $major->id)>
                    {{ $major->course->abbreviation ?: $major->course->name }} · {{ $major->name }}
                </option>
            @endforeach
        </flux:select>
    </div>

    <section class="rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Adviser Binding') }}</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Bind this student to one compatible adviser assignment based on the current course, optional major, and year level.') }}</p>
        </div>

        @if ($compatibleAdviserAssignments->isNotEmpty())
            <div class="mt-4">
                <flux:select name="adviser_assignment_id" :label="__('Program Adviser')">
                    <option value="">{{ __('No adviser binding') }}</option>
                    @foreach ($compatibleAdviserAssignments as $assignment)
                        <option value="{{ $assignment->id }}" @selected((string) old('adviser_assignment_id', $student?->studentProfile?->adviserBinding?->adviser_assignment_id) === (string) $assignment->id)>
                            {{ $assignment->adviserProfile->user->fullName() }} · {{ __('Year :year', ['year' => $assignment->year_level]) }} · {{ $assignment->course->abbreviation ?: $assignment->course->name }} · {{ $assignment->major?->name ?: __('General program') }}
                        </option>
                    @endforeach
                </flux:select>
            </div>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                {{ __('No compatible adviser assignments are available for the currently selected course, major, and year level.') }}
            </div>
        @endif
    </section>

    <flux:textarea name="home_address" :label="__('Home Address')">{{ old('home_address', $student?->studentProfile?->home_address) }}</flux:textarea>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:checkbox name="is_graduating" :label="__('Graduating')" :checked="old('is_graduating', $student?->studentProfile?->is_graduating)" />
        <flux:checkbox name="is_withdrawn" :label="__('Withdrawn from Program')" :checked="old('is_withdrawn', $student?->studentProfile?->withdrawn_at !== null)" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <flux:button :href="route('admin.students.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        <flux:button type="submit" variant="primary">{{ $student ? __('Update Student') : __('Save Student') }}</flux:button>
    </div>
</form>