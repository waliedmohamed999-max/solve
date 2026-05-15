@php
    $paths = [
        'layout-dashboard' => '<rect x="3" y="3" width="7" height="8" rx="2"></rect><rect x="14" y="3" width="7" height="5" rx="2"></rect><rect x="14" y="12" width="7" height="9" rx="2"></rect><rect x="3" y="15" width="7" height="6" rx="2"></rect>',
        'shopping-bag' => '<path d="M6 8h12l-1 13H7L6 8Z"></path><path d="M9 8a3 3 0 0 1 6 0"></path>',
        'package' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"></path><path d="M12 12 4.5 7.8"></path><path d="M12 12l7.5-4.2"></path><path d="M12 12v9"></path>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.9"></path><path d="M16 3.1a4 4 0 0 1 0 7.8"></path>',
        'megaphone' => '<path d="m3 11 18-5v12L3 14v-3Z"></path><path d="M7 14v5a2 2 0 0 0 2 2h1"></path>',
        'bar-chart' => '<path d="M3 3v18h18"></path><rect x="7" y="11" width="3" height="6" rx="1"></rect><rect x="13" y="7" width="3" height="10" rx="1"></rect><rect x="19" y="4" width="3" height="13" rx="1" transform="translate(-1)"></rect>',
        'wallet' => '<path d="M4 7h16a2 2 0 0 1 2 2v10H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14"></path><path d="M16 13h6"></path>',
        'plug' => '<path d="M12 22v-5"></path><path d="M9 8V2"></path><path d="M15 8V2"></path><path d="M7 8h10v4a5 5 0 0 1-10 0V8Z"></path>',
        'store' => '<path d="M4 10h16l-1-6H5l-1 6Z"></path><path d="M5 10v10h14V10"></path><path d="M9 20v-6h6v6"></path>',
        'truck' => '<path d="M3 7h11v10H3z"></path><path d="M14 11h4l3 3v3h-7z"></path><circle cx="7" cy="19" r="2"></circle><circle cx="18" cy="19" r="2"></circle>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="3" width="7" height="7" rx="2"></rect><rect x="14" y="14" width="7" height="7" rx="2"></rect><rect x="3" y="14" width="7" height="7" rx="2"></rect>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4v-.2a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1L7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"></path>',
        'search' => '<circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path>',
        'star' => '<path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9L12 3Z"></path>',
        'bolt' => '<path d="m13 2-9 12h7l-1 8 9-12h-7l1-8Z"></path>',
        'moon' => '<path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.5 6.5 0 0 0 21 12.8Z"></path>',
        'menu' => '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path>',
        'x' => '<path d="M18 6 6 18"></path><path d="m6 6 12 12"></path>',
        'chevron' => '<path d="m9 18 6-6-6-6"></path>',
        'home' => '<path d="m3 11 9-8 9 8"></path><path d="M5 10v10h14V10"></path><path d="M9 20v-6h6v6"></path>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"></path>',
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle>',
        'trash' => '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="m6 6 1 15h10l1-15"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>',
        'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"></rect><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"></path>',
        'monitor' => '<rect x="3" y="4" width="18" height="12" rx="2"></rect><path d="M8 21h8"></path><path d="M12 16v5"></path>',
        'tablet' => '<rect x="6" y="3" width="12" height="18" rx="2"></rect><path d="M11 18h2"></path>',
        'mobile' => '<rect x="7" y="2" width="10" height="20" rx="2"></rect><path d="M11 18h2"></path>',
        'undo' => '<path d="M9 14 4 9l5-5"></path><path d="M4 9h10a6 6 0 0 1 0 12h-1"></path>',
        'redo' => '<path d="m15 14 5-5-5-5"></path><path d="M20 9H10a6 6 0 0 0 0 12h1"></path>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8" cy="10" r="2"></circle><path d="m21 15-5-5L5 19"></path>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h6"></path>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"></path><path d="M14 11a5 5 0 0 0-7.1 0l-2 2a5 5 0 0 0 7.1 7.1l1.1-1.1"></path>',
        'sparkles' => '<path d="m12 3 1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3Z"></path><path d="m5 14 .8 2.2L8 17l-2.2.8L5 20l-.8-2.2L2 17l2.2-.8L5 14Z"></path><path d="m19 14 .8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14Z"></path>',
        'clock' => '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
        'message-circle' => '<path d="M21 11.5a8.4 8.4 0 0 1-1 4 8.5 8.5 0 0 1-7.5 4.5 8.4 8.4 0 0 1-4-.9L3 21l1.8-5.2a8.4 8.4 0 0 1-.8-3.8 8.5 8.5 0 0 1 17-.5Z"></path>',
        'layout' => '<rect x="3" y="4" width="18" height="16" rx="2"></rect><path d="M3 10h18"></path><path d="M9 10v10"></path>',
    ];
@endphp

<svg class="{{ $class ?? 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name ?? 'grid'] ?? $paths['grid'] !!}
</svg>
