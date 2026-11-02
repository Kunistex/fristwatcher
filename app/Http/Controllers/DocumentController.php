<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function store(Request $request, Contract $contract): RedirectResponse
    {
        $this->owns($contract);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,heic,webp'],
            'kind' => ['required', Rule::in(['policy', 'letter', 'confirmation', 'other'])],
        ], ['file.max' => 'Die Datei darf höchstens 10 MB gross sein.']);

        $file = $data['file'];
        $path = $file->storeAs(
            'documents/'.$request->user()->id,
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            'local'
        );

        $contract->documents()->create([
            'user_id' => $request->user()->id,
            'kind' => $data['kind'],
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        // Eine hochgeladene Bestätigung schliesst den Kündigungsablauf ab.
        if ($data['kind'] === 'confirmation' && $contract->status->value === 'cancelled_unconfirmed') {
            $contract->status = \App\Enums\ContractStatus::CancelledConfirmed;
            $contract->save();
        }

        return back()->with('status', 'Dokument hochgeladen.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->owns($document);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->owns($document);
        $document->delete();

        return back()->with('status', 'Dokument gelöscht.');
    }
}
