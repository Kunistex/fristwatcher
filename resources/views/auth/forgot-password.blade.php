<x-layouts.guest title="Passwort vergessen">
    <h1 class="text-[22px] font-semibold tracking-tight">Passwort vergessen</h1>
    <p class="mt-2 text-[15px] text-ink-soft">Du bekommst einen Link, mit dem du ein neues Passwort setzt.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">E-Mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="field">
            <x-field-error name="email" />
        </div>
        <button class="btn-primary w-full">Link senden</button>
    </form>
    <a href="{{ route('login') }}" class="mt-5 inline-block text-sm text-ink-soft hover:text-ink">Zurück zur Anmeldung</a>
</x-layouts.guest>
