<x-admin.layout :title="__('Edit Program')" :heading="__('Edit Program')" :subheading="__('Update the details for this program.')" :backHref="route('admin.courses.show', $course)" :backLabel="__('Back to Program')">
    <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <flux:input name="name" :label="__('Program Name')" :value="old('name', $course->name)" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation', $course->abbreviation)" />
        <flux:textarea name="description" :label="__('Description')">{{ old('description', $course->description) }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.courses.show', $course)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Update Program') }}</flux:button>
        </div>
    </form>
</x-admin.layout>