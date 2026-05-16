import { cpSync, existsSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const root = process.cwd();
const publicDir = join(root, 'public');
const distDir = join(root, 'dist');

if (!existsSync(publicDir)) {
  throw new Error('Cannot prepare Vercel output: public directory is missing.');
}

rmSync(distDir, { recursive: true, force: true });
mkdirSync(distDir, { recursive: true });

cpSync(publicDir, distDir, {
  recursive: true,
  filter(source) {
    const normalized = source.replaceAll('\\', '/');

    return !normalized.endsWith('/public/hot')
      && !normalized.includes('/public/storage');
  },
});

const html = String.raw`<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solve - منصة إنشاء المتاجر الإلكترونية</title>
  <meta name="description" content="منصة Solve تساعدك على إنشاء متجر إلكتروني احترافي وإدارة المنتجات والطلبات والباقات من مكان واحد.">
  <link rel="icon" href="/solve-logo.png">
  <style>
    :root {
      --ink: #090f1f;
      --muted: #64748b;
      --line: #dbe4f0;
      --soft: #f6f8fc;
      --brand: #6d28d9;
      --brand-2: #0ea5e9;
      --yellow: #ffe600;
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      font-family: Tahoma, Arial, sans-serif;
      background: radial-gradient(circle at 20% 0%, #f2ecff 0, transparent 28%), #f7f9fc;
      color: var(--ink);
      overflow-x: hidden;
    }
    a { color: inherit; text-decoration: none; }
    .topbar {
      background: var(--yellow);
      color: #15051f;
      min-height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 14px;
    }
    .nav {
      position: sticky;
      top: 0;
      z-index: 20;
      backdrop-filter: blur(18px);
      background: rgba(255,255,255,.9);
      border-bottom: 1px solid var(--line);
    }
    .nav-inner, .wrap {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
    }
    .nav-inner {
      min-height: 78px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 950;
    }
    .brand img { width: 58px; height: 58px; object-fit: contain; border: 1px solid var(--line); border-radius: 18px; background: #fff; }
    .links { display: flex; gap: 26px; align-items: center; color: #475569; font-weight: 800; }
    .actions { display: flex; gap: 10px; align-items: center; }
    .btn {
      min-height: 46px;
      padding: 0 22px;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 950;
      box-shadow: 0 12px 30px rgba(15,23,42,.06);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 18px 44px rgba(15,23,42,.1); }
    .btn.primary { background: var(--brand); color: #fff; border-color: var(--brand); }
    .hero {
      padding: 68px 0 34px;
    }
    .hero-grid {
      display: grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 42px;
      align-items: center;
    }
    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #d9cffd;
      background: #f2edff;
      color: #5b21b6;
      border-radius: 999px;
      padding: 9px 14px;
      font-weight: 950;
      margin-bottom: 18px;
    }
    h1 {
      margin: 0;
      font-size: clamp(38px, 5.4vw, 74px);
      line-height: 1.12;
      letter-spacing: 0;
    }
    .lead {
      margin: 18px 0 0;
      color: var(--muted);
      font-size: clamp(17px, 1.8vw, 22px);
      line-height: 1.9;
      max-width: 720px;
    }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
    .stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 30px;
    }
    .stat {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 22px;
      padding: 18px;
      box-shadow: 0 20px 50px rgba(15,23,42,.06);
    }
    .stat b { display: block; font-size: 24px; }
    .stat span { color: var(--muted); font-weight: 800; font-size: 13px; }
    .visual {
      background: linear-gradient(135deg, #f9fbff, #eef5ff);
      border: 1px solid var(--line);
      border-radius: 34px;
      padding: 24px;
      box-shadow: 0 28px 80px rgba(15,23,42,.1);
      overflow: hidden;
    }
    .visual img {
      width: 100%;
      max-height: 520px;
      object-fit: contain;
      display: block;
    }
    section { padding: 48px 0; }
    .section-head { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin-bottom: 22px; }
    .section-head h2 { margin: 0; font-size: clamp(28px, 3vw, 42px); }
    .section-head p { margin: 8px 0 0; color: var(--muted); line-height: 1.8; }
    .pricing-shell {
      background: #260538;
      color: #fff;
      border-radius: 34px;
      padding: 30px;
      overflow: hidden;
      box-shadow: 0 30px 90px rgba(38,5,56,.18);
    }
    .billing-toggle {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
      font-weight: 900;
    }
    .save-pill { background: #9cf68b; color: #14210f; padding: 7px 12px; border-radius: 999px; font-size: 12px; }
    .plans { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .plan {
      min-height: 100%;
      background: #fff;
      color: var(--ink);
      border-radius: 20px;
      padding: 24px;
      border: 1px solid rgba(255,255,255,.22);
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .plan.dark { background: #4a2167; color: #fff; }
    .plan h3 { margin: 0; font-size: 26px; }
    .price { font-size: 38px; font-weight: 950; }
    .price small { font-size: 13px; color: currentColor; opacity: .75; }
    .feature-list { display: grid; gap: 11px; margin: 0; padding: 0; list-style: none; color: inherit; }
    .feature-list li { display: flex; gap: 8px; align-items: flex-start; font-weight: 800; font-size: 14px; line-height: 1.7; }
    .feature-list li::before { content: "✓"; color: #a855f7; font-weight: 950; }
    .plan.dark .feature-list li::before { color: #fff; }
    .features {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }
    .feature-card {
      background: #fff;
      border: 1px solid var(--line);
      border-radius: 24px;
      padding: 24px;
      box-shadow: 0 22px 70px rgba(15,23,42,.06);
    }
    .feature-card h3 { margin: 0 0 8px; font-size: 22px; }
    .feature-card p { margin: 0; color: var(--muted); line-height: 1.8; }
    .footer {
      margin-top: 40px;
      background: #071121;
      color: #dbeafe;
      padding: 34px 0;
    }
    .footer-grid {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr;
      gap: 24px;
      align-items: start;
    }
    .footer h3 { color: #fff; margin: 0 0 10px; }
    .footer p, .footer a { color: #b6c7e3; line-height: 1.9; }
    @media (max-width: 980px) {
      .hero-grid, .plans, .features, .footer-grid { grid-template-columns: 1fr; }
      .links { display: none; }
      .stats { grid-template-columns: 1fr; }
      .pricing-shell { padding: 18px; border-radius: 26px; }
    }
  </style>
</head>
<body>
  <div class="topbar">خصم 19% على جميع الباقات | استخدم الكود: SOLVE15</div>
  <header class="nav">
    <div class="nav-inner">
      <a class="brand" href="/">
        <img src="/solve-logo.png" alt="Solve">
        <span>Solve</span>
      </a>
      <nav class="links" aria-label="التنقل الرئيسي">
        <a href="#pricing">الباقات</a>
        <a href="#features">الخدمات</a>
        <a href="#features">المنتجات</a>
        <a href="/login">تسجيل الدخول</a>
      </nav>
      <div class="actions">
        <a class="btn" href="/login">دخول</a>
        <a class="btn primary" href="/merchant/register">انضم كتاجر</a>
      </div>
    </div>
  </header>
  <main>
    <section class="hero">
      <div class="wrap hero-grid">
        <div>
          <span class="eyebrow">منصة عربية لبناء المتاجر</span>
          <h1>أنشئ متجر إلكتروني احترافي وابدأ البيع بسرعة</h1>
          <p class="lead">Solve تجمع المتجر، المنتجات، الطلبات، الباقات، الذكاء الاصطناعي، وواجهة المتجر في تجربة واحدة جاهزة للنمو.</p>
          <div class="hero-actions">
            <a class="btn primary" href="/merchant/register">ابدأ مجاناً</a>
            <a class="btn" href="#pricing">شاهد الباقات</a>
          </div>
          <div class="stats">
            <div class="stat"><b>24/7</b><span>دعم وتشغيل</span></div>
            <div class="stat"><b>AI</b><span>ذكاء Solve للتاجر</span></div>
            <div class="stat"><b>Free</b><span>ابدأ بدون تكلفة</span></div>
          </div>
        </div>
        <div class="visual">
          <img src="/منصة_متاجر.png" alt="واجهة منصة Solve لإنشاء المتاجر">
        </div>
      </div>
    </section>
    <section id="pricing">
      <div class="wrap">
        <div class="section-head">
          <div>
            <h2>باقات Solve</h2>
            <p>اختر الباقة المناسبة لمرحلة متجرك، ويمكنك الترقية لاحقاً من لوحة التحكم.</p>
          </div>
        </div>
        <div class="pricing-shell">
          <div class="billing-toggle"><span>شهري</span><span class="save-pill">كاش باك 50%</span><span>سنوي</span></div>
          <div class="plans">
            <article class="plan">
              <h3>البداية</h3>
              <div class="price">مجاناً <small>مدى الحياة</small></div>
              <a class="btn" href="/merchant/register">ابدأ مجاناً</a>
              <ul class="feature-list">
                <li>إضافة منتجاتك في السوق.</li>
                <li>عدد غير محدود من المنتجات والطلبات والعملاء.</li>
                <li>كوبونات خصم وأسئلة وتقييمات.</li>
                <li>طرق دفع أساسية وربط شحن.</li>
              </ul>
            </article>
            <article class="plan">
              <h3>الانطلاقة</h3>
              <div class="price">99 <small>ريال شهرياً</small></div>
              <a class="btn primary" href="/merchant/register">ابدأ الآن</a>
              <ul class="feature-list">
                <li>استرداد نقدي 50% على الباقة السنوية.</li>
                <li>صلاحية إدارة المتجر لشخص إضافي.</li>
                <li>بوابة زر لرفع سرعة الدفع.</li>
                <li>ربط دومين خاص لتعزيز الثقة.</li>
              </ul>
            </article>
            <article class="plan">
              <h3>النمو</h3>
              <div class="price">299 <small>ريال شهرياً</small></div>
              <a class="btn primary" href="/merchant/register">ابدأ الآن</a>
              <ul class="feature-list">
                <li>خصائص الانطلاقة بالإضافة إلى أدوات نمو متقدمة.</li>
                <li>إدارة 5 أشخاص إضافيين.</li>
                <li>تقسيم العملاء لعروض حصرية.</li>
                <li>تخصيص الواجهة و CSS.</li>
              </ul>
            </article>
            <article class="plan dark">
              <h3>الإحترافية</h3>
              <div class="price">قيمة مخصصة <small>تدفع سنوياً</small></div>
              <a class="btn primary" href="/merchant/register">تواصل مع فريقنا</a>
              <ul class="feature-list">
                <li>أولوية الدعم 24/7.</li>
                <li>مدير علاقات مخصص.</li>
                <li>تقارير ربحية وتكاليف متقدمة.</li>
                <li>ربط متجرك بأنظمة خارجية عبر API.</li>
              </ul>
            </article>
          </div>
        </div>
      </div>
    </section>
    <section id="features">
      <div class="wrap">
        <div class="section-head">
          <div>
            <h2>خدمات ومنتجات Solve</h2>
            <p>كل ما يحتاجه التاجر لتشغيل المتجر اليومي والنمو من مكان واحد.</p>
          </div>
        </div>
        <div class="features">
          <article class="feature-card"><h3>لوحة تاجر ذكية</h3><p>طلبات، منتجات، عملاء، تحليلات، وتنبيهات تشغيلية واضحة.</p></article>
          <article class="feature-card"><h3>متجر إلكتروني جاهز</h3><p>واجهة متجر قابلة للتخصيص، بنرات، قوالب، وسلة دفع.</p></article>
          <article class="feature-card"><h3>ذكاء Solve</h3><p>أدوات AI لكتابة الوصف، تحسين SEO، وتحليل المبيعات والمخزون.</p></article>
        </div>
      </div>
    </section>
  </main>
  <footer class="footer">
    <div class="wrap footer-grid">
      <div><h3>Solve</h3><p>منصة SaaS عربية لإنشاء وتشغيل المتاجر الإلكترونية.</p></div>
      <div><h3>روابط</h3><p><a href="#pricing">الباقات</a><br><a href="#features">الخدمات</a><br><a href="/login">تسجيل الدخول</a></p></div>
      <div><h3>ابدأ الآن</h3><p>أنشئ متجرك وشاهد لوحة التحكم والباقات خلال دقائق.</p><a class="btn primary" href="/merchant/register">انضم كتاجر</a></div>
    </div>
  </footer>
</body>
</html>`;

writeFileSync(join(distDir, 'index.html'), html, 'utf8');

console.log('Prepared Vercel static output in dist/.');
