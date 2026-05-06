<x-admin.layout :title="__('Add Program')" :heading="__('Add Program')" :subheading="__('Create a course under '.$department->name)" :backHref="route('admin.departments.show', ['department' => $department, 'section' => 'programs'])" :backLabel="__('Back to Department')">
    <form method="POST" action="{{ route('admin.departments.courses.store', $department) }}" class="space-y-6">
        @csrf
        <flux:input name="name" :label="__('Program Name')" :value="old('name')" required autofocus />
        <flux:input name="abbreviation" :label="__('Abbreviation')" :value="old('abbreviation')" />
        <flux:textarea name="description" :label="__('Description')">{{ old('description') }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.departments.show', ['department' => $department, 'section' => 'programs'])" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Save Program') }}</flux:button>
        </div>
    </form>
</x-admin.layout>