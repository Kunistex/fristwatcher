<?php

namespace App\Http\Controllers;

use App\Enums\ReminderKind;
use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use App\Services\ReminderPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('settings', [
            'user' => $user,
            'offsetsText' => implode(', ', $user->reminderOffsets()),
            'cantons' => config('fristwatcher.cantons'),
            'pushDevices' => $user->pushSubscriptions()->count(),
            'vapidKey' => config('webpush.vapid.public_key'),
        ]);
    }

    public function update(Request $request, ReminderPlanner $planner): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'street' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:80'],
            'canton' => ['required', Rule::in(config('fristwatcher.cantons'))],
            'reminder_offsets_text' => ['nullable', 'string', 'max:60'],
            'send_buffer_days' => ['required', 'integer', 'min:0', 'max:30'],
            'notify_mail' => ['nullable', 'boolean'],
            'notify_push' => ['nullable', 'boolean'],
        ]);

        $offsets = collect(preg_split('/[,;\s]+/', (string) ($data['reminder_offsets_text'] ?? '')))
            ->filter(fn ($v) => is_numeric($v) && (int) $v >= 0 && (int) $v <= 365)
            ->map(fn ($v) => (int) $v)->unique()->sortDesc()->values()->all();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'street' => $data['street'],
            'zip' => $data['zip'],
            'city' => $data['city'],
            'canton' => $data['canton'],
            'settings' => array_merge($user->settings ?? [], [
                'reminder_offsets' => $offsets ?: config('fristwatcher.default_offsets'),
                'send_buffer_days' => (int) $data['send_buffer_days'],
                'notify_mail' => $request->boolean('notify_mail'),
                'notify_push' => $request->boolean('notify_push'),
            ]),
        ])->save();

        // Neue Vorlaufzeiten/Puffer gelten sofort für alle Verträge.
        $user->contracts()->open()->get()->each(fn ($c) => $c->setRelation('user', $user)->save());

        return back()->with('status', 'Einstellungen gespeichert.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Passwort geändert.');
    }

    public function testNotification(Request $request): RedirectResponse
    {
        $user = $request->user();
        $contract = $user->contracts()->watching()->whereNotNull('send_by')->orderBy('send_by')->first();

        if (! $contract) {
            return back()->with('status', 'Für eine Test-Erinnerung zuerst einen aktiven Vertrag mit Frist erfassen.');
        }

        // Nicht gespeicherte Erinnerung, nur für den Versand.
        $reminder = new Reminder([
            'user_id' => $user->id,
            'kind' => ReminderKind::Deadline,
            'target_date' => $contract->send_by,
            'due_on' => now()->startOfDay(),
            'offset_days' => 0,
        ]);
        $reminder->setRelation('remindable', $contract);

        $user->notify(new ReminderNotification($reminder));

        return back()->with('status', 'Test-Erinnerung gesendet an '.collect([$user->wantsMail() ? 'E-Mail' : null, $user->wantsPush() ? 'Push' : null])->filter()->implode(' und ').'.');
    }
}
