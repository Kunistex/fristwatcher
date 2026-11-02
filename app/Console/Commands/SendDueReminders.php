<?php

namespace App\Console\Commands;

use App\Enums\ReminderKind;
use App\Models\Contract;
use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDueReminders extends Command
{
    protected $signature = 'fristwatcher:send-reminders {--force : Auch vor der konfigurierten Stunde senden}';

    protected $description = 'Versendet fällige Erinnerungen per E-Mail und Push';

    public function handle(): int
    {
        $now = CarbonImmutable::now();

        if (! $this->option('force') && $now->hour < (int) config('fristwatcher.reminder_hour')) {
            $this->line('Noch zu früh, Versand ab '.config('fristwatcher.reminder_hour').' Uhr.');

            return self::SUCCESS;
        }

        $sent = 0;

        Reminder::query()
            ->whereNull('sent_at')
            ->whereDate('due_on', '<=', $now->toDateString())
            ->with(['user', 'remindable'])
            ->orderBy('due_on')
            ->get()
            ->each(function (Reminder $reminder) use (&$sent) {
                if (! $reminder->remindable || ! $reminder->user || $this->isObsolete($reminder)) {
                    $reminder->delete();

                    return;
                }

                try {
                    $reminder->user->notify(new ReminderNotification($reminder));
                    $reminder->forceFill(['sent_at' => now()])->save();
                    $sent++;
                } catch (\Throwable $e) {
                    Log::error('Erinnerung konnte nicht gesendet werden', ['reminder' => $reminder->id, 'error' => $e->getMessage()]);
                    $this->error("Erinnerung {$reminder->id}: {$e->getMessage()}");
                }
            });

        $this->info("{$sent} Erinnerung(en) versendet.");

        return self::SUCCESS;
    }

    private function isObsolete(Reminder $reminder): bool
    {
        $item = $reminder->remindable;

        return match ($reminder->kind) {
            ReminderKind::Deadline => ! ($item instanceof Contract) || ! $item->status->watchesDeadline(),
            ReminderKind::Confirmation => ! ($item instanceof Contract) || $item->status->value !== 'cancelled_unconfirmed',
            ReminderKind::PriceSpecial => ! $item->contract?->status->watchesDeadline(),
            ReminderKind::Expiry => false,
        };
    }
}
