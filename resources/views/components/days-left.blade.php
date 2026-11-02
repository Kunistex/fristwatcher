@props(['days' => null, 'urgency' => 'none'])
@php
    $color = match ($urgency) {
        'critical', 'expired' => 'text-due-critical',
        'soon' => 'text-due-soon',
        'ok' => 'text-due-ok',
        default => 'text-ink-soft',
    };
    $label = match (true) {
        $days === null => 'keine Frist',
        $days < -1 => 'vor '.abs($days).' Tagen',
        $days === -1 => 'gestern',
        $days === 0 => 'heute',
        $days === 1 => 'morgen',
        default => "in {$days} Tagen",
    };
@endphp
<span {{ $attributes->merge(['class' => "font-semibold {$color}"]) }}>{{ $label }}</span>
