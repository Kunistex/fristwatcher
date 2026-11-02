<x-layouts.app title="Vertrag erfassen" :back="route('contracts.index')">
    <h1 class="text-[28px] font-semibold tracking-tight">Was für ein Vertrag?</h1>
    <p class="mt-2 max-w-[60ch] text-[15px] text-ink-soft">Die Vorlage füllt typische Fristen für die Schweiz vor. Du kannst alles anpassen; massgebend ist immer dein Vertrag.</p>

    <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($templates as $t)
            <li>
                <a href="{{ route('contracts.create', ['template' => $t->slug]) }}" class="panel flex h-full gap-4 p-4 hover:border-accent/50 focus-visible:border-accent">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-accent-wash text-accent">
                        <x-icon :name="$t->category?->icon ?? 'folder'" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[15px] font-semibold leading-snug">{{ $t->name }}</span>
                        <span class="mt-1 block text-[13.5px] leading-snug text-ink-soft">
                            @php $d = $t->defaults; @endphp
                            @if (($d['notice_anchor'] ?? '') === 'fixed_dates')
                                Kündbar auf {{ \App\Support\FixedDates::humanize($d['fixed_end_dates'] ?? []) }}, {{ $d['notice_value'] }} {{ \App\Enums\NoticeUnit::from($d['notice_unit'])->label($d['notice_value']) }} Frist
                            @elseif (($d['notice_value'] ?? 0) === 0)
                                Bis zur nächsten Abrechnung kündbar
                            @else
                                {{ $d['min_term_months'] ?? '–' }} Mt. Laufzeit, {{ $d['notice_value'] }} {{ \App\Enums\NoticeUnit::from($d['notice_unit'])->label($d['notice_value']) }} Frist
                            @endif
                        </span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</x-layouts.app>
