<div x-data="{ open: false, title: 'تأكيد العملية', body: 'هل تريد تنفيذ هذا الإجراء؟' }"
    x-on:confirm-action.window="open = true; title = $event.detail.title || 'تأكيد العملية'; body = $event.detail.body || 'هل تريد تنفيذ هذا الإجراء؟'"
    x-on:keydown.escape.window="open = false">
    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-[60] bg-slate-950/35 backdrop-blur-sm" @click="open = false"></div>
    <div x-cloak x-show="open" x-transition class="fixed left-1/2 top-1/2 z-[61] w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-3xl bg-white p-6 shadow-soft">
        <h3 class="text-xl font-black text-slate-900" x-text="title"></h3>
        <p class="mt-3 text-sm leading-6 text-slate-500" x-text="body"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600" @click="open = false">إلغاء</button>
            <button type="button" class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-bold text-white" @click="open = false; $dispatch('solve-toast', 'تم تأكيد العملية')">تأكيد</button>
        </div>
    </div>
</div>
