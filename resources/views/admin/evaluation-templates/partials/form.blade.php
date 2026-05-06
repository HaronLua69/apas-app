<div class="space-y-6">
    <flux:input name="title" :label="__('Template Title')" :value="old('title', $evaluationTemplate->title ?? '')" required autofocus />

    <flux:select name="major_id" :label="__('Major')">
        <option value="">{{ __('General program template') }}</option>
        @foreach ($course->majors as $major)
            <option value="{{ $major->id }}" @selected((string) old('major_id', $evaluationTemplate->major_id ?? '') === (string) $major->id)>{{ $major->name }}</option>
        @endforeach
    </flux:select>

    <flux:textarea name="description" :label="__('Description')">{{ old('description', $evaluationTemplate->description ?? '') }}</flux:textarea>
</div>