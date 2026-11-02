<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public static function registrationOpen(): bool
    {
        return config('fristwatcher.open_registration') || ! User::query()->exists();
    }

    public function showLogin(): View|RedirectResponse
    {
        if (! User::query()->exists()) {
            return redirect()->route('register');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Zu viele Versuche. Bitte in '.RateLimiter::availableIn($key).' Sekunden erneut versuchen.',
            ]);
        }

        if (! Auth::attempt($credentials, true)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'E-Mail oder Passwort stimmen nicht.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (! self::registrationOpen()) {
            return redirect()->route('login');
        }

        return view('auth.register', ['first' => ! User::query()->exists()]);
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(self::registrationOpen(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $user = User::create($data + ['email_verified_at' => now()]);
        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('settings.edit')->with('status', 'Willkommen! Ergänze deine Adresse für Kündigungsschreiben.');
    }

    public function showForgot(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Immer gleiche Antwort, damit nicht erkennbar ist, ob ein Konto existiert.
        return back()->with('status', 'Falls ein Konto mit dieser Adresse existiert, ist ein Link zum Zurücksetzen unterwegs.');
    }

    public function showReset(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->string('email')]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Der Link ist ungültig oder abgelaufen. Bitte neu anfordern.']);
        }

        return redirect()->route('login')->with('status', 'Passwort geändert. Du kannst dich jetzt anmelden.');
    }
}
