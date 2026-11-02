<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Enums\ReminderKind;
use App\Models\Contract;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractActionController extends Controller
{
    /** Statuswechsel im Kündigungsablauf. */
    public function status(Request $request, Contract $contract): RedirectResponse
    {
        $this->owns($contract);

        $data = $request->validate([
            'action' => ['required', Rule::in(['plan', 'sent', 'confirmed', 'reactivate', 'end'])],
            'sent_on' => ['nullable', 'date'],
            'effective_on' => ['nullable', 'date'],
        ]);

        $message = match ($data['action']) {
            'plan' => $this->apply($contract, ContractStatus::CancellationPlanned, 'Kündigung vorgemerkt.'),
            'sent' => $this->markSent($contract, $data),
            'confirmed' => $this->apply($contract, ContractStatus::CancelledConfirmed, 'Bestätigung vermerkt. Erledigt.'),
            'reactivate' => $this->reactivate($contract),
            'end' => $this->apply($contract, ContractStatus::Ended, 'Vertrag als beendet markiert.'),
        };

        return back()->with('status', $message);
    }

    /** Vertrag behalten: Erinnerungen für die aktuelle Periode stummschalten. */
    public function keep(Contract $contract): RedirectResponse
    {
        $this->owns($contract);

        $contract->reminders()
            ->where('kind', ReminderKind::Deadline->value)
            ->whereNull('sent_at')
            ->update(['sent_at' => now()]);

        $until = $contract->next_term_end?->format('d.m.Y');

        return back()->with('status', "Behalten. Die nächste Erinnerung kommt für die Periode nach dem {$until}.");
    }

    public function used(Contract $contract): RedirectResponse
    {
        $this->owns($contract);
        $contract->forceFill(['last_used_at' => now()])->save();

        return back()->with('status', 'Als genutzt vermerkt.');
    }

    private function apply(Contract $contract, ContractStatus $status, string $message): string
    {
        $contract->status = $status;
        $contract->save();

        return $message;
    }

    private function markSent(Contract $contract, array $data): string
    {
        $contract->status = ContractStatus::CancelledUnconfirmed;
        $contract->cancellation_sent_on = CarbonImmutable::parse($data['sent_on'] ?? now());
        $contract->cancellation_effective_on = isset($data['effective_on'])
            ? CarbonImmutable::parse($data['effective_on'])
            : $contract->next_term_end;
        $contract->save();

        return 'Kündigung als versendet vermerkt. Ich erinnere dich, falls keine Bestätigung kommt.';
    }

    private function reactivate(Contract $contract): string
    {
        $contract->status = ContractStatus::Active;
        $contract->cancellation_sent_on = null;
        $contract->cancellation_effective_on = null;
        $contract->save();

        return 'Vertrag wieder aktiv.';
    }
}
