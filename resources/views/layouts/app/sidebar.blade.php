<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    @php($user = auth()->user())
    @php($activeRole = $user->activeRole())
    @php($dashboardRoute = route($user->dashboardRouteName()))
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ $dashboardRoute }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="$dashboardRoute" :current="request()->routeIs('admin.dashboard', 'adviser.dashboard', 'student.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if ($activeRole === \App\Enums\UserRole::Administrator)
                    <flux:sidebar.group :heading="__('APAS Manager')" class="grid">
                        <flux:sidebar.item icon="calendar-days" :href="route('admin.academic-terms.index')" :current="request()->routeIs('admin.academic-terms.*')" wire:navigate>
                            {{ __('Academic Terms') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="building-library" :href="route('admin.colleges.index')" :current="request()->routeIs('admin.colleges.*')" wire:navigate>
                            {{ __('Colleges') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="layout-grid" :href="route('admin.subject-progression.index')" :current="request()->routeIs('admin.subject-progression.*')" wire:navigate>
                            {{ __('Subject Progression') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>

                    <flux:sidebar.group :heading="__('User Manager')" class="grid">
                        <flux:sidebar.item icon="academic-cap" :href="route('admin.students.index')" :current="request()->routeIs('admin.students.*')" wire:navigate>
                            {{ __('Students') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('admin.advisers.index')" :current="request()->routeIs('admin.advisers.*')" wire:navigate>
                            {{ __('Advisers') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @elseif ($activeRole === \App\Enums\UserRole::Adviser)
                    <flux:sidebar.group :heading="__('Advising')" class="grid">
                        <flux:sidebar.item icon="academic-cap" :href="route('adviser.students.index')" :current="request()->routeIs('adviser.students.*')" wire:navigate>
                            {{ __('Advisees') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar-square" :href="route('adviser.reports.apap')" :current="request()->routeIs('adviser.reports.apap')" wire:navigate>
                            {{ __('APAP Report') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @elseif ($activeRole === \App\Enums\UserRole::Student)
                    <flux:sidebar.group :heading="__('Student View')" class="grid">
                        <flux:sidebar.item icon="chart-pie" :href="route('student.dashboard')" :current="request()->routeIs('student.dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="academic-cap" :href="route('student.subject-progression')" :current="request()->routeIs('student.subject-progression')" wire:navigate>
                            {{ __('Subject Progression') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="table-cells" :href="route('student.program-of-study')" :current="request()->routeIs('student.program-of-study')" wire:navigate>
                            {{ __('Program of Study') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="book-open" :href="route('student.prospectus')" :current="request()->routeIs('student.prospectus')" wire:navigate>
                            {{ __('Prospectus') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('student.evaluation')" :current="request()->routeIs('student.evaluation')" wire:navigate>
                            {{ __('Evaluation') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    @if (auth()->user()->isAdministrator() && auth()->user()->isAdviser())
                        <div class="px-1 py-1.5 text-xs font-medium uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                            {{ __('Role Switch') }}
                        </div>

                        <flux:menu.radio.group>
                            <form method="POST" action="{{ route('role.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="role" value="administrator">
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="shield-check"
                                    class="w-full cursor-pointer"
                                >
                                    {{ auth()->user()->hasActiveRole(\App\Enums\UserRole::Administrator) ? __('Using Admin View') : __('Switch to Admin View') }}
                                </flux:menu.item>
                            </form>

                            <form method="POST" action="{{ route('role.switch') }}" class="w-full">
                                @csrf
                                <input type="hidden" name="role" value="adviser">
                                <flux:menu.item
                                    as="button"
                                    type="submit"
                                    icon="academic-cap"
                                    class="w-full cursor-pointer"
                                >
                                    {{ auth()->user()->hasActiveRole(\App\Enums\UserRole::Adviser) ? __('Using Adviser View') : __('Switch to Adviser View') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu.radio.group>

                        <flux:menu.separator />
                    @endif

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
