@php
    $c = $contract;
    $urg = $c->urgency($today);
    $watching = $c->status->watchesDeadline();
@endphp
<x-layouts.app :title="$c->name" :back="route('contracts.index')">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('contracts.index') }}" class="mb-2 hidden items-center gap-1 text-[14px] text-ink-soft hover:text-ink lg:inline-flex"><x-icon name="back" class="size-4" /> Verträge</a>
            <h1 class="text-[28px] font-semibold leading-tight tracking-tight">{{ $c->name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[15px] text-ink-soft">
                <span>{{ $c->provider }}</span>
                <x-status-badge :status="$c->status" />
            </p>
        </div>
        <a href="{{ route('contracts.edit', $c) }}" class="btn-secondary"><x-icon name="edit" class="size-4" /> Bearbeiten</a>
    </div>

    {{-- Frist --}}
    @if ($watching && $c->send_by)
        <section class="panel p-5 sm:p-7" aria-labelledby="deadline-title">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                <x-date-tile :date="$c->send_by" :urgency="$urg" size="lg" />
                <div class="flex-1">
                    <h2 id="deadline-title" class="text-[22px] font-semibold leading-tight tracking-tight">Kündigung bis {{ $c->send_by->translatedFormat('l, j. F Y') }} versenden</h2>
                    <p class="mt-1 text-[15px]"><x-days-left :days="$c->daysUntilSendBy($today)" :urgency="$urg" /></p>
                    @if ($deadline->note)
                        <p class="mt-2 text-[14px] text-due-critical">{{ $deadline->note }}</p>
                    @endif
                </div>
            </div>

            {{-- Ablauf in drei Schritten --}}
            <ol class="mt-7 grid gap-4 border-t border-line pt-6 sm:grid-cols-3">
                <li class="relative pl-5">
                    <span class="absolute top-1.5 left-0 size-2.5 rounded-full bg-accent"></span>
                    <p class="text-[13px] text-ink-soft">Versenden bis</p>
                    <p class="text-[16px] font-semibold">{{ $c->send_by->format('d.m.Y') }}</p>
                </li>
                <li class="relative pl-5">
                    <span class="absolute top-1.5 left-0 size-2.5 rounded-full border-2 border-accent bg-sheet"></span>
                    <p class="text-[13px] text-ink-soft">Eingang beim Anbieter bis</p>
                    <p class="text-[16px] font-semibold">{{ $c->receipt_deadline->format('d.m.Y') }}</p>
                </li>
                <li class="relative pl-5">
                    <span class="absolute top-1.5 left-0 size-2.5 rounded-full border-2 border-line-strong bg-sheet"></span>
                    <p class="text-[13px] text-ink-soft">Vertrag endet dann am</p>
                    <p class="text-[16px] font-semibold">{{ $c->next_term_end->format('d.m.Y') }}</p>
                </li>
            </ol>

            <div class="mt-7 flex flex-wrap gap-2">
                <a href="{{ route('contracts.letter.edit', $c) }}" class="btn-primary"><x-icon name="letter" /> Kündigungsschreiben erstellen</a>
                <details class="group relative">
                    <summary class="btn-secondary cursor-pointer list-none">Als versendet markieren</summary>
                    <form method="POST" action="{{ route('contracts.status', $c) }}" class="panel absolute z-10 mt-2 w-72 space-y-3 p-4">
                        @csrf
                        <input type="hidden" name="action" value="sent">
                        <div>
                            <label for="sent_on" class="label">Versendet am</label>
                            <input id="sent_on" type="date" name="sent_on" value="{{ $today->format('Y-m-d') }}" class="field">
                        </div>
                        <div>
                            <label for="effective_on" class="label">Wirksam per</label>
                            <input id="effective_on" type="date" name="effective_on" value="{{ $c->next_term_end->format('Y-m-d') }}" class="field">
                        </div>
                        <button class="btn-primary w-full">Speichern</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('contracts.keep', $c) }}">
                    @csrf
                    <button class="btn-quiet">Behalten, diesmal nicht erinnern</button>
                </form>
            </div>
        </section>
    @elseif ($c->status->value === 'cancelled_unconfirmed')
        <section class="panel border-due-soon/40 p-5 sm:p-7">
            <h2 class="text-[20px] font-semibold tracking-tight">Gekündigt, Bestätigung noch offen</h2>
            <p class="mt-2 text-[15px] text-ink-soft">Versendet am {{ $c->cancellation_sent_on?->format('d.m.Y') }}, wirksam per {{ $c->cancellation_effective_on?->format('d.m.Y') ?? 'unbekannt' }}. Ohne schriftliche Bestätigung lohnt es sich nachzufragen.</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('contracts.status', $c) }}">
                    @csrf
                    <input type="hidden" name="action" value="confirmed">
                    <button class="btn-primary"><x-icon name="check" /> Bestätigung erhalten</button>
                </form>
                <form method="POST" action="{{ route('contracts.status', $c) }}">
                    @csrf
                    <input type="hidden" name="action" value="reactivate">
                    <button class="btn-quiet">Kündigung zurückgezogen</button>
                </form>
            </div>
        </section>
    @elseif ($c->status->value === 'cancelled_confirmed')
        <section class="panel border-due-ok/40 p-5 sm:p-7">
            <h2 class="text-[20px] font-semibold tracking-tight">Gekündigt und bestätigt</h2>
            <p class="mt-2 text-[15px] text-ink-soft">Der Vertrag endet am {{ $c->cancellation_effective_on?->format('d.m.Y') ?? 'Laufzeitende' }}. Danach wird er automatisch als beendet abgelegt.</p>
        </section>
    @elseif ($c->status->value === 'ended')
        <section class="panel p-5 sm:p-7">
            <h2 class="text-[20px] font-semibold tracking-tight">Beendet</h2>
            <form method="POST" action="{{ route('contracts.status', $c) }}" class="mt-4">
                @csrf
                <input type="hidden" name="action" value="reactivate">
                <button class="btn-secondary">Wieder aktivieren</button>
            </form>
        </section>
    @else
        <section class="panel p-5 sm:p-7">
            <h2 class="text-[20px] font-semibold tracking-tight">Keine Frist zu überwachen</h2>
            <p class="mt-2 text-[15px] text-ink-soft">{{ $deadline->note ?? 'Für diesen Vertrag ergibt sich aus den Angaben kein Kündigungstermin.' }}</p>
        </section>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-8">
            {{-- Kosten und Preisverlauf --}}
            <section aria-labelledby="price-title">
                <h2 id="price-title" class="section-title mb-3">Kosten</h2>
                <div class="panel p-5">
                    <div class="flex flex-wrap items-end gap-x-8 gap-y-3">
                        <div>
                            <p class="text-[13px] text-ink-soft">Aktuell</p>
                            <p class="text-[24px] font-semibold tracking-tight">CHF @chf($c->cost_amount) <span class="text-[15px] font-normal text-ink-soft">{{ $c->cost_interval->label() }}</span></p>
                        </div>
                        <div>
                            <p class="text-[13px] text-ink-soft">Pro Jahr</p>
                            <p class="text-[18px] font-medium">CHF @chf($c->yearlyCost())</p>
                        </div>
                    </div>

                    @if ($c->priceChanges->isNotEmpty())
                        @php
                            $points = collect([['d' => $c->start_date, 'v' => $c->priceChanges->first()->old_amount]])
                                ->concat($c->priceChanges->map(fn ($p) => ['d' => $p->effective_date, 'v' => $p->new_amount]));
                            $min = $points->min('v'); $max = $points->max('v');
                            $range = max(1, $max - $min);
                            $n = $points->count();
                            $coords = $points->values()->map(fn ($p, $i) => [
                                'x' => $n > 1 ? round(8 + $i * (304 / ($n - 1)), 1) : 160,
                                'y' => round(52 - (($p['v'] - $min) / $range) * 40, 1),
                            ]);
                            $line = $coords->map(fn ($p) => $p['x'].','.$p['y'])->implode(' ');
                        @endphp
                        <svg viewBox="0 0 320 60" class="mt-5 h-16 w-full max-w-[420px]" role="img" aria-label="Preisverlauf">
                            <polyline points="{{ $line }}" fill="none" stroke="var(--color-accent)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                            @foreach ($coords as $p)
                                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3.5" fill="var(--color-sheet)" stroke="var(--color-accent)" stroke-width="2" />
                            @endforeach
                        </svg>
                        <ul class="mt-4 divide-y divide-line border-t border-line">
                            @foreach ($c->priceChanges->reverse() as $p)
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-3 text-[14px]">
                                    <span class="w-24 text-ink-soft">{{ $p->effective_date->format('d.m.Y') }}</span>
                                    <span class="flex-1">CHF @chf($p->old_amount) auf <strong class="font-semibold">CHF @chf($p->new_amount)</strong>
                                        @if ($p->percent() !== null)
                                            <span class="{{ $p->difference() > 0 ? 'text-due-critical' : 'text-due-ok' }}">({{ $p->difference() > 0 ? '+' : '' }}{{ $p->percent() }} %)</span>
                                        @endif
                                    </span>
                                    @if ($p->special_termination_deadline)
                                        <span class="text-ink-soft">Sonderkündigung bis {{ $p->special_termination_deadline->format('d.m.Y') }}</span>
                                    @endif
                                    <form method="POST" action="{{ route('contracts.price-changes.destroy', [$c, $p]) }}" onsubmit="return confirm('Preisänderung entfernen?')">
                                        @csrf @method('DELETE')
                                        <button class="text-ink-faint hover:text-due-critical" aria-label="Preisänderung entfernen"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <details class="mt-5 border-t border-line pt-4" @if($errors->has('new_price') || $errors->has('effective_date')) open @endif>
                        <summary class="cursor-pointer text-[15px] font-medium text-accent">Preisänderung erfassen</summary>
                        <form method="POST" action="{{ route('contracts.price-changes.store', $c) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                            @csrf
                            <div>
                                <label for="new_price" class="label">Neuer Betrag in CHF ({{ $c->cost_interval->label() }})</label>
                                <input id="new_price" name="new_price" inputmode="decimal" value="{{ old('new_price') }}" class="field" required>
                                <x-field-error name="new_price" />
                            </div>
                            <div>
                                <label for="effective_date" class="label">Gültig ab</label>
                                <input id="effective_date" name="effective_date" type="date" value="{{ old('effective_date') }}" class="field" required>
                                <x-field-error name="effective_date" />
                            </div>
                            <div>
                                <label for="announced_at" class="label">Mitgeteilt am</label>
                                <input id="announced_at" name="announced_at" type="date" value="{{ old('announced_at', $today->format('Y-m-d')) }}" class="field">
                            </div>
                            <div>
                                <label for="special_termination_deadline" class="label">Sonderkündigung möglich bis</label>
                                <input id="special_termination_deadline" name="special_termination_deadline" type="date" value="{{ old('special_termination_deadline') }}" class="field">
                                <p class="hint">Steht meist im Schreiben zur Preiserhöhung. Mit Datum erinnert dich die App.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <button class="btn-primary">Preisänderung speichern</button>
                            </div>
                        </form>
                    </details>
                </div>
            </section>

            {{-- Dokumente --}}
            <section aria-labelledby="docs-title">
                <h2 id="docs-title" class="section-title mb-3">Dokumente</h2>
                <div class="panel">
                    @if ($c->documents->isNotEmpty())
                        <ul class="divide-y divide-line">
                            @foreach ($c->documents as $doc)
                                <li class="flex items-center gap-3 px-4 py-3">
                                    <x-icon name="contracts" class="size-5 shrink-0 text-ink-faint" />
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('documents.download', $doc) }}" class="block truncate text-[15px] font-medium hover:underline">{{ $doc->original_name }}</a>
                                        <p class="text-[13px] text-ink-soft">{{ $doc->kindLabel() }}, {{ $doc->created_at->format('d.m.Y') }}</p>
                                    </div>
                                    <form method="POST" action="{{ route('documents.destroy', $doc) }}" onsubmit="return confirm('Dokument löschen?')">
                                        @csrf @method('DELETE')
                                        <button class="p-1 text-ink-faint hover:text-due-critical" aria-label="Dokument löschen"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('contracts.documents.store', $c) }}" enctype="multipart/form-data" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-end {{ $c->documents->isNotEmpty() ? 'border-t border-line' : '' }}">
                        @csrf
                        <div class="flex-1">
                            <label for="file" class="label">Datei (PDF oder Foto, max. 10 MB)</label>
                            <input id="file" name="file" type="file" accept=".pdf,image/*" required class="block w-full text-[14px] text-ink-soft file:mr-3 file:rounded-lg file:border file:border-line-strong file:bg-sheet file:px-3 file:py-2 file:text-[14px] file:font-medium file:text-ink">
                            <x-field-error name="file" />
                        </div>
                        <div>
                            <label for="kind" class="label">Art</label>
                            <select id="kind" name="kind" class="field">
                                <option value="policy">Vertrag / Police</option>
                                <option value="confirmation">Kündigungsbestätigung</option>
                                <option value="letter">Kündigungsschreiben</option>
                                <option value="other">Anderes</option>
                            </select>
                        </div>
                        <button class="btn-secondary"><x-icon name="upload" class="size-4" /> Hochladen</button>
                    </form>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="panel p-5" aria-labelledby="facts-title">
                <h2 id="facts-title" class="text-[15px] font-medium text-ink-soft">Vertragsdaten</h2>
                <dl class="mt-3 space-y-3 text-[14.5px]">
                    <div><dt class="text-[13px] text-ink-soft">Kategorie</dt><dd>{{ $c->category?->name ?? 'Keine' }}</dd></div>
                    <div><dt class="text-[13px] text-ink-soft">Beginn</dt><dd>{{ $c->start_date->format('d.m.Y') }}</dd></div>
                    @if ($c->notice_anchor->value === 'fixed_dates')
                        <div><dt class="text-[13px] text-ink-soft">Kündbar auf</dt><dd>{{ \App\Support\FixedDates::humanize($c->fixed_end_dates) }}</dd></div>
                    @elseif ($c->notice_anchor->value === 'term_end')
                        <div><dt class="text-[13px] text-ink-soft">Laufzeit</dt><dd>{{ $c->min_term_months ? $c->min_term_months.' Monate' : 'keine Mindestlaufzeit' }}{{ $c->renewal_months ? ', verlängert sich um '.$c->renewal_months.' Mt.' : ', keine Verlängerung' }}</dd></div>
                    @else
                        <div><dt class="text-[13px] text-ink-soft">Kündbar</dt><dd>jederzeit</dd></div>
                    @endif
                    <div><dt class="text-[13px] text-ink-soft">Kündigungsfrist</dt><dd>{{ $c->noticeLabel() }}</dd></div>
                    @if ($c->customer_number)
                        <div><dt class="text-[13px] text-ink-soft">Kunden- / Policennummer</dt><dd class="break-all">{{ $c->customer_number }}</dd></div>
                    @endif
                    @if ($c->cancellation_address || $c->cancellation_email)
                        <div><dt class="text-[13px] text-ink-soft">Kündigung an</dt>
                            <dd class="whitespace-pre-line">{{ $c->cancellation_address }}</dd>
                            @if ($c->cancellation_email)<dd><a href="mailto:{{ $c->cancellation_email }}" class="text-accent hover:underline">{{ $c->cancellation_email }}</a></dd>@endif
                        </div>
                    @endif
                    @if ($c->notes)
                        <div><dt class="text-[13px] text-ink-soft">Notizen</dt><dd class="whitespace-pre-line">{{ $c->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($c->category?->tracks_usage)
                <section class="panel p-5">
                    <h2 class="text-[15px] font-medium text-ink-soft">Nutzung</h2>
                    <p class="mt-1 text-[15px]">Zuletzt genutzt: {{ $c->last_used_at ? $c->last_used_at->translatedFormat('j. F Y') : 'noch nie vermerkt' }}</p>
                    @if ($c->isUnused($today))
                        <p class="mt-1 text-[14px] text-due-soon">Seit über {{ config('fristwatcher.unused_after_days') }} Tagen nicht genutzt.</p>
                    @endif
                    <form method="POST" action="{{ route('contracts.used', $c) }}" class="mt-3">
                        @csrf
                        <button class="btn-secondary px-3 py-1.5 text-sm">Heute genutzt</button>
                    </form>
                </section>
            @endif

            @if ($reminders->isNotEmpty())
                <section class="panel p-5">
                    <h2 class="text-[15px] font-medium text-ink-soft">Erinnerungen</h2>
                    <ul class="mt-3 space-y-2 text-[14px]">
                        @foreach ($reminders as $r)
                            <li class="flex items-center gap-2 {{ $r->sent_at ? 'text-ink-faint' : '' }}">
                                <x-icon :name="$r->sent_at ? 'check' : 'bell'" class="size-4 shrink-0" />
                                <span>{{ $r->due_on->format('d.m.Y') }}</span>
                                <span class="text-ink-soft">{{ $r->kind->value === 'confirmation' ? 'Bestätigung nachfragen' : ($r->offset_days === 0 ? 'am Versandtag' : $r->offset_days.' Tage vorher') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <form method="POST" action="{{ route('contracts.destroy', $c) }}" onsubmit="return confirm('Vertrag mit allen Dokumenten löschen?')">
                @csrf @method('DELETE')
                <button class="btn-danger w-full"><x-icon name="trash" class="size-4" /> Vertrag löschen</button>
            </form>
        </aside>
    </div>
</x-layouts.app>
