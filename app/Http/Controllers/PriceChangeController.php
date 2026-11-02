<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\PriceChange;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PriceChangeController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->owns($contract);

        $request->merge(['new_amount' => Money::parse($request->input('new_price'))]);

        $data = $request->validate([
            'new_price' => ['required'],
            'new_amount' => ['integer', 'min:0'],
            'effective_date' => ['required', 'date'],
            'announced_at' => ['nullable', 'date'],
            'special_termination_deadline' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['new_price.required' => 'Neuen Betrag eingeben.']);

        $change = new PriceChange([
            'old_amount' => $contract->cost_amount,
            'new_amount' => $data['new_amount'],
            'effective_date' => $data['effective_date'],
            'announced_at' => $data['announced_at'] ?? null,
            'special_termination_deadline' => $data['special_termination_deadline'] ?? null,
            'note' => $data['note'] ?? null,
        ]);
        $contract->priceChanges()->save($change);

        // Neuer Preis gilt ab Inkrafttreten; liegt das Datum in der Zukunft, bleibt der alte Preis bis dahin aktiv.
        if ($change->effective_date->lte(now())) {
            $contract->update(['cost_amount' => $change->new_amount]);
            $change->forceFill(['applied_at' => now()])->saveQuietly();
        }

        $message = 'Preisänderung erfasst.';
        if ($change->special_termination_deadline) {
            $message .= ' Erinnerung an das Sonderkündigungsrecht ist geplant.';
        }

        return back()->with('status', $message);
    }

    public function destroy(Contract $contract, PriceChange $priceChange): RedirectResponse
    {
        $this->owns($contract);
        abort_unless($priceChange->contract_id === $contract->id, 404);
        $priceChange->delete();

        return back()->with('status', 'Preisänderung entfernt.');
    }
}
