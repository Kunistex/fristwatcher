@php
    $today ??= now()->startOfDay();
    $urg = $c->urgency($today);
@endphp
<li>
    <a href="{{ route('contracts.show', $c) }}" class="group flex items-center gap-4 px-4 py-3.5 hover:bg-paper/60">
        <x-date-tile :date="$c->status->watchesDeadline() ? $c->send_by : null" :urgency="$urg" />
        <div class="min-w-0 flex-1">
            <p class="truncate text-[16px] font-medium">{{ $c->name }}</p>
            <p class="truncate text-[14px] text-ink-soft">{{ $c->provider }}@if($c->cost_amount > 0), CHF @chf($c->cost_amount) {{ $c->cost_interval->label() }}@endif</p>
        </div>
        <div class="hidden text-right text-[14px] sm:block">
            @if ($c->status->watchesDeadline())
                <x-days-left :days="$c->daysUntilSendBy($today)" :urgency="$urg" />
                @if ($c->receipt_deadline)
                    <p class="text-[13px] text-ink-faint">Eingang bis {{ $c->receipt_deadline->format('d.m.Y') }}</p>
                @endif
            @else
                <x-status-badge :status="$c->status" />
            @endif
        </div>
        <x-icon name="chevron" class="size-4 text-ink-faint group-hover:text-ink" />
    </a>
</li>
