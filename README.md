# Solve

منصة SaaS عربية لإدارة وبيع المتاجر للشركاء والتجار، مبنية على Laravel 12 وواجهة RTL. تحتوي على لوحة Super Admin، لوحة تاجر معزولة حسب `store_id`، إدارة طلبات ومنتجات وتحليلات وإعدادات، وربط فعلي بقاعدة البيانات وواجهات API داخلية.

## المتطلبات

- PHP 8.2 أو أحدث
- Composer
- Node.js و npm
- MySQL
- Queue worker في الإنتاج
- HTTPS ودومين فعلي

## التشغيل المحلي

```bash
composer install
npm install
copy env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

## إعداد الإنتاج

اضبط ملف `.env` بقيم إنتاج حقيقية:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

أنشئ hash آمن لكلمة مرور الأدمن:

```bash
php artisan solve:admin-hash "StrongAdminPasswordHere"
```

ثم ضعه في:

```env
ADMIN_USERNAME=owner@your-domain.example
ADMIN_PASSWORD=
ADMIN_PASSWORD_HASH=ضع_الناتج_هنا
```

في الإنتاج لا يتم قبول `ADMIN_PASSWORD` النصية. يجب استخدام `ADMIN_PASSWORD_HASH`.

## أوامر النشر

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work --tries=3 --timeout=90
```

## فحص الجاهزية

بعد تسجيل دخول الأدمن افتح:

```text
/admin/production-readiness
```

الصفحة تفحص المتطلبات الحرجة قبل بيع المتاجر:

- بيئة الإنتاج و `APP_DEBUG`
- `APP_KEY` و HTTPS
- حساب أدمن مشفر
- جلسات مشفرة وآمنة
- session/cache/queue إنتاجية
- migrations وجداول SaaS
- وجود متاجر وحسابات تجار
- مزود بريد فعلي

لا تطلق المنصة تجارياً قبل أن تصبح النتيجة جاهزة.

## المسارات الأساسية

- `/admin` لوحة Super Admin
- `/admin/stores` إدارة المتاجر وإنشاء حسابات التجار
- `/admin/production-readiness` فحص جاهزية الإنتاج
- `/partner/login` دخول التاجر
- `/partner/dashboard` لوحة التاجر
- `/partner/orders` الطلبات
- `/partner/products` المنتجات
- `/partner/settings` إعدادات المتجر
- `/partner/analytics` التحليلات

## الاختبارات والفحص الأمني

```bash
php artisan test
composer audit
npm audit --omit=dev
```

## ملاحظات تشغيلية

- بيانات التاجر معزولة حسب `store_id`.
- Super Admin يرى كل المتاجر، والتاجر يرى متجره فقط.
- إنشاء أو تعديل متجر من الأدمن يجهز حساب التاجر ويربطه بقاعدة البيانات.
- أي تكامل خارجي مثل الدفع، الشحن، البريد، SMS، أو التخزين السحابي يجب ضبط مفاتيحه الحقيقية في `.env` قبل البيع الفعلي.
