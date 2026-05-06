<div x-data="{ hovered: false }" @mouseleave="hovered = false">
    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
        <div class="relative min-w-max" style="width: {{ $canvasWidth }}px; height: {{ $canvasHeight }}px;">
            <svg class="absolute inset-0 overflow-visible" width="{{ $canvasWidth }}" height="{{ $canvasHeight }}" viewBox="0 0 {{ $canvasWidth }} {{ $canvasHeight }}" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <defs>
                    <marker id="arrowhead-goal" markerWidth="10" markerHeight="10" refX="9" refY="5" orient="auto" markerUnits="strokeWidth">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="currentColor"></path>
                    </marker>
                </defs>

                @foreach ($graphEdges as $edge)
                    <line
                        x1="{{ $edge['x1'] }}"
                        y1="{{ $edge['y1'] }}"
                        x2="{{ $edge['x2'] }}"
                        y2="{{ $edge['y2'] }}"
                        stroke-width="{{ $edge['type'] === 'goal' ? 4 : 3 }}"
                        marker-end="{{ in_array($edge['type'], ['goal', 'prerequisite'], true) ? 'url(#arrowhead-goal)' : '' }}"
                        :class="hovered ? 'text-amber-500 stroke-amber-500' : '{{ $edge['lineClasses'] }}'"
                    />
                @endforeach
            </svg>

            @foreach ($graphEdges as $edge)
                @if ($edge['scopeLabel'] !== '')
                    <div class="pointer-events-none absolute -translate-x-1/2 -translate-y-1/2 rounded-full border bg-white px-3 py-1 text-[11px] font-medium uppercase tracking-[0.18em] shadow-sm dark:bg-zinc-900" style="left: {{ $edge['labelX'] }}px; top: {{ $edge['labelY'] }}px;" :class="hovered ? 'border-amber-300 text-amber-700 dark:border-amber-500/40 dark:text-amber-300' : '{{ $edge['chipClasses'] }}'">
                        {{ $edge['scopeLabel'] }}
                    </div>
                @endif
            @endforeach

            @foreach ($graphNodes as $node)
                <div
                    class="absolute rounded-2xl border border-zinc-200 bg-white/95 px-4 py-3 shadow-sm transition dark:border-zinc-700 dark:bg-zinc-900/95"
                    style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px; width: 208px; min-height: 104px;"
                    @mouseenter="hovered = true"
                    :class="hovered ? 'border-amber-300 ring-2 ring-amber-400/60 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' : ''"
                >
                    <div class="flex h-full flex-col justify-center gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $node['subjectCode'] }}</p>
                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-[11px] font-medium uppercase tracking-[0.2em] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ __(':units units', ['units' => $node['unitsLabel']]) }}</span>
                        </div>

                        @if (($mode ?? 'admin') === 'student')
                            <div class="rounded-2xl bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-950/60">
                                <p class="text-xs font-medium uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ __('Grade') }}</p>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $node['gradeLabel'] !== '' ? $node['gradeLabel'] : '—' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($goal !== null)
                <div
                    class="absolute rounded-[2rem] border border-emerald-200 bg-emerald-50/95 px-5 py-4 text-center shadow-sm transition dark:border-emerald-500/40 dark:bg-emerald-500/10"
                    style="left: {{ $goal['x'] }}px; top: {{ $goal['y'] }}px; width: 256px; min-height: 156px;"
                    @mouseenter="hovered = true"
                    :class="hovered ? 'ring-2 ring-amber-400/60' : ''"
                >
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 3 1 9l11 6 9-4.91V17h2V9L12 3Zm-7.77 9.4V16c0 1.92 3.58 3.5 7.77 3.5s7.77-1.58 7.77-3.5v-3.6L12 16.67 4.23 12.4Z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-xs font-medium uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">{{ __('Graduation Goal') }}</p>
                    <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $goal['title'] }}</p>
                    <p class="mt-2 text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-300">{{ $goal['caption'] }}</p>
                </div>
            @endif
        </div>
    </div>
</div>