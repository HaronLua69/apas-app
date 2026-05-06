<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="id_number" :label="__('ID Number')" :value="old('id_number', $adviser?->id_number)" required autofocus />
        <flux:input name="username" :label="__('Username')" :value="old('username', $adviser?->username)" required />
        <flux:input name="first_name" :label="__('First Name')" :value="old('first_name', $adviser?->first_name)" required />
        <flux:input name="middle_name" :label="__('Middle Name')" :value="old('middle_name', $adviser?->middle_name)" />
        <flux:input name="last_name" :label="__('Last Name')" :value="old('last_name', $adviser?->last_name)" required />
        <flux:input name="name_suffix" :label="__('Name Prefix / Suffix')" :value="old('name_suffix', $adviser?->name_suffix)" />
        <flux:select name="sex_at_birth" :label="__('Sex at Birth')" required>
            <option value="Male" @selected(old('sex_at_birth', $adviser?->adviserProfile?->sex_at_birth) === 'Male')>{{ __('Male') }}</option>
            <option value="Female" @selected(old('sex_at_birth', $adviser?->adviserProfile?->sex_at_birth) === 'Female')>{{ __('Female') }}</option>
        </flux:select>
        <flux:input name="rank" :label="__('Rank')" :value="old('rank', $adviser?->adviserProfile?->rank)" required />
        <flux:input name="email" :label="__('Email Address')" :value="old('email', $adviser?->email)" type="email" required />
        <flux:select name="department_id" :label="__('Department')" required>
            <option value="">{{ __('Select a department') }}</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected((string) old('department_id', $adviser?->adviserProfile?->department_id) === (string) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </flux:select>
    </div>

    <flux:textarea name="home_address" :label="__('Home Address')">{{ old('home_address', $adviser?->adviserProfile?->home_address) }}</flux:textarea>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="password" :label="__('Password')" type="password" :required="$adviser === null" />
        <flux:input name="password_confirmation" :label="__('Confirm Password')" type="password" :required="$adviser === null" />
    </div>

    <div class="flex items-center justify-end gap-3">
        <flux:button :href="route('admin.advisers.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        <flux:button type="submit" variant="primary">{{ $adviser ? __('Update Adviser') : __('Save Adviser') }}</flux:button>
    </div>
</form>