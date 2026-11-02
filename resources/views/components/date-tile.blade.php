@props(['date' => null, 'urgency' => 'none', 'size' => 'md'])
@php
    $tone = match ($urgency) {
        'critical', 'expired' => 'text-due-critical bg-due-critical-wash border-due-critical/30',
        'soon' => 'text-due-soon bg-due-soon-wash border-due-soon/30',
        'ok' => 'text-due-ok bg-due-ok-wash border-due-ok/30',
        default => 'text-due-none bg-due-none-wash border-line',
    };
    $dims = match ($size) {
        'lg' => ['w-[104px]', 'text-[13px] py-1.5', 'text-[52px] leading-none pt-2.5', 'text-[13px] pb-2.5'],
        'sm' => ['w-12', 'text-[10.5px] py-0.5', 'text-[20px] leading-none pt-1', 'text-[10.5px] pb-1'],
        default => ['w-[60px]', 'text-[11.5px] py-1', 'text-[26px] leading-none pt-1.5', 'text-[11.5px] pb-1.5'],
    };
@endphp
{{-- Kalenderblatt: Monat oben im Ampelton, Tag gross, Wochentag klein. --}}
<div {{ $attributes->merge(['class' => "shrink-0 overflow-hidden rounded-tile border bg-sheet text-center {$dims[0]} ".explode(' ', $tone)[2]]) }}>
    @if ($date)
        <div class="font-semibold {{ $dims[1] }} {{ explode(' ', $tone)[0] }} {{ explode(' ', $tone)[1] }}">{{ $date->translatedFormat('M') }}</div>
        <div class="font-semibold tracking-tight text-ink {{ $dims[2] }}">{{ $date->format('j') }}</div>
        <div class="text-ink-soft {{ $dims[3] }}">{{ $date->translatedFormat('D') }}</div>
    @else
        <div class="{{ $dims[1] }} text-due-none bg-due-none-wash">&nbsp;</div>
        <div class="text-ink-faint {{ $dims[2] }}">–</div>
        <div class="{{ $dims[3] }}">&nbsp;</div>
    @endif
</div>
