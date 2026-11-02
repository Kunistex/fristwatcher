<?php

namespace App\Console\Commands;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\PriceChange;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RefreshDeadlines extends Command
{
    protected $signature = 'fristwatcher:refresh';

    protected $description = 'Berechnet Fristen neu (abgelaufene Perioden) und beendet abgelaufene Kündigungen';

    public function handle(): int
    {
        $today = CarbonImmutable::today();
        $updated = 0;

        Contract::query()->open()->with('user')->get()->each(function (Contract $contract) use ($today, &$updated) {
            if ($contract->status->isCancelled() && $contract->cancellation_effective_on?->lt($today)) {
                $contract->status = ContractStatus::Ended;
                $contract->save();
                $updated++;

                return;
            }

            $contract->applyDeadlines($today);

            if ($contract->isDirty(['next_term_end', 'receipt_deadline', 'send_by'])) {
                $contract->save();
                $updated++;
            }
        });

        // Angekündigte Preisänderungen ab Inkrafttreten übernehmen.
        PriceChange::query()
            ->whereNull('applied_at')
            ->whereDate('effective_date', '<=', $today->toDateString())
            ->with('contract')
            ->orderBy('effective_date')
            ->get()
            ->each(function (PriceChange $change) use (&$updated) {
                $change->contract?->update(['cost_amount' => $change->new_amount]);
                $change->forceFill(['applied_at' => now()])->saveQuietly();
                $updated++;
            });

        $this->info("{$updated} Vertrag/Verträge aktualisiert.");

        return self::SUCCESS;
    }
}
