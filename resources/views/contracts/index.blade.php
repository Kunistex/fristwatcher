<x-layouts.app title="Verträge">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-[28px] font-semibold tracking-tight">Verträge</h1>
        <a href="{{ route('contracts.create') }}" class="btn-primary hidden lg:inline-flex"><x-icon name="plus" /> Vertrag erfassen</a>
    </div>

    <form method="GET" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="flex gap-1 overflow-x-auto rounded-lg border border-line bg-sheet p-1" role="tablist">
            @foreach (['open' => 'Laufend', 'cancelled' => 'Gekündigt', 'ended' => 'Beendet', 'all' => 'Alle'] as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['filter' => $key]) }}" role="tab" aria-selected="{{ $filter === $key ? 'true' : 'false' }}"
                   class="whitespace-nowrap rounded-md px-3 py-1.5 text-[14px] font-medium {{ $filter === $key ? 'bg-accent-wash text-accent' : 'text-ink-soft hover:text-ink' }}">{{ $label }}</a>
            @endforeach
        </div>
        <input type="hidden" name="filter" value="{{ $filter }}">
        <select name="category" class="field sm:w-52" onchange="this.form.submit()" aria-label="Kategorie">
            <option value="">Alle Kategorien</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected($category === $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </select>
        <label class="relative sm:ml-auto sm:w-64">
            <span class="sr-only">Suchen</span>
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-faint" />
            <input type="search" name="q" value="{{ $q }}" placeholder="Name, Anbieter, Nummer" class="field pl-9">
        </label>
    </form>

    @if ($contracts->isEmpty())
        <div class="panel px-6 py-12 text-center">
            @if ($q !== '' || $category)
                <p class="text-[16px] font-medium">Keine Treffer für diese Suche.</p>
                <a href="{{ route('contracts.index', ['filter' => $filter]) }}" class="mt-3 inline-block text-[15px] text-accent hover:underline">Filter zurücksetzen</a>
            @elseif ($filter === 'open')
                <p class="text-[16px] font-medium">Noch keine laufenden Verträge.</p>
                <a href="{{ route('contracts.create') }}" class="btn-primary mt-5"><x-icon name="plus" /> Vertrag erfassen</a>
            @else
                <p class="text-[16px] text-ink-soft">Hier ist nichts.</p>
            @endif
        </div>
    @else
        <ul class="panel divide-y divide-line">
            @foreach ($contracts as $c)
                @include('contracts._row', ['c' => $c])
            @endforeach
        </ul>
        <p class="mt-3 text-[13px] text-ink-faint">{{ $contracts->count() }} {{ $contracts->count() === 1 ? 'Vertrag' : 'Verträge' }}, sortiert nach Versandtag.</p>
    @endif
</x-layouts.app>
