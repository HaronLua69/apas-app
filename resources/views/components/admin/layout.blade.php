<x-layouts::app :title="$title ?? __('Administration')">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4 max-md:flex-col max-md:items-start">
                <div>
                    <flux:heading>{{ $heading ?? '' }}</flux:heading>
                    <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>
                </div>

                @if (isset($backHref))
                    <flux:button :href="$backHref" variant="ghost" wire:navigate>
                        {{ $backLabel ?? __('Back') }}
                    </flux:button>
                @endif
            </div>

            @if (session('status'))
                <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-300">
                    <p class="font-medium">{{ __('Please correct the highlighted fields.') }}</p>
                    <ul class="mt-2 list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
    </div>
</x-layouts::app>