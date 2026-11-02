@props(['name', 'class' => 'size-5'])
@php
    // Strichsymbole im Stil von Lucide (ISC-Lizenz), 24er Raster.
    $paths = [
        'overview' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'contracts' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'expiry' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.5 16a2.5 2.5 0 0 1 5 0M14 10h4M14 14h3"/>',
        'savings' => '<path d="M21 12a9 9 0 1 1-9-9v9z"/><path d="M15 3.5A9 9 0 0 1 20.5 9H15z"/>',
        'settings' => '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'bell' => '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
        'download' => '<path d="M12 4v11m0 0-4.5-4.5M12 15l4.5-4.5M5 20h14"/>',
        'upload' => '<path d="M12 16V5m0 0L7.5 9.5M12 5l4.5 4.5M5 20h14"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit' => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'letter' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'logout' => '<path d="M14 4h5v16h-5M10 8l-4 4 4 4M6 12h10"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'back' => '<path d="M15 5l-7 7 7 7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'heart' => '<path d="M12 20s-7.5-4.6-7.5-10A4.3 4.3 0 0 1 12 7.3 4.3 4.3 0 0 1 19.5 10c0 5.4-7.5 10-7.5 10z"/>',
        'shield' => '<path d="M12 3 5 6v5.5c0 4.3 3 7.8 7 9.5 4-1.7 7-5.2 7-9.5V6z"/>',
        'dumbbell' => '<path d="M6.5 7v10M17.5 7v10M3.5 9.5v5M20.5 9.5v5M6.5 12h11"/>',
        'phone' => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18h2"/>',
        'play' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m10 9 5 3-5 3z"/>',
        'app' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'home' => '<path d="M4 11 12 4l8 7M6 9.5V20h12V9.5"/>',
        'car' => '<path d="M5 16V11l2-5h10l2 5v5M3.5 16h17"/><circle cx="7.5" cy="16.5" r="1.5"/><circle cx="16.5" cy="16.5" r="1.5"/>',
        'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'trend' => '<path d="m3 17 6-6 4 4 8-8M15 7h6v6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['folder'] !!}</svg>
