@php
    $apiUrl = $url ?? '#';
    $copyLabel = $copyLabel ?? 'نسخ API';
    $openLabel = $openLabel ?? 'فتح API';
@endphp

<div class="flex flex-wrap gap-2">
    <button
        type="button"
        class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-black text-slate-700 transition hover:border-solve-200 hover:bg-solve-50 hover:text-solve-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-solve-700 dark:hover:bg-solve-950/40"
        onclick="navigator.clipboard?.writeText(@js($apiUrl)); const label = this.textContent; this.textContent = 'تم نسخ API'; setTimeout(() => this.textContent = label, 1400);"
    >
        {{ $copyLabel }}
    </button>
    <a
        href="{{ $apiUrl }}"
        target="_blank"
        rel="noopener"
        class="rounded-full bg-slate-950 px-4 py-2 text-xs font-black text-white transition hover:bg-solve-700 dark:bg-white dark:text-slate-950"
    >
        {{ $openLabel }}
    </a>
</div>
