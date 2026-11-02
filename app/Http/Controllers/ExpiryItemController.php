<?php

namespace App\Http\Controllers;

use App\Enums\ExpiryType;
use App\Models\ExpiryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpiryItemController extends Controller
{
    public function index(Request $request): View
    {
        return view('expiry.index', [
            'items' => $request->user()->expiryItems()->orderBy('expires_at')->get(),
            'types' => ExpiryType::cases(),
            'today' => now()->startOfDay(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->expiryItems()->create($this->validated($request));

        return redirect()->route('expiry.index')->with('status', 'Ablaufdatum gespeichert.');
    }

    public function edit(ExpiryItem $expiry): View
    {
        $this->owns($expiry);

        return view('expiry.edit', ['item' => $expiry, 'types' => ExpiryType::cases()]);
    }

    public function update(Request $request, ExpiryItem $expiry): RedirectResponse
    {
        $this->owns($expiry);
        $expiry->update($this->validated($request));

        return redirect()->route('expiry.index')->with('status', 'Änderungen gespeichert.');
    }

    public function destroy(ExpiryItem $expiry): RedirectResponse
    {
        $this->owns($expiry);
        $expiry->delete();

        return redirect()->route('expiry.index')->with('status', "{$expiry->name} gelöscht.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::enum(ExpiryType::class)],
            'name' => ['required', 'string', 'max:120'],
            'expires_at' => ['required', 'date'],
            // Nie die ganze Ausweisnummer speichern.
            'reference_last4' => ['nullable', 'string', 'max:4'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['reference_last4.max' => 'Nur die letzten 4 Zeichen eintragen.']);
    }
}
