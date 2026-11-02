@props(['title' => null, 'back' => null])
<!DOCTYPE html>
<html lang="de-CH">
<head>
    <x-head :title="$title" />
</head>
<body class="min-h-dvh">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-sheet focus:px-4 focus:py-2">Zum Inhalt</a>

<div class="lg:grid lg:min-h-dvh lg:grid-cols-[248px_1fr]">
    {{-- Seitenleiste ab Desktop --}}
    <aside class="hidden border-r border-line bg-sheet lg:flex lg:flex-col">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-6 pt-7 pb-8">
            <img src="/icons/icon.svg" alt="" class="size-8">
            <span class="text-[17px] font-semibold tracking-tight">Fristenwächter</span>
        </a>
        <nav class="flex flex-col gap-1 px-3" aria-label="Hauptnavigation">
            <x-nav-link :href="route('dashboard')" icon="overview" :active="request()->routeIs('dashboard')">Übersicht</x-nav-link>
            <x-nav-link :href="route('contracts.index')" icon="contracts" :active="request()->routeIs('contracts.*')">Verträge</x-nav-link>
            <x-nav-link :href="route('expiry.index')" icon="expiry" :active="request()->routeIs('expiry.*')">Ablaufdaten</x-nav-link>
            <x-nav-link :href="route('savings')" icon="savings" :active="request()->routeIs('savings')">Sparpotenzial</x-nav-link>
            <x-nav-link :href="route('settings.edit')" icon="settings" :active="request()->routeIs('settings.*')">Einstellungen</x-nav-link>
        </nav>
        <div class="mt-6 px-6">
            <a href="{{ route('contracts.create') }}" class="btn-primary w-full"><x-icon name="plus" /> Vertrag erfassen</a>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-auto px-3 pb-6">
            @csrf
            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-[15px] text-ink-faint hover:text-ink">
                <x-icon name="logout" /> Abmelden
            </button>
        </form>
    </aside>

    <div class="flex min-w-0 flex-col pb-[calc(72px+env(safe-area-inset-bottom))] lg:pb-0">
        {{-- Kopfzeile mobil --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-line bg-sheet/95 px-4 py-3 backdrop-blur lg:hidden">
            @if ($back)
                <a href="{{ $back }}" class="-ml-1 rounded-lg p-1 text-ink-soft" aria-label="Zurück"><x-icon name="back" class="size-6" /></a>
            @else
                <img src="/icons/icon.svg" alt="" class="size-7">
            @endif
            <span class="truncate text-[16px] font-semibold">{{ $title ?? 'Fristenwächter' }}</span>
            <a href="{{ route('contracts.create') }}" class="ml-auto rounded-full bg-accent p-2 text-white dark:text-paper" aria-label="Vertrag erfassen"><x-icon name="plus" /></a>
        </header>

        <main id="main" class="mx-auto w-full max-w-[1120px] px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
            @if (session('status'))
                <div class="panel mb-6 flex items-start gap-3 border-accent/30 bg-accent-wash px-4 py-3 text-[15px] text-ink" role="status">
                    <x-icon name="check" class="mt-0.5 size-5 shrink-0 text-accent" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

{{-- Tab-Leiste mobil --}}
<nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-sheet/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden" aria-label="Hauptnavigation">
    <x-nav-link variant="tab" :href="route('dashboard')" icon="overview" :active="request()->routeIs('dashboard')">Übersicht</x-nav-link>
    <x-nav-link variant="tab" :href="route('contracts.index')" icon="contracts" :active="request()->routeIs('contracts.*')">Verträge</x-nav-link>
    <x-nav-link variant="tab" :href="route('expiry.index')" icon="expiry" :active="request()->routeIs('expiry.*')">Ablauf</x-nav-link>
    <x-nav-link variant="tab" :href="route('savings')" icon="savings" :active="request()->routeIs('savings')">Sparen</x-nav-link>
    <x-nav-link variant="tab" :href="route('settings.edit')" icon="settings" :active="request()->routeIs('settings.*')">Konto</x-nav-link>
</nav>
</body>
</html>
