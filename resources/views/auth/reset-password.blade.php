<x-layouts.guest title="Neues Passwort">
    <h1 class="text-[22px] font-semibold tracking-tight">Neues Passwort setzen</h1>
    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="label">E-Mail</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="field">
            <x-field-error name="email" />
        </div>
        <div>
            <label for="password" class="label">Neues Passwort</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" minlength="10" class="field">
            <x-field-error name="password" />
        </div>
        <div>
            <label for="password_confirmation" class="label">Passwort wiederholen</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="field">
        </div>
        <button class="btn-primary w-full">Passwort speichern</button>
    </form>
</x-layouts.guest>
