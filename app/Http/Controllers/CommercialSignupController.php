<?php

namespace App\Http\Controllers;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformRecord;
use App\Models\StoreOnboardingStep;
use App\Support\PartnerDashboardSummary;
use App\Support\PartnerTenantStore;
use App\Support\PlatformAudit;
use App\Support\PlatformAudit as Audit;
use App\Support\SubscriptionPlans;
use App\Support\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommercialSignupController extends Controller
{
    public function create(Request $request): View
    {
        return view('site.signup', [
            'plans' => SubscriptionPlans::all(),
            'selectedPlan' => $request->query('plan', $request->is('merchant/*') ? 'Free' : 'Growth'),
            'registerAction' => $request->is('merchant/*') ? route('merchant.register.store') : route('commercial.signup.store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'owner_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180', 'unique:partner_users,username'],
            'phone' => ['required', 'string', 'max:40', 'unique:partner_stores,owner_phone'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'activity_type' => ['nullable', 'string', 'max:120'],
            'store_slug' => ['nullable', 'alpha_dash:ascii', 'max:80', Rule::unique('partner_stores', 'store_id')],
            'terms' => [$request->is('merchant/*') ? 'accepted' : 'nullable'],
            'plan' => ['nullable', 'string', 'in:' . implode(',', SubscriptionPlans::names())],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        abort_unless(Schema::hasTable('partner_stores') && Schema::hasTable('partner_users'), 503, 'Signup is not ready until tenant tables are migrated.');

        if (! empty($data['store_slug'])) {
            $requestedStoreId = Str::startsWith($data['store_slug'], 'store-') ? $data['store_slug'] : 'store-' . $data['store_slug'];
            if (PartnerStore::query()->where('store_id', $requestedStoreId)->exists()) {
                throw ValidationException::withMessages(['store_slug' => 'Store URL is already taken.']);
            }
        }

        $plan = SubscriptionPlans::find($data['plan'] ?? 'Free');
        $storeId = $this->uniqueStoreId($data['store_slug'] ?? $data['store_name']);
        $partnerId = Str::after($storeId, 'store-') ?: Str::random(8);
        $trialEndsAt = now()->addDays((int) $plan['trial_days']);
        $isFree = ($plan['name'] ?? '') === 'Free';

        $store = PartnerStore::query()->create([
            'partner_id' => $partnerId,
            'store_id' => $storeId,
            'name' => $data['store_name'],
            'brand_name' => $data['store_name'],
            'owner_name' => $data['owner_name'],
            'owner_email' => $data['email'],
            'owner_phone' => $data['phone'],
            'status' => $isFree ? 'free' : 'trial',
            'plan' => $plan['name'],
            'domain' => $storeId . '.solve.local',
            'store_url' => 'https://' . $storeId . '.solve.local',
            'logo' => 'solve-logo.png',
            'payment_status' => $isFree ? 'free' : 'trial',
            'subscription_started_at' => now()->toDateString(),
            'subscription_renews_at' => $isFree ? null : $trialEndsAt->toDateString(),
            'metadata' => [
                'source' => 'self_signup',
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'activity_type' => $data['activity_type'] ?? null,
                'trial_ends_at' => $isFree ? null : $trialEndsAt->toDateString(),
                'billing_cycle' => $plan['cycle'],
                'limits' => $plan['limits'],
                'features' => $plan['features'],
                'onboarding_stage' => 'New',
                'suggestions' => [
                    'أضف أول منتج حتى تظهر المتجر للعملاء.',
                    'فعّل بوابة دفع وشركة شحن قبل استقبال الطلبات.',
                    'اربط الدومين بعد نشر الهوية.',
                ],
            ],
        ]);

        $user = PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => $data['owner_name'],
            'username' => $data['email'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'role' => PartnerTenantStore::ROLE_PARTNER_ADMIN,
            'status' => 'active',
            'abilities' => ['*'],
            'last_login_at' => now(),
        ]);

        $this->createCommercialRecords($store, $plan, $trialEndsAt);

        $partner = PartnerTenantStore::findPartner($store->store_id);
        if ($partner) {
            PartnerDashboardSummary::ensureStoreData($partner);
        }

        $request->session()->regenerate();
        $request->session()->put('partner_user', [
            'name' => $user->name,
            'username' => $user->username,
            'role' => $user->role,
            'email' => $user->email,
            'abilities' => ['*'],
            'partner_id' => $store->partner_id,
            'store_id' => $store->store_id,
            'store_name' => $store->name,
        ]);

        PlatformAudit::activity('signup_completed', 'partner_store', $store->store_id, [
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'plan' => $store->plan,
            'trial_ends_at' => $plan['name'] === 'Free' ? null : $trialEndsAt->toDateString(),
        ], $request);

        return redirect()
            ->route($request->is('merchant/*') ? 'merchant.onboarding.plans' : 'partner.dashboard')
            ->with('status', 'تم إنشاء المتجر على الباقة المجانية. اختر الباقة المناسبة أو ابدأ الإعداد.');
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:180']]);

        return response()->json([
            'available' => ! PartnerUser::query()->where('username', $data['email'])->orWhere('email', $data['email'])->exists(),
        ]);
    }

    public function checkStoreSlug(Request $request): JsonResponse
    {
        $data = $request->validate(['store_slug' => ['required', 'alpha_dash:ascii', 'max:80']]);
        $storeId = Str::startsWith($data['store_slug'], 'store-') ? $data['store_slug'] : 'store-' . $data['store_slug'];

        return response()->json([
            'store_id' => $storeId,
            'available' => ! PartnerStore::query()->where('store_id', $storeId)->exists(),
        ]);
    }

    public function storeApi(Request $request): JsonResponse
    {
        $this->store($request);

        return response()->json(['created' => true, 'redirect' => route('merchant.onboarding.plans')], 201);
    }

    public function onboardingPlans(Request $request): View
    {
        $partner = $this->currentPartner($request);

        return view('site.merchant-plans', [
            'partner' => $partner,
            'plans' => SubscriptionManager::plans(false),
            'subscription' => SubscriptionManager::partnerSummary($partner)['subscription'],
        ]);
    }

    public function onboarding(Request $request): View
    {
        $partner = $this->currentPartner($request);

        return view('site.merchant-onboarding', [
            'partner' => $partner,
            'steps' => $this->onboardingPayload($partner),
        ]);
    }

    public function onboardingApi(Request $request): JsonResponse
    {
        return response()->json(['steps' => $this->onboardingPayload($this->currentPartner($request))]);
    }

    public function updateOnboardingApi(Request $request): JsonResponse
    {
        $partner = $this->currentPartner($request);
        $data = $request->validate([
            'step_key' => ['required', 'string', 'max:120'],
            'status' => ['required', 'in:pending,active,completed,skipped'],
            'payload' => ['nullable', 'array'],
        ]);

        StoreOnboardingStep::query()
            ->where('store_id', $partner['store_id'])
            ->where('step_key', $data['step_key'])
            ->update([
                'status' => $data['status'],
                'completed_at' => in_array($data['status'], ['completed', 'skipped'], true) ? now() : null,
                'payload' => array_merge(
                    StoreOnboardingStep::query()->where('store_id', $partner['store_id'])->where('step_key', $data['step_key'])->first()?->payload ?? [],
                    $data['payload'] ?? [],
                ),
            ]);

        return response()->json(['steps' => $this->onboardingPayload($partner)]);
    }

    public function completeOnboardingApi(Request $request): JsonResponse
    {
        $partner = $this->currentPartner($request);
        PartnerStore::query()->where('store_id', $partner['store_id'])->update([
            'metadata->onboarding_stage' => 'Completed',
            'metadata->onboarding_completed_at' => now()->toIso8601String(),
        ]);
        PlatformAudit::activity('merchant.onboarding_completed', 'partner_store', $partner['store_id'], ['store_id' => $partner['store_id']], $request);

        return response()->json(['completed' => true, 'redirect' => route('partner.dashboard')]);
    }

    private function uniqueStoreId(string $storeName): string
    {
        $slug = Str::slug($storeName) ?: Str::lower(Str::random(8));
        $base = Str::startsWith($slug, 'store-') ? $slug : 'store-' . $slug;
        $candidate = $base;
        $counter = 2;

        while (PartnerStore::query()->where('store_id', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function currentPartner(Request $request): array
    {
        $user = PartnerTenantStore::currentUser($request);
        $partner = PartnerTenantStore::currentPartner($request);

        abort_unless($user && $partner && ($user['store_id'] ?? null) === ($partner['store_id'] ?? null), 403);

        return $partner;
    }

    private function onboardingPayload(array $partner): array
    {
        return StoreOnboardingStep::query()
            ->where('store_id', $partner['store_id'])
            ->orderBy('id')
            ->get()
            ->map(fn (StoreOnboardingStep $step) => [
                'key' => $step->step_key,
                'title' => $step->title,
                'status' => $step->status,
                'completed_at' => $step->completed_at?->toIso8601String(),
                'description' => $step->payload['description'] ?? null,
                'tooltip' => $step->payload['tooltip'] ?? null,
                'cta' => $step->payload['cta'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function createCommercialRecords(PartnerStore $store, array $plan, \Carbon\CarbonInterface $trialEndsAt): void
    {
        foreach ($this->onboardingSteps() as $index => $step) {
            StoreOnboardingStep::query()->create([
                'store_id' => $store->store_id,
                'step_key' => $step['key'],
                'title' => $step['title'],
                'status' => $index === 0 ? 'completed' : 'pending',
                'completed_at' => $index === 0 ? now() : null,
                'payload' => [
                    'description' => $step['description'],
                    'tooltip' => $step['tooltip'],
                    'cta' => $step['cta'],
                    'source' => 'commercial_signup',
                ],
            ]);
        }

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'subscriptions', 'record_id' => 'subscription-' . $store->store_id],
            [
                'store_id' => $store->store_id,
                'partner_id' => $store->partner_id,
                'status' => $plan['name'] === 'Free' ? 'free' : 'trial',
                'payload' => [
                    'store' => $store->name,
                    'owner' => $store->owner_name,
                    'plan' => $plan['name'],
                    'status' => $plan['name'] === 'Free' ? 'free' : 'trial',
                    'billing_cycle' => $plan['cycle'],
                    'amount' => $plan['price'] ? $plan['price'] . ' SAR' : '0 SAR',
                    'start_date' => now()->toDateString(),
                    'renewal_date' => $plan['name'] === 'Free' ? null : $trialEndsAt->toDateString(),
                    'trial_ends_at' => $plan['name'] === 'Free' ? null : $trialEndsAt->toDateString(),
                    'limits' => $plan['limits'],
                    'features' => $plan['features'],
                ],
            ],
        );

        Audit::notify('signup', 'متجر جديد بدأ التجربة', $store->name . ' اختار باقة ' . $plan['name'], [
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'severity' => 'info',
            'url' => route('admin.partners.show', ['partner' => $store->partner_id]),
        ]);
    }

    private function onboardingSteps(): array
    {
        return [
            ['key' => 'store-profile', 'title' => 'بيانات المتجر', 'description' => 'اكتملت من نموذج التسجيل.', 'tooltip' => 'اسم المتجر والمالك ورقم التواصل.', 'cta' => 'مراجعة البيانات'],
            ['key' => 'identity', 'title' => 'الهوية', 'description' => 'ارفع الشعار وحدد الألوان.', 'tooltip' => 'هوية واضحة ترفع الثقة والتحويل.', 'cta' => 'إعداد الهوية'],
            ['key' => 'first-products', 'title' => 'أول منتج', 'description' => 'أضف منتجاً حقيقياً مع صورة وسعر.', 'tooltip' => 'ابدأ بمنتج واحد مكتمل بدل رفع كتالوج ناقص.', 'cta' => 'إضافة منتج'],
            ['key' => 'payments-shipping', 'title' => 'الدفع والشحن', 'description' => 'فعّل طريقة دفع وشركة شحن.', 'tooltip' => 'لا تستقبل طلبات قبل اختبار الدفع والشحن.', 'cta' => 'ربط الخدمات'],
            ['key' => 'domain', 'title' => 'الدومين', 'description' => 'اربط دومينك أو استخدم رابط Solve المؤقت.', 'tooltip' => 'الدومين المخصص يزيد ثقة العملاء.', 'cta' => 'ربط الدومين'],
        ];
    }
}
