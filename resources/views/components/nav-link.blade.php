@props(['href', 'icon', 'active' => false, 'variant' => 'side'])
@if ($variant === 'side')
    <a href="{{ $href }}" @if($active) aria-current="page" @endif
       class="flex items-center gap-3 rounded-lg px-3 py-2 text-[15px] font-medium {{ $active ? 'bg-accent-wash text-accent' : 'text-ink-soft hover:bg-paper hover:text-ink' }}">
        <x-icon :name="$icon" />
        <span>{{ $slot }}</span>
    </a>
@else
    <a href="{{ $href }}" @if($active) aria-current="page" @endif
       class="flex flex-1 flex-col items-center gap-0.5 pt-2 pb-1 text-[11px] font-medium {{ $active ? 'text-accent' : 'text-ink-faint' }}">
        <x-icon :name="$icon" class="size-[22px]" />
        <span>{{ $slot }}</span>
    </a>
@endif
