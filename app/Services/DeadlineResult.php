<?php

namespace App\Services;

use Carbon\CarbonImmutable;

final class DeadlineResult
{
    public function __construct(
        public readonly ?CarbonImmutable $termEnd = null,
        public readonly ?CarbonImmutable $receiptDeadline = null,
        public readonly ?CarbonImmutable $sendBy = null,
        public readonly ?string $note = null,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'term_end' => $this->termEnd?->toDateString(),
            'receipt_deadline' => $this->receiptDeadline?->toDateString(),
            'send_by' => $this->sendBy?->toDateString(),
            'term_end_label' => $this->termEnd?->translatedFormat('j. F Y'),
            'receipt_deadline_label' => $this->receiptDeadline?->translatedFormat('l, j. F Y'),
            'send_by_label' => $this->sendBy?->translatedFormat('l, j. F Y'),
            'note' => $this->note,
        ];
    }
}
