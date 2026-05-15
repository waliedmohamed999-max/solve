<div x-data="{ show: false, message: '' }"
    x-on:solve-toast.window="message = $event.detail; show = true; setTimeout(() => show = false, 2600)"
    x-show="show"
    x-cloak
    x-transition
    class="fixed left-6 top-6 z-50 rounded-2xl border border-emerald-100 bg-white px-5 py-4 text-sm font-bold text-emerald-700 shadow-soft">
    <span x-text="message"></span>
</div>
