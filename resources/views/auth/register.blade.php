<x-layouts.guest title="Konto anlegen">
    <h1 class="text-[22px] font-semibold tracking-tight">Konto anlegen</h1>
    @if ($first)
        <p class="mt-2 text-[15px] text-ink-soft">Das erste Konto auf dieser Installation. Danach ist die Registrierung geschlossen.</p>
    @endif
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="name" class="label">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="field">
            <x-field-error name="name" />
        </div>
        <div>
            <label for="email" class="label">E-Mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" class="field">
            <p class="hint">An diese Adresse gehen die Erinnerungen.</p>
            <x-field-error name="email" />
        </div>
        <div>
            <label for="password" class="label">Passwort</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="10" class="field">
            <p class="hint">Mindestens 10 Zeichen.</p>
            <x-field-error name="password" />
        </div>
        <div>
            <label for="password_confirmation" class="label">Passwort wiederholen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
        </div>
        <button class="btn-primary w-full">Konto anlegen</button>
    </form>
</x-layouts.guest>
