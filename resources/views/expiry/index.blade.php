<x-layouts.app title="Ablaufdaten">
    <h1 class="text-[28px] font-semibold tracking-tight">Ablaufdaten</h1>
    <p class="mt-2 max-w-[62ch] text-[15px] text-ink-soft">Pass, ID, Führerausweis, Garantien. Für Ausweise erinnert die App ein halbes Jahr vorher, weil Termine beim Passbüro oft Wochen brauchen.</p>

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
        <section aria-label="Einträge">
            @if ($items->isEmpty())
                <div class="panel px-6 py-12 text-center text-[15px] text-ink-soft">Noch nichts erfasst. Rechts den ersten Eintrag anlegen.</div>
            @else
                <ul class="panel divide-y divide-line">
                    @foreach ($items as $item)
                        @php $urg = $item->urgency($today); @endphp
                        <li class="flex items-center gap-4 px-4 py-3.5">
                            <x-date-tile :date="$item->expires_at" :urgency="$urg" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[16px] font-medium">{{ $item->name }}</p>
                                <p class="text-[14px] text-ink-soft">{{ $item->type->label() }}{{ $item->reference_last4 ? ', …'.$item->reference_last4 : '' }}, gültig bis {{ $item->expires_at->format('d.m.Y') }}</p>
                            </div>
                            <x-days-left :days="$item->daysLeft($today)" :urgency="$urg" class="hidden text-[14px] sm:inline" />
                            <a href="{{ route('expiry.edit', $item) }}" class="p-1 text-ink-faint hover:text-ink" aria-label="{{ $item->name }} bearbeiten"><x-icon name="edit" class="size-4" /></a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside>
            <form method="POST" action="{{ route('expiry.store') }}" class="panel space-y-4 p-5">
                @csrf
                <h2 class="section-title">Neues Ablaufdatum</h2>
                @include('expiry._fields', ['item' => null])
                <button class="btn-primary w-full">Speichern</button>
            </form>
        </aside>
    </div>
</x-layouts.app>
