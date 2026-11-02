<?php

use Illuminate\Support\Facades\Schedule;

// Stündlich, damit ein verpasster Cron-Lauf auf Shared Hosting nichts verschluckt.
// send-reminders versendet erst ab der konfigurierten Stunde (FRISTWATCHER_REMINDER_HOUR).
Schedule::command('fristwatcher:refresh')->hourlyAt(0)->withoutOverlapping();
Schedule::command('fristwatcher:send-reminders')->hourlyAt(5)->withoutOverlapping();

Schedule::command('auth:clear-resets')->daily();
