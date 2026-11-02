<x-layouts.guest title="Anmelden">
    <h1 class="mb-6 text-[22px] font-semibold tracking-tight">Anmelden</h1>
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="label">E-Mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">
            <x-field-error name="email" />
        </div>
        <div>
            <label for="password" class="label">Passwort</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="field">
        </div>
        <button class="btn-primary w-full">Anmelden</button>
    </form>
    <div class="mt-5 flex justify-between text-sm">
        <a href="{{ route('password.request') }}" class="text-ink-soft underline-offset-4 hover:text-ink hover:underline">Passwort vergessen?</a>
        @if (\App\Http\Controllers\Auth\AuthController::registrationOpen())
            <a href="{{ route('register') }}" class="font-medium text-accent underline-offset-4 hover:underline">Konto anlegen</a>
        @endif
    </div>
</x-layouts.guest>
