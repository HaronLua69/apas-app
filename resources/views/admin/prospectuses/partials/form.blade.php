<div class="space-y-6">
    <flux:input name="title" :label="__('Prospectus Title')" :value="old('title', $prospectus->title ?? '')" required autofocus />

    <flux:select name="major_id" :label="__('Major')">
        <option value="">{{ __('General program prospectus') }}</option>
        @foreach ($course->majors as $major)
            <option value="{{ $major->id }}" @selected((string) old('major_id', $prospectus->major_id ?? '') === (string) $major->id)>{{ $major->name }}</option>
        @endforeach
    </flux:select>

    <flux:textarea name="description" :label="__('Description')">{{ old('description', $prospectus->description ?? '') }}</flux:textarea>
    <flux:checkbox name="is_active" :label="__('Mark as active prospectus for this scope')" :checked="old('is_active', $prospectus->is_active ?? true)" />
</div>