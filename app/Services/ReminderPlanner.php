<?php

namespace App\Services;

use App\Enums\ContractStatus;
use App\Enums\ReminderKind;
use App\Models\Contract;
use App\Models\ExpiryItem;
use App\Models\PriceChange;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Legt die geplanten Erinnerungen an. Offene (noch nicht versendete) Erinnerungen eines Objekts
 * werden bei jeder Änderung verworfen und neu erzeugt; bereits versendete bleiben als Verlauf.
 */
class ReminderPlanner
{
    public function planForContract(Contract $contract, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();

        $contract->reminders()->whereNull('sent_at')
            ->whereIn('kind', [ReminderKind::Deadline->value, ReminderKind::Confirmation->value])
            ->delete();

        if ($contract->status->watchesDeadline() && $contract->send_by) {
            $offsets = $contract->reminder_offsets ?: $contract->user->reminderOffsets();
            $this->plan($contract, ReminderKind::Deadline, $contract->send_by, $offsets, $contract->user_id, $today);
        }

        if ($contract->status === ContractStatus::CancelledUnconfirmed && $contract->cancellation_sent_on) {
            $due = $contract->cancellation_sent_on->addDays((int) config('fristwatcher.confirmation_after_days'));
            $this->create($contract, ReminderKind::Confirmation, $contract->cancellation_effective_on ?? $due, $due->lt($today) ? $today : $due, 0, $contract->user_id);
        }
    }

    public function planForPriceChange(PriceChange $change, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();
        $change->reminders()->whereNull('sent_at')->delete();

        $contract = $change->contract;
        if (! $change->special_termination_deadline || ! $contract->status->watchesDeadline()) {
            return;
        }

        $this->plan($change, ReminderKind::PriceSpecial, $change->special_termination_deadline, [14, 3, 0], $contract->user_id, $today);
    }

    public function planForExpiry(ExpiryItem $item, ?CarbonImmutable $today = null): void
    {
        $today ??= CarbonImmutable::today();
        $item->reminders()->whereNull('sent_at')->delete();

        $this->plan($item, ReminderKind::Expiry, $item->expires_at, $item->type->offsets(), $item->user_id, $today);
    }

    /** @param  list<int>  $offsets */
    private function plan(Model $remindable, ReminderKind $kind, CarbonImmutable $target, array $offsets, int $userId, CarbonImmutable $today): void
    {
        if ($target->lt($today)) {
            return;
        }

        $created = 0;
        foreach (array_unique(array_map('intval', $offsets)) as $offset) {
            $due = $target->subDays($offset);
            if ($due->lt($today)) {
                continue;
            }
            $created += $this->create($remindable, $kind, $target, $due, $offset, $userId) ? 1 : 0;
        }

        // Alle Vorlaufzeiten schon vorbei: einmal sofort erinnern.
        if ($created === 0 && ! $this->alreadySent($remindable, $kind, $target)) {
            $this->create($remindable, $kind, $target, $today, (int) $today->diffInDays($target), $userId);
        }
    }

    private function create(Model $remindable, ReminderKind $kind, CarbonImmutable $target, CarbonImmutable $due, int $offset, int $userId): bool
    {
        $sentBefore = $remindable->reminders()
            ->where('kind', $kind->value)
            ->whereDate('target_date', $target->toDateString())
            ->where('offset_days', $offset)
            ->whereNotNull('sent_at')
            ->exists();

        if ($sentBefore) {
            return false;
        }

        $remindable->reminders()->create([
            'user_id' => $userId,
            'kind' => $kind,
            'target_date' => $target,
            'due_on' => $due,
            'offset_days' => $offset,
        ]);

        return true;
    }

    private function alreadySent(Model $remindable, ReminderKind $kind, CarbonImmutable $target): bool
    {
        return $remindable->reminders()
            ->where('kind', $kind->value)
            ->whereDate('target_date', $target->toDateString())
            ->whereNotNull('sent_at')
            ->exists();
    }
}
