<x-layouts.app title="Sparpotenzial">
    <h1 class="text-[28px] font-semibold tracking-tight">Sparpotenzial</h1>

    @if ($s['count'] === 0)
        <div class="panel mt-6 px-6 py-12 text-center text-[15px] text-ink-soft">Sobald Verträge mit Kosten erfasst sind, siehst du hier, wo das Geld hingeht.</div>
    @else
        <p class="mt-2 text-[16px] text-ink-soft">{{ $s['count'] }} laufende Verträge kosten <strong class="font-semibold text-ink">CHF @chf($s['yearly'])</strong> im Jahr, das sind CHF @chf($s['monthly']) pro Monat.</p>

        <div class="mt-8 grid gap-8 lg:grid-cols-2">
            {{-- Zuerst die handfesten Hinweise --}}
            <section class="space-y-6 lg:order-2" aria-label="Hinweise">
                @if ($s['unused']->isNotEmpty())
                    <div class="panel border-due-soon/40 p-5">
                        <h2 class="section-title">Länger nicht genutzt</h2>
                        <p class="mt-1 text-[14px] text-ink-soft">Seit über {{ config('fristwatcher.unused_after_days') }} Tagen kein «genutzt» vermerkt.</p>
                        <ul class="mt-4 divide-y divide-line">
                            @foreach ($s['unused'] as $c)
                                <li class="flex items-center gap-3 py-3">
                                    <a href="{{ route('contracts.show', $c) }}" class="min-w-0 flex-1">
                                        <span class="block truncate font-medium hover:underline">{{ $c->name }}</span>
                                        <span class="text-[13px] text-ink-soft">CHF @chf($c->yearlyCost()) im Jahr</span>
                                    </a>
                                    <form method="POST" action="{{ route('contracts.used', $c) }}">
                                        @csrf
                                        <button class="btn-quiet px-2 py-1 text-[13px]">Doch genutzt</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($s['increases']->isNotEmpty())
                    <div class="panel p-5">
                        <h2 class="section-title">Teurer geworden</h2>
                        <p class="mt-1 text-[14px] text-ink-soft">Preiserhöhungen der letzten 12 Monate.</p>
                        <ul class="mt-4 divide-y divide-line">
                            @foreach ($s['increases'] as $row)
                                <li class="flex items-center gap-3 py-3">
                                    <a href="{{ route('contracts.show', $row['contract']) }}" class="min-w-0 flex-1 truncate font-medium hover:underline">{{ $row['contract']->name }}</a>
                                    <span class="text-[14px] text-due-critical">+ CHF @chf($row['yearly_delta']) / Jahr</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($s['compare']->isNotEmpty())
                    <div class="panel p-5">
                        <h2 class="section-title">Vergleichen</h2>
                        <ul class="mt-3 space-y-2">
                            @foreach ($s['compare'] as $row)
                                <li class="text-[15px]">
                                    {{ $row['contract']->name }}:
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="font-medium text-accent hover:underline">{{ parse_url($row['url'], PHP_URL_HOST) }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-[13px] text-ink-soft">Für die Krankenkasse ist Priminfo der offizielle Prämienvergleich des Bundes.</p>
                    </div>
                @endif

                @if ($s['unused']->isEmpty() && $s['increases']->isEmpty())
                    <div class="panel p-5 text-[15px] text-ink-soft">Keine ungenutzten Abos und keine Preiserhöhungen im letzten Jahr. Für Abos wie Fitness oder Streaming im Vertrag ab und zu «Heute genutzt» tippen, dann fällt Leerlauf hier auf.</div>
                @endif
            </section>

            <section class="space-y-6 lg:order-1" aria-label="Kostenverteilung">
                <div class="panel p-5">
                    <h2 class="section-title">Nach Kategorie</h2>
                    <ul class="mt-4 space-y-3.5">
                        @foreach ($s['by_category'] as $cat)
                            <li>
                                <div class="flex items-baseline justify-between gap-3 text-[15px]">
                                    <span class="truncate">{{ $cat['name'] }}</span>
                                    <span class="shrink-0 text-ink-soft">CHF @chf($cat['monthly']) / Mt.</span>
                                </div>
                                <div class="mt-1.5 h-2 rounded-full bg-paper">
                                    <div class="h-2 rounded-full bg-accent" style="width: {{ max(1, round($cat['share'] * 100, 1)) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="panel p-5">
                    <h2 class="section-title">Die teuersten Verträge</h2>
                    <ol class="mt-3 divide-y divide-line">
                        @foreach ($s['top'] as $i => $c)
                            <li class="flex items-center gap-3 py-3">
                                <span class="w-5 text-[14px] text-ink-faint">{{ $i + 1 }}</span>
                                <a href="{{ route('contracts.show', $c) }}" class="min-w-0 flex-1 truncate font-medium hover:underline">{{ $c->name }}</a>
                                <span class="text-[14px] text-ink-soft">CHF @chf($c->yearlyCost()) / Jahr</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        </div>
    @endif
</x-layouts.app>
