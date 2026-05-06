@php($previewStart = (int) old('academic_year_start', $academicTerm?->academic_year_start))
@php($previewEnd = $previewStart > 0 ? $previewStart + 1 : null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="academic_year_start" :label="__('Academic Year (From Year)')" :value="old('academic_year_start', $academicTerm?->academic_year_start)" type="number" min="2000" max="9998" required autofocus />

        <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
            @if ($previewEnd !== null)
                {{ __('Computed Academic Year: A.Y. :start-:end', ['start' => $previewStart, 'end' => $previewEnd]) }}
            @else
                {{ __('Enter the starting year and the app will reckon the academic year automatically.') }}
            @endif
        </div>
    </div>

    <flux:select name="term_name" :label="__('Term')" required>
        <option value="">{{ __('Select a term') }}</option>
        @foreach (\App\Models\AcademicTerm::TERM_OPTIONS as $termOption)
            <option value="{{ $termOption }}" @selected(old('term_name', $academicTerm?->term_name) === $termOption)>{{ $termOption }}</option>
        @endforeach
    </flux:select>

    <div class="grid gap-6 md:grid-cols-2">
        <flux:input name="date_from" :label="__('Date From')" :value="old('date_from', $academicTerm?->date_from?->format('Y-m-d'))" type="date" required />
        <flux:input name="date_to" :label="__('Date To')" :value="old('date_to', $academicTerm?->date_to?->format('Y-m-d'))" type="date" required />
    </div>

    <div class="flex items-center justify-end gap-3">
        <flux:button :href="route('admin.academic-terms.index')" variant="ghost" wire:navigate>
            {{ __('Cancel') }}
        </flux:button>
        <flux:button type="submit" variant="primary">{{ $submitLabel }}</flux:button>
    </div>
</form>