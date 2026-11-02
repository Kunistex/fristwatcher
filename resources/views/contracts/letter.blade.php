<x-layouts.app :title="'Kündigung '.$contract->name" :back="route('contracts.show', $contract)">
    <h1 class="text-[28px] font-semibold tracking-tight">Kündigungsschreiben</h1>
    <p class="mt-2 max-w-[62ch] text-[15px] text-ink-soft">Text prüfen, dann als PDF herunterladen. Ausdrucken, unterschreiben und
        @if ($contract->receipt_deadline) so versenden, dass es bis {{ $contract->receipt_deadline->translatedFormat('l, j. F Y') }} ankommt. @else rechtzeitig versenden. @endif
    </p>

    @unless (auth()->user()->hasPostalAddress())
        <p class="panel mt-5 flex gap-2 border-due-soon/40 bg-due-soon-wash px-4 py-3 text-[14px]">
            <x-icon name="info" class="mt-0.5 size-[18px] shrink-0 text-due-soon" />
            <span>Deine Adresse fehlt noch. Du kannst sie unten eintragen oder dauerhaft in den <a href="{{ route('settings.edit') }}" class="font-medium underline">Einstellungen</a> speichern.</span>
        </p>
    @endunless

    <form method="POST" action="{{ route('contracts.letter.download', $contract) }}" class="mt-6 max-w-[720px]">
        @csrf
        {{-- Papierähnliche Vorschau, direkt editierbar --}}
        <div class="panel space-y-6 p-6 sm:p-10">
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="sender" class="label">Absender</label>
                    <textarea id="sender" name="sender" rows="3" required class="field">{{ old('sender', $sender) }}</textarea>
                    <x-field-error name="sender" />
                </div>
                <div>
                    <label for="recipient" class="label">Empfänger</label>
                    <textarea id="recipient" name="recipient" rows="4" required class="field">{{ old('recipient', $recipient) }}</textarea>
                    <x-field-error name="recipient" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-[1fr_180px]">
                <div>
                    <label for="place" class="label">Ort</label>
                    <input id="place" name="place" value="{{ old('place', $place) }}" required class="field">
                </div>
                <div>
                    <label for="date" class="label">Datum</label>
                    <input id="date" name="date" type="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="field">
                </div>
            </div>

            <div>
                <label for="subject" class="label">Betreff</label>
                <input id="subject" name="subject" value="{{ old('subject', $subject) }}" required class="field font-semibold">
            </div>

            <div>
                <label for="body" class="label">Text</label>
                <textarea id="body" name="body" rows="12" required class="field leading-relaxed">{{ old('body', $body) }}</textarea>
                <x-field-error name="body" />
            </div>

            <div class="sm:w-80">
                <label for="signature" class="label">Name unter der Unterschrift</label>
                <input id="signature" name="signature" value="{{ old('signature', $signature) }}" required class="field">
            </div>

            <label class="flex items-center gap-2.5 text-[15px]">
                <input type="hidden" name="registered" value="0">
                <input type="checkbox" name="registered" value="1" checked class="rounded text-accent focus:ring-accent">
                Vermerk «Einschreiben» oben auf dem Brief
            </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="btn-primary"><x-icon name="download" /> PDF herunterladen</button>
            <a href="{{ route('contracts.show', $contract) }}" class="btn-quiet">Zurück</a>
        </div>
        <p class="mt-3 text-[13px] text-ink-soft">Das PDF wird beim Vertrag abgelegt und der Status auf «Kündigung geplant» gesetzt. Nach dem Versand im Vertrag «Als versendet markieren».</p>
    </form>
</x-layouts.app>
