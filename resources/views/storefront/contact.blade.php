@extends('storefront.layout')

@section('title', 'اتصل بنا - ' . ($store['name'] ?? $partner['name']))

@section('content')
    <section class="section">
        <div class="wrap hero-grid">
            <div class="panel" style="padding:32px">
                <span class="section-kicker">تواصل معنا</span>
                <h1 style="font-size:42px;margin:14px 0">{{ $store['name'] ?? $partner['name'] }}</h1>
                <p class="muted" style="line-height:2">نستقبل استفساراتك عبر النموذج أو بيانات التواصل الرسمية المسجلة من لوحة التاجر.</p>
                <div style="display:grid;gap:12px;margin-top:18px">
                    <div class="mini-stat"><span class="muted">الجوال</span><strong style="font-size:18px">{{ $settings['contact_phone'] ?? $partner['phone'] ?? '-' }}</strong></div>
                    <div class="mini-stat"><span class="muted">البريد</span><strong style="font-size:18px">{{ $settings['contact_email'] ?? $partner['email'] ?? '-' }}</strong></div>
                    <div class="mini-stat"><span class="muted">أوقات العمل</span><strong style="font-size:18px">{{ $settings['working_hours'] ?? '-' }}</strong></div>
                </div>
            </div>
            <form id="contactForm" class="panel" style="padding:32px;display:grid;gap:12px">
                <span class="badge">رسالة مباشرة</span>
                <input class="input" name="name" placeholder="اسمك" required>
                <input class="input" name="contact" placeholder="بريدك أو جوالك" required>
                <textarea class="input" name="message" style="height:150px;padding-top:14px" placeholder="رسالتك" required></textarea>
                <button class="btn btn-primary" type="submit">إرسال الرسالة</button>
                <p id="contactResult" class="muted" style="display:none;margin:0"></p>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
<script>
document.getElementById('contactForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = new FormData(event.currentTarget);
    const response = await fetch(window.solveStorefront.contactEndpoint, {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({name: form.get('name'), contact: form.get('contact'), message: form.get('message')})
    });
    const result = await response.json();
    const target = document.getElementById('contactResult');
    target.style.display = 'block';
    target.textContent = result.message || (response.ok ? 'تم الإرسال.' : 'تعذر الإرسال.');
});
</script>
@endpush
