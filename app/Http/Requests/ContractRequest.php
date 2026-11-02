<?php

namespace App\Http\Requests;

use App\Enums\CostInterval;
use App\Enums\NoticeAnchor;
use App\Enums\NoticeUnit;
use App\Models\Category;
use App\Support\FixedDates;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $offsets = collect(preg_split('/[,;\s]+/', (string) $this->input('reminder_offsets_text', '')))
            ->filter(fn ($v) => $v !== '' && is_numeric($v))
            ->map(fn ($v) => (int) $v)->unique()->sortDesc()->values()->all();

        $this->merge([
            'cost_amount' => Money::parse($this->input('cost')),
            'fixed_end_dates' => FixedDates::parse($this->input('fixed_end_dates_text')),
            'reminder_offsets' => $offsets ?: null,
            'min_term_months' => $this->filled('min_term_months') ? $this->input('min_term_months') : null,
            'renewal_months' => $this->filled('renewal_months') ? $this->input('renewal_months') : null,
            'send_buffer_days' => $this->filled('send_buffer_days') ? $this->input('send_buffer_days') : null,
            'notice_value' => $this->input('notice_value', 0) ?: 0,
        ]);
    }

    public function rules(): array
    {
        $categoryIds = Category::visibleTo($this->user())->pluck('id')->all();

        return [
            'template_id' => ['nullable', 'integer', 'exists:contract_templates,id'],
            'category_id' => ['nullable', 'integer', Rule::in($categoryIds)],
            'name' => ['required', 'string', 'max:120'],
            'provider' => ['required', 'string', 'max:120'],
            'customer_number' => ['nullable', 'string', 'max:80'],
            'cost' => ['nullable', 'regex:/^[\d\'’ ]*([.,]\d{1,2})?$/'],
            'cost_amount' => ['integer', 'min:0', 'max:100000000'],
            'cost_interval' => ['required', Rule::enum(CostInterval::class)],
            'start_date' => ['required', 'date', 'after:1950-01-01', 'before:2100-01-01'],
            'notice_anchor' => ['required', Rule::enum(NoticeAnchor::class)],
            'min_term_months' => ['nullable', 'integer', 'min:0', 'max:240'],
            'renewal_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'fixed_end_dates' => ['array', Rule::requiredIf(fn () => $this->input('notice_anchor') === NoticeAnchor::FixedDates->value)],
            'fixed_end_dates.*' => ['string'],
            'notice_value' => ['integer', 'min:0', 'max:730'],
            'notice_unit' => ['required', Rule::enum(NoticeUnit::class)],
            'send_buffer_days' => ['nullable', 'integer', 'min:0', 'max:30'],
            'reminder_offsets' => ['nullable', 'array', 'max:6'],
            'reminder_offsets.*' => ['integer', 'min:0', 'max:365'],
            'cancellation_address' => ['nullable', 'string', 'max:500'],
            'cancellation_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fixed_end_dates.required' => 'Mindestens einen festen Termin eintragen, z.B. 31.12.',
            'cost.regex' => 'Betrag als Zahl eingeben, z.B. 79.90.',
        ];
    }

    /** @return array<string, mixed> */
    public function contractData(): array
    {
        $data = $this->safe()->except(['cost']);

        if (($data['notice_anchor'] ?? null) !== NoticeAnchor::FixedDates->value) {
            $data['fixed_end_dates'] = null;
        }

        return $data;
    }
}
