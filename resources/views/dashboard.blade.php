<x-layouts.app title="Übersicht">
@if ($contractCount === 0)
    <section class="mx-auto max-w-[560px] py-10 text-center sm:py-16">
        <div class="mx-auto mb-6 flex w-fit gap-2" aria-hidden="true">
            <x-date-tile :date="$today->addDays(9)" urgency="critical" />
            <x-date-tile :date="$today->addDays(31)" urgency="soon" class="mt-3" />
            <x-date-tile :date="$today->addDays(120)" urgency="ok" />
        </div>
        <h1 class="text-[28px] font-semibold leading-tight tracking-tight sm:text-[32px]">Keine Kündigungsfrist mehr verpassen</h1>
        <p class="mx-auto mt-3 max-w-[46ch] text-[16px] leading-relaxed text-ink-soft">Erfasse Krankenkasse, Versicherungen, Fitness und Abos. Der Fristenwächter rechnet den letzten Kündigungstag aus und erinnert dich rechtzeitig per E-Mail und Push.</p>
        <a href="{{ route('contracts.create') }}" class="btn-primary mt-7"><x-icon name="plus" /> Ersten Vertrag erfassen</a>
        <div class="mt-6 flex flex-wrap justify-center gap-2 text-sm">
            @foreach (['kvg-grundversicherung' => 'Krankenkasse', 'fitness-abo' => 'Fitnessabo', 'telekom-abo' => 'Handy-Abo', 'streaming' => 'Streaming'] as $slug => $label)
                <a href="{{ route('contracts.create', ['template' => $slug]) }}" class="rounded-full border border-line bg-sheet px-3 py-1.5 text-ink-soft hover:border-ink-faint hover:text-ink">{{ $label }}</a>
            @endforeach
        </div>
    </section>
