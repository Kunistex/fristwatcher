<?php

namespace App\Notifications;

use App\Enums\ReminderKind;
use App\Models\Contract;
use App\Models\ExpiryItem;
use App\Models\PriceChange;
use App\Models\Reminder;
use App\Models\User;
use App\Support\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class ReminderNotification extends Notification
{
    public function __construct(public readonly Reminder $reminder) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        $channels = [];

        if ($notifiable->wantsMail()) {
            $channels[] = 'mail';
        }

        if ($notifiable->wantsPush() && filled(config('webpush.vapid.public_key')) && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(User $notifiable): MailMessage
    {
        $c = $this->content();

        $mail = (new MailMessage)
            ->subject($c['title'])
            ->greeting('Hallo '.strtok($notifiable->name, ' '))
            ->salutation('Dein Fristenwächter');

        foreach ($c['lines'] as $line) {
            $mail->line($line);
        }

        return $mail->action($c['action'], $c['url']);
    }

    public function toWebPush(User $notifiable, Notification $notification): WebPushMessage
    {
        $c = $this->content();

        return (new WebPushMessage)
            ->title($c['title'])
            ->body($c['lines'][0] ?? '')
            ->icon(url('/icons/icon-192.png'))
            ->badge(url('/icons/badge-72.png'))
            ->tag('reminder-'.$this->reminder->id)
            ->data(['url' => $c['url']])
            ->options(['TTL' => 86400]);
    }

    /** @return array{title: string, lines: list<string>, action: string, url: string} */
    public function content(): array
    {
        $r = $this->reminder;
        $item = $r->remindable;
        $date = fn ($d) => $d?->translatedFormat('l, j. F Y');
        $days = (int) $r->due_on->diffInDays($r->target_date, false);
        $when = match (true) {
            $days <= 0 => 'heute',
            $days === 1 => 'morgen',
            default => "in {$days} Tagen",
        };

        return match ($r->kind) {
            ReminderKind::Deadline => $this->forContract($item, $when, $date),
            ReminderKind::Confirmation => [
                'title' => "{$item->name}: Kündigungsbestätigung erhalten?",
                'lines' => [
                    "Du hast {$item->name} bei {$item->provider} gekündigt. Ist die schriftliche Bestätigung angekommen?",
                    'Ohne Bestätigung lohnt sich eine Nachfrage, bevor die Frist abläuft.',
                ],
                'action' => 'Status aktualisieren',
                'url' => route('contracts.show', $item),
            ],
            ReminderKind::PriceSpecial => $this->forPriceChange($item, $when, $date),
            ReminderKind::Expiry => $this->forExpiry($item, $when, $date),
        };
    }

    private function forContract(Contract $c, string $when, callable $date): array
    {
        return [
            'title' => "{$c->name}: Kündigung {$when} versenden",
            'lines' => array_values(array_filter([
                "Wenn du {$c->name} bei {$c->provider} kündigen willst, versende die Kündigung bis {$date($c->send_by)}.",
                "Sie muss bis {$date($c->receipt_deadline)} eingetroffen sein. Sonst läuft der Vertrag über den {$c->next_term_end?->format('d.m.Y')} hinaus weiter.",
                $c->cost_amount > 0 ? 'Kosten: CHF '.Money::format($c->yearlyCost()).' pro Jahr.' : null,
            ])),
            'action' => 'Kündigungsschreiben erstellen',
            'url' => route('contracts.letter.edit', $c),
        ];
    }

    private function forPriceChange(PriceChange $p, string $when, callable $date): array
    {
        $c = $p->contract;

        return [
            'title' => "{$c->name}: Sonderkündigung bis {$p->special_termination_deadline->format('d.m.Y')}",
            'lines' => [
                "Der Preis steigt von CHF ".Money::format($p->old_amount).' auf CHF '.Money::format($p->new_amount).'. Das Sonderkündigungsrecht läuft '.$when.' ab.',
                "Letzter Tag: {$date($p->special_termination_deadline)}.",
            ],
            'action' => 'Vertrag öffnen',
            'url' => route('contracts.show', $c),
        ];
    }

    private function forExpiry(ExpiryItem $e, string $when, callable $date): array
    {
        return [
            'title' => "{$e->name} läuft ".($when === 'heute' ? 'heute' : $when).' ab',
            'lines' => [
                "{$e->type->label()} {$e->name}: gültig bis {$date($e->expires_at)}.",
                in_array($e->type->value, ['passport', 'id_card'], true)
                    ? 'Für einen neuen Ausweis rechtzeitig einen Termin beim Passbüro buchen.'
                    : 'Rechtzeitig erneuern oder Garantiefall melden.',
            ],
            'action' => 'Ablaufdaten öffnen',
            'url' => route('expiry.index'),
        ];
    }
}
