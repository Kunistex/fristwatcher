@php
    $editing = $contract->exists;
    $v = fn ($key, $default = null) => old($key, $default ?? $contract->{$key});
    $anchor = old('notice_anchor', $contract->notice_anchor?->value ?? 'term_end');
@endphp
<x-layouts.app :title="$editing ? $contract->name.' bearbeiten' : 'Vertrag erfassen'" :back="$editing ? route('contracts.show', $contract) : route('contracts.create')">
    <h1 class="text-[28px] font-semibold tracking-tight">{{ $editing ? 'Vertrag bearbeiten' : ($template?->name ?? 'Vertrag erfassen') }}</h1>
    @if ($template?->help_text)
        <p class="mt-2 flex max-w-[70ch] gap-2 text-[15px] leading-relaxed text-ink-soft"><x-icon name="info" class="mt-0.5 size-[18px] shrink-0 text-accent" /> {{ $template->help_text }}</p>
    @endif

    <form method="POST" action="{{ $editing ? route('contracts.update', $contract) : route('contracts.store') }}"
          x-data="contractForm({ anchor: @js($anchor), previewUrl: @js(route('contracts.preview')) })"
          class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_300px]">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="template_id" value="{{ old('template_id', $contract->template_id) }}">

        <div class="space-y-8">
            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-4 w-full">Vertrag</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="name" class="label">Bezeichnung</label>
                        <input id="name" name="name" value="{{ $v('name') }}" required maxlength="120" class="field" placeholder="z.B. Fitnessabo">
                        <x-field-error name="name" />
                    </div>
                    <div>
                        <label for="provider" class="label">Anbieter</label>
                        <input id="provider" name="provider" value="{{ $v('provider') }}" required maxlength="120" class="field" placeholder="z.B. Helsana">
                        <x-field-error name="provider" />
                    </div>
                    <div>
                        <label for="category_id" class="label">Kategorie</label>
                        <select id="category_id" name="category_id" class="field">
                            <option value="">Keine</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected((int) $v('category_id') === $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="customer_number" class="label">Kunden- / Policennummer</label>
                        <input id="customer_number" name="customer_number" value="{{ $v('customer_number') }}" maxlength="80" class="field">
                        <p class="hint">Kommt ins Kündigungsschreiben.</p>
                    </div>
                </div>
            </fieldset>

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-4 w-full">Kosten</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="cost" class="label">Betrag in CHF</label>
                        <input id="cost" name="cost" value="{{ $costValue }}" inputmode="decimal" class="field" placeholder="79.90">
                        <x-field-error name="cost" />
                    </div>
                    <div>
                        <label for="cost_interval" class="label">Intervall</label>
                        <select id="cost_interval" name="cost_interval" class="field">
                            @foreach (\App\Enums\CostInterval::cases() as $ci)
                                <option value="{{ $ci->value }}" @selected(old('cost_interval', $contract->cost_interval?->value) === $ci->value)>{{ ucfirst($ci->label()) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-4 w-full">Laufzeit und Kündigung</legend>

                <div>
                    <label for="start_date" class="label">Vertragsbeginn</label>
                    <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}" required class="field sm:w-56">
                    <x-field-error name="start_date" />
                </div>

                <div role="radiogroup" aria-labelledby="anchor-label">
                    <p id="anchor-label" class="label">Kündbar</p>
                    <div class="grid gap-2 sm:grid-cols-3">
                        @foreach (\App\Enums\NoticeAnchor::cases() as $a)
                            <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2.5 text-[14px]"
                                   :class="anchor === '{{ $a->value }}' ? 'border-accent bg-accent-wash' : 'border-line-strong bg-sheet'">
                                <input type="radio" name="notice_anchor" value="{{ $a->value }}" x-model="anchor" class="mt-0.5 text-accent focus:ring-accent" @checked($anchor === $a->value)>
                                <span>{{ $a->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2" x-show="anchor === 'term_end'" x-cloak>
                    <div>
                        <label for="min_term_months" class="label">Mindestlaufzeit in Monaten</label>
                        <input id="min_term_months" name="min_term_months" type="number" min="0" max="240" value="{{ $v('min_term_months') }}" class="field">
                        <x-field-error name="min_term_months" />
                    </div>
                    <div>
                        <label for="renewal_months" class="label">Verlängert sich danach um</label>
                        <div class="flex items-center gap-2">
                            <input id="renewal_months" name="renewal_months" type="number" min="0" max="120" value="{{ $v('renewal_months') }}" class="field">
                            <span class="text-[14px] text-ink-soft">Monate</span>
                        </div>
                        <p class="hint">Leer lassen, wenn der Vertrag einfach endet.</p>
                    </div>
                </div>

                <div x-show="anchor === 'fixed_dates'" x-cloak>
                    <label for="fixed_end_dates_text" class="label">Feste Kündigungstermine</label>
                    <input id="fixed_end_dates_text" name="fixed_end_dates_text" value="{{ $fixedDatesText }}" class="field sm:w-80" placeholder="31.12.">
                    <p class="hint">Mehrere mit Komma trennen. Gilt für einen Termin eine andere Frist, die Monate mit Doppelpunkt anhängen: <span class="whitespace-nowrap font-medium text-ink">30.06.:3, 31.12.</span></p>
                    <x-field-error name="fixed_end_dates" />
                </div>

                <div>
                    <p class="label">Kündigungsfrist</p>
                    <div class="flex items-center gap-2">
                        <label for="notice_value" class="sr-only">Anzahl</label>
                        <input id="notice_value" name="notice_value" type="number" min="0" max="730" value="{{ $v('notice_value') ?? 0 }}" class="field w-24">
                        <label for="notice_unit" class="sr-only">Einheit</label>
                        <select id="notice_unit" name="notice_unit" class="field w-36">
                            @foreach (\App\Enums\NoticeUnit::cases() as $u)
                                <option value="{{ $u->value }}" @selected(old('notice_unit', $contract->notice_unit?->value) === $u->value)>{{ $u->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="hint">0 = bis zum Laufzeitende kündbar (z.B. Streaming vor der nächsten Abbuchung).</p>
                    <x-field-error name="notice_value" />
                </div>
            </fieldset>

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-4 w-full">Erinnerungen</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="send_buffer_days" class="label">Postweg in Arbeitstagen</label>
                        <input id="send_buffer_days" name="send_buffer_days" type="number" min="0" max="30" value="{{ $v('send_buffer_days') }}" placeholder="{{ $defaultBuffer }}" class="field w-28">
                        <p class="hint">So viele Arbeitstage vor dem Eingangsdatum solltest du versenden. Leer = Standard ({{ $defaultBuffer }}).</p>
                    </div>
                    <div>
                        <label for="reminder_offsets_text" class="label">Erinnern, Tage vorher</label>
                        <input id="reminder_offsets_text" name="reminder_offsets_text" value="{{ $offsetsText }}" placeholder="{{ $defaultOffsets }}" class="field">
                        <p class="hint">Bezogen auf den Versandtag. Leer = Standard aus den Einstellungen.</p>
                    </div>
                </div>
            </fieldset>

            <fieldset class="panel space-y-5 p-5 sm:p-6 [&>legend~*]:clear-both">
                <legend class="section-title float-left mb-4 w-full">Kündigung an</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="cancellation_address" class="label">Postadresse</label>
                        <textarea id="cancellation_address" name="cancellation_address" rows="4" maxlength="500" class="field" placeholder="Firma&#10;Abteilung&#10;Strasse&#10;PLZ Ort">{{ $v('cancellation_address') }}</textarea>
                    </div>
                    <div>
                        <label for="cancellation_email" class="label">E-Mail für Kündigungen</label>
                        <input id="cancellation_email" name="cancellation_email" type="email" value="{{ $v('cancellation_email') }}" class="field">
                        <x-field-error name="cancellation_email" />
                    </div>
                </div>
                <div>
                    <label for="notes" class="label">Notizen</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="5000" class="field">{{ $v('notes') }}</textarea>
                </div>
            </fieldset>

            <div class="flex flex-wrap gap-3">
                <button class="btn-primary">{{ $editing ? 'Änderungen speichern' : 'Vertrag speichern' }}</button>
                <a href="{{ $editing ? route('contracts.show', $contract) : route('contracts.index') }}" class="btn-quiet">Abbrechen</a>
            </div>
        </div>

        {{-- Live-Vorschau --}}
        <aside class="lg:sticky lg:top-10 lg:self-start" aria-live="polite">
            <div class="panel p-5">
                <h2 class="text-[15px] font-medium text-ink-soft">So rechnet der Fristenwächter</h2>
                <template x-if="preview && preview.send_by">
                    <dl class="mt-4 space-y-4">
                        <div>
                            <dt class="text-[13px] text-ink-soft">Versenden bis</dt>
                            <dd class="text-[20px] font-semibold leading-tight tracking-tight text-accent" x-text="preview.send_by_label"></dd>
                        </div>
                        <div>
                            <dt class="text-[13px] text-ink-soft">Eingang beim Anbieter bis</dt>
                            <dd class="text-[16px] font-medium" x-text="preview.receipt_deadline_label"></dd>
                        </div>
                        <div>
                            <dt class="text-[13px] text-ink-soft">Vertrag endet dann am</dt>
                            <dd class="text-[16px] font-medium" x-text="preview.term_end_label"></dd>
                        </div>
                    </dl>
                </template>
                <p class="mt-4 text-[14px] leading-snug text-ink-soft" x-show="preview && preview.note" x-text="preview?.note"></p>
                <p class="mt-4 text-[14px] text-ink-faint" x-show="!preview">Rechne …</p>
            </div>
        </aside>
    </form>
</x-layouts.app>
