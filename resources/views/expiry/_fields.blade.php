<div>
    <label for="type" class="label">Art</label>
    <select id="type" name="type" class="field">
        @foreach ($types as $t)
            <option value="{{ $t->value }}" @selected(old('type', $item?->type->value) === $t->value)>{{ $t->label() }}</option>
        @endforeach
    </select>
</div>
<div>
    <label for="name" class="label">Bezeichnung</label>
    <input id="name" name="name" value="{{ old('name', $item?->name) }}" required maxlength="120" class="field" placeholder="z.B. Pass Stefan">
    <x-field-error name="name" />
</div>
<div>
    <label for="expires_at" class="label">Gültig bis</label>
    <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at', $item?->expires_at->format('Y-m-d')) }}" required class="field">
    <x-field-error name="expires_at" />
</div>
<div>
    <label for="reference_last4" class="label">Letzte 4 Zeichen der Nummer (optional)</label>
    <input id="reference_last4" name="reference_last4" value="{{ old('reference_last4', $item?->reference_last4) }}" maxlength="4" class="field w-28" autocomplete="off">
    <p class="hint">Zum Auseinanderhalten. Die ganze Nummer wird nie gespeichert.</p>
    <x-field-error name="reference_last4" />
</div>
<div>
    <label for="notes" class="label">Notiz</label>
    <textarea id="notes" name="notes" rows="2" maxlength="2000" class="field">{{ old('notes', $item?->notes) }}</textarea>
</div>
