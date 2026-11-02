<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LetterController extends Controller
{
    public function edit(Request $request, Contract $contract): View
    {
        $this->owns($contract);
        $user = $request->user();

        $termEnd = $contract->next_term_end?->format('d.m.Y');
        $reference = $contract->customer_number ? ", Kunden-/Policennummer {$contract->customer_number}" : '';

        $body = implode("\n\n", array_filter([
            'Sehr geehrte Damen und Herren',
            $termEnd
                ? "Hiermit kündige ich den oben genannten Vertrag fristgerecht per {$termEnd}, eventualiter auf den nächstmöglichen Termin."
                : 'Hiermit kündige ich den oben genannten Vertrag fristgerecht auf den nächstmöglichen Termin.',
            $contract->category?->slug === 'krankenkasse'
                ? 'Den Versicherungsnachweis meiner neuen Krankenkasse reiche ich nach, sobald er vorliegt.'
                : null,
            'Bitte bestätigen Sie mir die Kündigung und das genaue Vertragsende schriftlich.',
            'Freundliche Grüsse',
        ]));

        return view('contracts.letter', [
            'contract' => $contract,
            'sender' => trim(implode("\n", array_filter([$user->name, $user->street, trim("{$user->zip} {$user->city}")]))),
            'recipient' => $contract->cancellation_address ?: $contract->provider,
            'place' => $user->city ?: 'St. Gallen',
            'subject' => "Kündigung {$contract->name}{$reference}",
            'body' => $body,
            'signature' => $user->name,
        ]);
    }

    public function download(Request $request, Contract $contract): Response
    {
        $this->owns($contract);

        $data = $request->validate([
            'sender' => ['required', 'string', 'max:500'],
            'recipient' => ['required', 'string', 'max:500'],
            'place' => ['required', 'string', 'max:80'],
            'date' => ['required', 'date'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'signature' => ['required', 'string', 'max:120'],
            'registered' => ['nullable', 'boolean'],
        ]);

        $pdf = Pdf::loadView('pdf.letter', $data + ['contract' => $contract])->setPaper('a4');
        $filename = 'Kuendigung-'.Str::slug($contract->provider.' '.$contract->name).'.pdf';
        $path = 'documents/'.$request->user()->id.'/'.Str::uuid().'.pdf';
        $output = $pdf->output();

        Storage::disk('local')->put($path, $output);

        $contract->documents()->create([
            'user_id' => $request->user()->id,
            'kind' => 'letter',
            'path' => $path,
            'original_name' => $filename,
            'mime' => 'application/pdf',
            'size' => strlen($output),
        ]);

        if ($contract->status === ContractStatus::Active) {
            $contract->status = ContractStatus::CancellationPlanned;
            $contract->save();
        }

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
