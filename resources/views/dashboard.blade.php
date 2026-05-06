@php
    $user = auth()->user();

    $dashboard = match ($user->activeRole()->value) {
        'administrator' => [
            'title' => 'Administrator Dashboard',
            'summary' => 'Manage APAS initialization, account provisioning, and academic structures from one workspace.',
            'stats' => [
                ['label' => 'Priority', 'value' => 'Initialize colleges, departments, courses, and subjects'],
                ['label' => 'Access', 'value' => 'Administrator-only routes and system setup'],
                ['label' => 'Next Build', 'value' => 'APAS Manager and User Manager CRUD screens'],
            ],
            'focus' => [
                'Set up academic structures before adviser and student workflows go live.',
                'Provision student and adviser accounts through the User Manager.',
                'Maintain the active prospectus and evaluation template per course or major.',
            ],
        ],
        'adviser' => [
            'title' => 'Adviser Dashboard',
            'summary' => 'Track advisees, review academic standing, and prepare advising actions by course, major, and year level.',
            'stats' => [
                ['label' => 'Priority', 'value' => 'Review advisee progress and prepare term advising'],
                ['label' => 'Access', 'value' => 'Adviser routes scoped to assigned course and year level'],
                ['label' => 'Next Build', 'value' => 'Advisee list, grade entry, and graduating eligibility tools'],
            ],
            'focus' => [
                'Monitor GPA, CGPA, and earned units for assigned students.',
                'Record or update grades for advisees as academic records change.',
                'Flag graduating candidates after the 90 percent earned-unit threshold is met.',
            ],
        ],
        default => [
            'title' => 'Student Dashboard',
            'summary' => 'Review your academic standing, compare completed work against the prospectus, and prepare advising records.',
            'stats' => [
                ['label' => 'Priority', 'value' => 'Track progress against your program of study'],
                ['label' => 'Access', 'value' => 'Student self-service view of APAS records'],
                ['label' => 'Next Build', 'value' => 'Program of study, evaluation, prospectus, and grade entry pages'],
            ],
            'focus' => [
                'Review completed and pending subjects across all year levels and terms.',
                'Compare actual enrolled subjects against the active prospectus.',
                'Prepare grade records for adviser verification when needed.',
            ],
        ],
    };
@endphp

<x-layouts::app :title="__($dashboard['title'])">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-50 px-6 py-8 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm font-medium uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">{{ $user->roleLabel() }}</p>
            <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <h1 class="text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">{{ $dashboard['title'] }}</h1>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $dashboard['summary'] }}</p>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-950/60">
                    <p class="text-zinc-500 dark:text-zinc-400">Signed in as</p>
                    <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $user->fullName() }}</p>
                    <p class="text-zinc-500 dark:text-zinc-400">{{ $user->id_number }} · {{ '@'.$user->username }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            @foreach ($dashboard['stats'] as $stat)
                <article class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-base font-semibold leading-6 text-zinc-950 dark:text-zinc-50">{{ $stat['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Current Focus</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($dashboard['focus'] as $item)
                        <div class="rounded-2xl border border-dashed border-zinc-200 px-4 py-3 text-sm leading-6 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Role Access</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dashboard Route</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ route($user->dashboardRouteName(), absolute: false) }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Account Email</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-zinc-100">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Settings Access</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-zinc-100">Profile, security, and appearance remain available from the account menu.</dd>
                    </div>
                </dl>
            </article>
        </section>
    </div>
</x-layouts::app>
