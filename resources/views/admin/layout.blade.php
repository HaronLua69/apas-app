<x-layouts::app :title="$title ?? __('Administration')">
    <div class="flex items-start gap-6 max-lg:flex-col">
        <aside class="w-full lg:w-72">
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('APAS Manager') }}</p>
                    <flux:navlist class="mt-3">
                        <flux:navlist.item :href="route('admin.academic-terms.index')" :current="request()->routeIs('admin.academic-terms.*')" wire:navigate>
                            {{ __('Academic Terms') }}
                        </flux:navlist.item>
                        <flux:navlist.item :href="route('admin.colleges.index')" :current="request()->routeIs('admin.colleges.*')" wire:navigate>
                            {{ __('Colleges') }}
                        </flux:navlist.item>
                    </flux:navlist>
                </div>

                <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ __('User Manager') }}</p>
                    <flux:navlist class="mt-3">
                        <flux:navlist.item :href="route('admin.students.index')" :current="request()->routeIs('admin.students.*')" wire:navigate>
                            {{ __('Students') }}
                        </flux:navlist.item>
                        <flux:navlist.item :href="route('admin.advisers.index')" :current="request()->routeIs('admin.advisers.*')" wire:navigate>
                            {{ __('Advisers') }}
                        </flux:navlist.item>
                    </flux:navlist>
                </div>
            </div>
        </aside>

        <div class="flex-1 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading>{{ $heading ?? '' }}</flux:heading>
            <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

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
    </div>
</x-layouts::app>