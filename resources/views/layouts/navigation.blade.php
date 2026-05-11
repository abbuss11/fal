@php
    $currentLocale = app()->getLocale();
    $otherLocale = $currentLocale === 'fr' ? 'en' : 'fr';
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-[var(--client-line)] bg-white/85 backdrop-blur-xl">
    <div class="client-shell">
        <div class="flex min-h-[5.2rem] items-center justify-between gap-3 py-2">
            <div class="flex items-center gap-8">
                <a href="{{ route('client.dashboard') }}" class="inline-flex items-center gap-3.5">
                    <x-application-logo class="h-11 w-11" />
                    <div class="leading-tight">
                        <p class="fal-brand-kicker">{{ __('ui.nav.brand_kicker') }}</p>
                        <p class="fal-brand-title">{{ config('app.name', 'FAL PMS') }}</p>
                    </div>
                </a>

                <div class="hidden items-center gap-7 md:flex">
                    <x-nav-link :href="route('client.dashboard')" :active="request()->routeIs('client.dashboard') || request()->routeIs('dashboard')">
                        {{ __('ui.nav.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('client.projects.index')" :active="request()->routeIs('client.projects.*')">
                        {{ __('ui.nav.projects') }}
                    </x-nav-link>
                    <x-nav-link :href="route('client.tasks.index')" :active="request()->routeIs('client.tasks.index')">
                        {{ __('ui.nav.tasks') }}
                    </x-nav-link>
                    <x-nav-link :href="route('client.tasks.calendar')" :active="request()->routeIs('client.tasks.calendar')">
                        {{ __('ui.nav.calendar') }}
                    </x-nav-link>
                    <x-nav-link :href="route('client.timesheets.index')" :active="request()->routeIs('client.timesheets.*')">
                        {{ __('ui.nav.timesheet') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden items-center gap-2 md:flex">
                <a
                    href="{{ route('locale.switch', $otherLocale) }}"
                    class="client-button-muted !px-2.5 !py-1.5 !text-[11px] !font-semibold"
                    title="{{ $otherLocale === 'fr' ? __('ui.locale.switch_to_fr') : __('ui.locale.switch_to_en') }}"
                >
                    {{ strtoupper($otherLocale) }}
                </a>

                <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.1em] text-emerald-700">
                    <i class="h-1.5 w-1.5 rounded-full bg-emerald-500"></i>
                    {{ __('ui.common.live') }}
                </span>

                @if (Auth::user()->isAdmin())
                    <a href="{{ route('admin.portal') }}" class="client-button-muted !px-3 !py-2 !text-xs">{{ __('ui.nav.admin_short') }}</a>
                @endif

                <x-dropdown align="right" width="56" contentClasses="py-2 bg-white">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-xl border border-[var(--client-line)] bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-cyan-300 hover:text-[var(--client-accent)]">
                            <span class="saas-avatar">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </span>
                            <span class="max-w-[110px] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 011.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 pb-2">
                            <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('ui.nav.profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('ui.nav.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--client-line)] bg-white text-slate-600 transition hover:border-cyan-300 hover:text-[var(--client-accent)]">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-[var(--client-line)] bg-white md:hidden">
        <div class="space-y-1 px-4 py-3">
            <x-responsive-nav-link :href="route('client.dashboard')" :active="request()->routeIs('client.dashboard') || request()->routeIs('dashboard')">
                {{ __('ui.nav.dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('client.projects.index')" :active="request()->routeIs('client.projects.*')">
                {{ __('ui.nav.projects') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('client.tasks.index')" :active="request()->routeIs('client.tasks.index')">
                {{ __('ui.nav.tasks') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('client.tasks.calendar')" :active="request()->routeIs('client.tasks.calendar')">
                {{ __('ui.nav.calendar') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('client.timesheets.index')" :active="request()->routeIs('client.timesheets.*')">
                {{ __('ui.nav.timesheet') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                {{ __('ui.nav.profile') }}
            </x-responsive-nav-link>
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.portal')" :active="request()->is('abba*') || request()->routeIs('admin.portal')">
                    {{ __('ui.nav.administration') }}
                </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('locale.switch', $otherLocale)" :active="false">
                {{ strtoupper($otherLocale) }}
            </x-responsive-nav-link>
        </div>

        <div class="border-t border-[var(--client-line)] px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</p>
            <p class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</p>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('ui.nav.logout') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
