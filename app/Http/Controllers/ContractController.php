<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Http\Requests\ContractRequest;
use App\Models\Category;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Services\DeadlineCalculator;
use App\Support\FixedDates;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter', 'open')->toString();
        $category = $request->integer('category') ?: null;
        $q = trim($request->string('q')->toString());

        $contracts = $request->user()->contracts()
            ->with('category')
            ->when($filter === 'open', fn ($query) => $query->whereIn('status', ['active', 'cancellation_planned']))
            ->when($filter === 'cancelled', fn ($query) => $query->whereIn('status', ['cancelled_unconfirmed', 'cancelled_confirmed']))
            ->when($filter === 'ended', fn ($query) => $query->where('status', 'ended'))
            ->when($category, fn ($query) => $query->where('category_id', $category))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('provider', 'like', "%{$q}%")
                ->orWhere('customer_number', 'like', "%{$q}%")))
            ->orderByRaw('send_by is null')
            ->orderBy('send_by')
            ->orderBy('name')
            ->get();

        return view('contracts.index', [
            'contracts' => $contracts,
            'filter' => $filter,
            'category' => $category,
            'q' => $q,
            'categories' => Category::visibleTo($request->user())->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $templates = ContractTemplate::with('category')->orderBy('sort')->get();
        $template = $request->filled('template')
            ? $templates->firstWhere('slug', $request->string('template')->toString())
            : null;

        if (! $template) {
            return view('contracts.pick-template', ['templates' => $templates]);
        }

        $d = $template->defaults;
        $contract = new Contract([
            'template_id' => $template->id,
            'category_id' => $template->category_id,
            'name' => $template->slug === 'eigener-vertrag' ? '' : $template->name,
            'cost_interval' => $d['cost_interval'] ?? 'monthly',
            'start_date' => now()->startOfMonth(),
            'notice_anchor' => $d['notice_anchor'] ?? 'term_end',
            'fixed_end_dates' => $d['fixed_end_dates'] ?? null,
            'min_term_months' => $d['min_term_months'] ?? null,
            'renewal_months' => $d['renewal_months'] ?? null,
            'notice_value' => $d['notice_value'] ?? 0,
            'notice_unit' => $d['notice_unit'] ?? 'months',
            'send_buffer_days' => $d['send_buffer_days'] ?? null,
        ]);

        return $this->form($request, $contract, $template);
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $contract = $request->user()->contracts()->create($request->contractData());

        return redirect()->route('contracts.show', $contract)->with('status', 'Vertrag gespeichert.');
    }

    public function show(Request $request, Contract $contract, DeadlineCalculator $calculator): View
    {
        $this->owns($contract);
        $contract->load(['category', 'template', 'priceChanges', 'documents']);

        return view('contracts.show', [
            'contract' => $contract,
            'deadline' => $calculator->forContract($contract, null, $request->user()),
            'reminders' => $contract->reminders()->orderBy('due_on')->get(),
            'today' => now()->startOfDay(),
        ]);
    }

    public function edit(Request $request, Contract $contract): View
    {
        $this->owns($contract);

        return $this->form($request, $contract, $contract->template);
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->owns($contract);
        $contract->update($request->contractData());

        return redirect()->route('contracts.show', $contract)->with('status', 'Änderungen gespeichert.');
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->owns($contract);
        $contract->delete();

        return redirect()->route('contracts.index')->with('status', "{$contract->name} gelöscht.");
    }

    /** Live-Vorschau der Fristen im Formular. */
    public function preview(Request $request, DeadlineCalculator $calculator): JsonResponse
    {
        $user = $request->user();

        $result = $calculator->calculate([
            'start_date' => $request->input('start_date'),
            'min_term_months' => $request->integer('min_term_months') ?: null,
            'renewal_months' => $request->integer('renewal_months') ?: null,
            'notice_anchor' => $request->input('notice_anchor'),
            'fixed_end_dates' => FixedDates::parse($request->input('fixed_end_dates_text')),
            'notice_value' => $request->integer('notice_value'),
            'notice_unit' => $request->input('notice_unit'),
            'send_buffer_days' => $request->filled('send_buffer_days') ? $request->integer('send_buffer_days') : $user->sendBufferDays(),
        ], null, $user->canton ?? 'SG');

        return response()->json($result->toArray());
    }

    private function form(Request $request, Contract $contract, ?ContractTemplate $template): View
    {
        return view('contracts.form', [
            'contract' => $contract,
            'template' => $template,
            'categories' => Category::visibleTo($request->user())->get(),
            'costValue' => old('cost', $contract->cost_amount ? Money::format($contract->cost_amount) : ''),
            'fixedDatesText' => old('fixed_end_dates_text', FixedDates::format($contract->fixed_end_dates)),
            'offsetsText' => old('reminder_offsets_text', implode(', ', $contract->reminder_offsets ?? [])),
            'defaultOffsets' => implode(', ', $request->user()->reminderOffsets()),
            'defaultBuffer' => $request->user()->sendBufferDays(),
            'statuses' => ContractStatus::cases(),
        ]);
    }
}
