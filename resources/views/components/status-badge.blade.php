@props(['status'])
@php
    $tone = match ($status->value) {
        'active' => 'border-line text-ink-soft',
        'cancellation_planned' => 'border-accent/40 bg-accent-wash text-accent',
        'cancelled_unconfirmed' => 'border-due-soon/40 bg-due-soon-wash text-due-soon',
        'cancelled_confirmed' => 'border-due-ok/40 bg-due-ok-wash text-due-ok',
        default => 'border-line bg-due-none-wash text-ink-faint',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-2.5 py-0.5 text-[12.5px] font-medium {$tone}"]) }}>{{ $status->label() }}</span>
