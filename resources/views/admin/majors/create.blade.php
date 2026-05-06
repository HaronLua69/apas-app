<x-admin.layout :title="__('Add Major')" :heading="__('Add Major')" :subheading="__('Create a major under '.$course->name)" :backHref="route('admin.courses.show', $course)" :backLabel="__('Back to Program')">
    <form method="POST" action="{{ route('admin.courses.majors.store', $course) }}" class="space-y-6">
        @csrf
        <flux:input name="name" :label="__('Major Name')" :value="old('name')" required autofocus />
        <flux:textarea name="description" :label="__('Description')">{{ old('description') }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.courses.show', $course)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Major') }}</flux:button>
        </div>
    </form>
</x-admin.layout>