@else
    {{-- Die eine Frist, die jetzt zählt --}}
    @if ($next)
        @php $urg = $next->urgency($today); @endphp
        <section class="panel flex flex-col gap-6 p-5 sm:flex-row sm:items-center sm:p-7" aria-labelledby="next-title">
            <x-date-tile :date="$next->send_by" :urgency="$urg" size="lg" />
            <div class="min-w-0 flex-1">
                <p class="text-[15px]"><x-days-left :days="$next->daysUntilSendBy($today)" :urgency="$urg" class="text-[17px]" /></p>
                <h1 id="next-title" class="mt-1 text-[24px] font-semibold leading-tight tracking-tight sm:text-[28px]">{{ $next->name }} bis {{ $next->send_by->translatedFormat('l, j. F') }} kündigen</h1>
                <p class="mt-2 max-w-[62ch] text-[15px] leading-relaxed text-ink-soft">
                    {{ $next->provider }}: Die Kündigung muss am {{ $next->receipt_deadline->format('d.m.Y') }} eingetroffen sein.
                    Sonst läuft der Vertrag über den {{ $next->next_term_end->format('d.m.Y') }} hinaus weiter{{ $next->cost_amount > 0 ? ', für CHF '.\App\Support\Money::format($next->yearlyCost()).' im Jahr' : '' }}.
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('contracts.letter.edit', $next) }}" class="btn-primary"><x-icon name="letter" /> Kündigungsschreiben erstellen</a>
                    <form method="POST" action="{{ route('contracts.keep', $next) }}">
                        @csrf
                        <button class="btn-secondary">Behalten</button>
                    </form>
                    <a href="{{ route('contracts.show', $next) }}" class="btn-quiet">Details</a>
                </div>
            </div>
        </section>
    @else
        <section class="panel p-6 sm:p-7">
            <h1 class="text-[24px] font-semibold tracking-tight">In den nächsten 90 Tagen ist keine Kündigung fällig.</h1>
            <p class="mt-2 text-[15px] text-ink-soft">Erinnerungen kommen automatisch, sobald eine Frist näher rückt.</p>
        </section>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-8">
            @if ($upcoming->isNotEmpty())
                <section aria-labelledby="upcoming-title">
                    <h2 id="upcoming-title" class="section-title mb-3">Danach in den nächsten 90 Tagen</h2>
                    <ul class="panel divide-y divide-line">
                        @foreach ($upcoming as $c)
                            @include('contracts._row', ['c' => $c])
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($awaitingConfirmation->isNotEmpty())
                <section aria-labelledby="confirm-title">
                    <h2 id="confirm-title" class="section-title mb-1">Bestätigung ausstehend</h2>
                    <p class="mb-3 text-[14px] text-ink-soft">Gekündigt, aber noch keine schriftliche Bestätigung vermerkt.</p>
                    <ul class="panel divide-y divide-line">
                        @foreach ($awaitingConfirmation as $c)
                            <li class="flex items-center gap-4 px-4 py-3.5">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('contracts.show', $c) }}" class="font-medium hover:underline">{{ $c->name }}</a>
                                    <p class="text-[14px] text-ink-soft">{{ $c->provider }}, versendet am {{ $c->cancellation_sent_on?->format('d.m.Y') }}</p>
                                </div>
                                <form method="POST" action="{{ route('contracts.status', $c) }}">
                                    @csrf
                                    <input type="hidden" name="action" value="confirmed">
                                    <button class="btn-secondary px-3 py-1.5 text-sm"><x-icon name="check" class="size-4" /> Bestätigt</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($upcoming->isEmpty() && $awaitingConfirmation->isEmpty())
                <a href="{{ route('contracts.index') }}" class="inline-flex items-center gap-1 text-[15px] font-medium text-accent hover:underline">Alle {{ $contractCount }} Verträge ansehen <x-icon name="chevron" class="size-4" /></a>
            @endif
        </div>

        <aside class="space-y-6">
            <section class="panel p-5" aria-labelledby="cost-title">
                <h2 id="cost-title" class="text-[15px] font-medium text-ink-soft">Fixkosten aktiver Verträge</h2>
                <p class="mt-1 text-[30px] font-semibold tracking-tight">CHF @chf($stats['monthly'])</p>
                <p class="text-[14px] text-ink-soft">pro Monat, CHF @chf($stats['yearly']) im Jahr</p>

                @if ($stats['by_category']->isNotEmpty())
                    @php $palette = ['bg-accent', 'bg-[#4f8f9a]', 'bg-[#8fb8b6]', 'bg-[#b9ccd1]', 'bg-line-strong']; @endphp
                    <div class="mt-5 flex h-2.5 overflow-hidden rounded-full bg-paper" role="img" aria-label="Kosten nach Kategorie">
                        @foreach ($stats['by_category']->take(5) as $i => $cat)
                            <span class="{{ $palette[$i] }}" style="width: {{ round($cat['share'] * 100, 1) }}%"></span>
                        @endforeach
                    </div>
                    <ul class="mt-4 space-y-2 text-[14px]">
                        @foreach ($stats['by_category']->take(5) as $i => $cat)
                            <li class="flex items-center gap-2">
                                <span class="size-2.5 rounded-full {{ $palette[$i] }}"></span>
                                <span class="flex-1 truncate">{{ $cat['name'] }}</span>
                                <span class="text-ink-soft">@chf($cat['monthly'])</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a href="{{ route('savings') }}" class="mt-5 inline-flex items-center gap-1 text-[14px] font-medium text-accent hover:underline">Sparpotenzial ansehen <x-icon name="chevron" class="size-4" /></a>
            </section>

            @if ($expiring->isNotEmpty())
                <section class="panel p-5" aria-labelledby="exp-title">
                    <h2 id="exp-title" class="text-[15px] font-medium text-ink-soft">Läuft bald ab</h2>
                    <ul class="mt-3 space-y-3">
                        @foreach ($expiring as $e)
                            <li class="flex items-center gap-3">
                                <x-date-tile :date="$e->expires_at" :urgency="$e->urgency($today)" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-[15px] font-medium">{{ $e->name }}</p>
                                    <p class="text-[13px] text-ink-soft">{{ $e->type->label() }}, <x-days-left :days="$e->daysLeft($today)" :urgency="$e->urgency($today)" class="font-normal" /></p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endif
</x-layouts.app>
