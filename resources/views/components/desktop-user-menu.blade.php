@php($user = auth()->user())

<flux:dropdown position="bottom" align="start">
    <flux:sidebar.profile
        :name="$user->name"
        :initials="$user->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="$user->name"
                :initials="$user->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                <flux:text class="truncate">{{ $user->email }}</flux:text>
            </div>
        </div>
        <flux:menu.separator />

        @if ($user->isAdministrator() && $user->isAdviser())
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
                        {{ $user->hasActiveRole(\App\Enums\UserRole::Administrator) ? __('Using Admin View') : __('Switch to Admin View') }}
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
                        {{ $user->hasActiveRole(\App\Enums\UserRole::Adviser) ? __('Using Adviser View') : __('Switch to Adviser View') }}
                    </flux:menu.item>
                </form>
            </flux:menu.radio.group>

            <flux:menu.separator />
        @endif

        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                {{ __('Settings') }}
            </flux:menu.item>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
