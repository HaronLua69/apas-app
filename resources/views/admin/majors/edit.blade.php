<x-admin.layout :title="__('Edit Major')" :heading="__('Edit Major')" :subheading="__('Update the details for this major.')" :backHref="route('admin.courses.show', $major->course)" :backLabel="__('Back to Program')">
    <form method="POST" action="{{ route('admin.majors.update', $major) }}" class="space-y-6">
        @csrf
        @method('PUT')
        <flux:input name="name" :label="__('Major Name')" :value="old('name', $major->name)" required autofocus />
        <flux:textarea name="description" :label="__('Description')">{{ old('description', $major->description) }}</flux:textarea>

        <div class="flex items-center justify-end gap-3">
            <flux:button :href="route('admin.courses.show', $major->course)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary">{{ __('Update Major') }}</flux:button>
        </div>
    </form>
</x-admin.layout>