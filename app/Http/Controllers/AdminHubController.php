<?php

namespace App\Http\Controllers;

use App\Support\AdminSectionStore;
use App\Support\AdminExecutiveExperience;
use App\Support\PartnerDashboardSummary;
use App\Support\PartnerTenantStore;
use App\Support\PlatformAudit;
use App\Support\PlatformReadiness;
use App\Support\SiteContent;
use App\Models\MarketplaceApp;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\StoreOnboardingStep;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdminHubController extends Controller
{
    public function dashboard(Request $request): View
    {
        return view('admin.dashboard', [
            'stats' => $this->adminStats(),
            'executive' => AdminExecutiveExperience::dashboard(),
            'activeRoute' => 'admin.dashboard',
            'pageTitle' => 'Executive Command Center',
            'pageDescription' => 'مركز تشغيل يومي للإدارة العليا مبني على بيانات المنصة الحقيقية.',
        ]);
    }

    public function executiveSearch(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:160']]);

        return response()->json(AdminExecutiveExperience::search((string) $request->query('q', '')));
    }

    public function executiveAlertAction(Request $request, string $alert): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:resolve,ignore,assign'],
            'assignee' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json(AdminExecutiveExperience::updateAlert($alert, $validated['action'], $validated['assignee'] ?? null));
    }

    public function executiveCommand(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:80'],
            'payload' => ['nullable', 'array'],
        ]);

        return response()->json(AdminExecutiveExperience::executeCommand($validated['command'], $validated['payload'] ?? []));
    }

    public function executiveFeed(Request $request): JsonResponse
    {
        return response()->json(['feed' => AdminExecutiveExperience::feed(40)]);
    }

    public function stores(Request $request): View { return $this->renderManagedSection($request, 'stores'); }
    public function orders(Request $request): View { return $this->renderManagedSection($request, 'orders'); }
    public function products(Request $request): View { return $this->renderManagedSection($request, 'products'); }
    public function customers(Request $request): View { return $this->renderManagedSection($request, 'customers'); }
    public function subscriptions(Request $request): View { return $this->renderManagedSection($request, 'subscriptions'); }
    public function payments(Request $request): View { return $this->renderManagedSection($request, 'payments'); }
    public function showPaymentInvoice(string $recordId): View
    {
        $record = collect($this->sectionRecords('payments'))->firstWhere('id', $recordId);
        abort_unless($record, 404);

        return view('admin.payments.invoice', [
            'invoice' => $this->invoicePreviewFromRecord($record),
        ]);
    }
    public function shipping(Request $request): View { return $this->renderManagedSection($request, 'shipping'); }
    public function coupons(Request $request): View { return $this->renderManagedSection($request, 'coupons'); }
    public function analytics(Request $request): View { return $this->renderManagedSection($request, 'analytics'); }
    public function partners(Request $request): View
    {
        $partners = PartnerTenantStore::allPartners();
        $query = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $filtered = array_values(array_filter($partners, function (array $partner) use ($query, $status) {
            $matchesStatus = $status === 'all' || ($partner['status'] ?? '') === $status;
            $haystack = implode(' ', Arr::only($partner, ['name', 'owner', 'email', 'phone', 'plan', 'store_url']));
            $matchesQuery = $query === '' || Str::contains(Str::lower($haystack), Str::lower($query));

            return $matchesStatus && $matchesQuery;
        }));

        return view('admin.partners.index', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.partners',
            'pageTitle' => 'الشركاء والمتاجر',
            'pageDescription' => 'إدارة الشركاء ضمن بنية Multi-Tenant مع عزل بيانات كل متجر وصلاحيات المستخدمين.',
            'partners' => $filtered,
            'allPartners' => $partners,
            'filters' => [
                'q' => $query,
                'status' => $status,
                'statuses' => ['all', 'نشط', 'موقوف', 'تحت المراجعة'],
            ],
        ]);
    }

    public function showPartner(Request $request, string $partner): View
    {
        $partnerRecord = PartnerTenantStore::findPartner($partner);

        abort_unless($partnerRecord, 404);

        return view('admin.partners.show', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.partners',
            'partner' => $partnerRecord,
            'dashboard' => PartnerDashboardSummary::forPartner($partnerRecord, [
                'role' => PartnerTenantStore::ROLE_SUPER_ADMIN,
                'abilities' => ['*'],
            ], $request),
            'pageTitle' => $partnerRecord['name'],
        ]);
    }
    public function support(Request $request): View { return $this->renderManagedSection($request, 'support'); }
    public function apps(Request $request): View { return $this->renderManagedSection($request, 'apps'); }
    public function settings(Request $request): View { return $this->renderManagedSection($request, 'settings'); }
    public function staff(Request $request): View { return $this->renderManagedSection($request, 'staff'); }

    public function notifications(Request $request): View
    {
        $notifications = Schema::hasTable('platform_notifications')
            ? PlatformNotification::query()->latest()->paginate(20)
            : collect();

        return view('admin.platform.notifications', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.notifications',
            'notifications' => $notifications,
        ]);
    }

    public function activity(Request $request): View
    {
        $logs = Schema::hasTable('platform_activity_logs')
            ? PlatformActivityLog::query()->latest()->paginate(25)
            : collect();

        return view('admin.platform.activity', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.activity',
            'logs' => $logs,
        ]);
    }

    public function onboarding(Request $request): View
    {
        $storeId = trim((string) $request->query('store_id', 'store-atlas'));
        $steps = $this->ensureOnboardingSteps($storeId);

        return view('admin.platform.onboarding', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.onboarding',
            'storeId' => $storeId,
            'steps' => $steps,
            'stores' => $this->sectionRecords('stores'),
        ]);
    }

    public function reports(Request $request): View
    {
        return view('admin.platform.reports', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.reports',
            'stores' => $this->sectionRecords('stores'),
            'orders' => $this->sectionRecords('orders'),
            'payments' => $this->sectionRecords('payments'),
            'subscriptions' => $this->sectionRecords('subscriptions'),
            'filters' => $request->only(['store_id', 'status', 'date_from', 'date_to', 'period']),
        ]);
    }

    public function marketplace(Request $request): View
    {
        $apps = $this->ensureMarketplaceApps();

        return view('admin.platform.marketplace', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.marketplace',
            'apps' => $apps,
            'category' => trim((string) $request->query('category', 'all')),
        ]);
    }

    public function productionReadiness(Request $request): View
    {
        return view('admin.platform.production-readiness', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.production-readiness',
            'readiness' => PlatformReadiness::report(),
        ]);
    }

    public function storeAdvancedSettings(Request $request, string $store): View
    {
        $settings = $this->ensureStoreSettings($store);

        return view('admin.platform.store-settings', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.settings',
            'storeId' => $store,
            'settings' => $settings,
        ]);
    }

    public function storeManagedSection(Request $request, string $section): RedirectResponse
    {
        $meta = $this->sectionMeta($section);
        $validated = $this->validateRecordPayload($request, $meta);
        $records = $this->sectionRecords($section);

        $record = $this->buildManagedRecordPayload($request, $meta, $validated, $section);
        $record['id'] = $this->generateRecordId($section, $record);
        $record['updated_at_human'] = 'Just now';

        $records[] = $record;
        AdminSectionStore::put($section, $records);
        $provisioning = $section === 'stores' ? PartnerTenantStore::provisionFromStoreRecord($record) : [];
        PlatformAudit::activity('created', $section, $record['id'], ['record' => $record, 'store_id' => $record['store_id'] ?? null], $request);
        $this->notifyForRecordChange($section, $record, 'created');

        return redirect()
            ->route($meta['route'])
            ->with('status', $this->addMessage($meta['entityLabel']))
            ->with('provisioning', $provisioning);
    }

    public function editManagedSection(Request $request, string $section, string $recordId): RedirectResponse
    {
        $meta = $this->sectionMeta($section);
        $validated = $this->validateRecordPayload($request, $meta);
        $records = $this->sectionRecords($section);

        foreach ($records as &$record) {
            if (($record['id'] ?? null) !== $recordId) {
                continue;
            }

            $record = $this->buildManagedRecordPayload($request, $meta, $validated, $section, $record);
            $record['id'] = $recordId;
            $record['updated_at_human'] = 'Updated now';
            if ($section === 'stores') {
                PartnerTenantStore::provisionFromStoreRecord($record);
            }
            PlatformAudit::activity('updated', $section, $recordId, ['record' => $record, 'store_id' => $record['store_id'] ?? null], $request);
            $this->notifyForRecordChange($section, $record, 'updated');
        }
        unset($record);

        AdminSectionStore::put($section, $records);

        return redirect()->route($meta['route'], $this->persistedFilters($request))->with('status', $this->editMessage($meta['entityLabel']));
    }

    public function updateManagedSection(Request $request, string $section, string $recordId): RedirectResponse
    {
        $meta = $this->sectionMeta($section);
        $records = $this->sectionRecords($section);
        $targetStatus = trim((string) $request->input('status'));
        $action = trim((string) $request->input('action'));
        $message = $this->updateMessage($meta['entityLabel']);

        foreach ($records as &$record) {
            if (($record['id'] ?? null) !== $recordId) {
                continue;
            }

            if ($action !== '') {
                $allowedActions = $this->allowedActionValues($record, $meta);
                if (! in_array($action, $allowedActions, true)) {
                    throw ValidationException::withMessages([
                        'action' => 'Invalid action.',
                    ]);
                }

                [$record, $message] = $this->applyOperationalAction($section, $record, $meta, $action);
                PlatformAudit::activity('action:' . $action, $section, $recordId, ['record' => $record, 'store_id' => $record['store_id'] ?? null], $request);
            } elseif ($targetStatus !== '') {
                if (! $this->statusIsAllowed($targetStatus, $meta)) {
                    throw ValidationException::withMessages([
                        'status' => 'Invalid status.',
                    ]);
                }

                $record[$meta['statusField']] = $targetStatus;
                $record['updated_at_human'] = 'Updated now';
                PlatformAudit::activity('status_changed', $section, $recordId, ['status' => $targetStatus, 'store_id' => $record['store_id'] ?? null], $request);
            }

            break;
        }
        unset($record);

        AdminSectionStore::put($section, $records);

        return redirect()->route($meta['route'], $this->persistedFilters($request))->with('status', $message);
    }

    public function exportManagedSection(Request $request, string $section): Response
    {
        $meta = $this->sectionMeta($section);
        $records = $this->filterRecords($request, $meta, $this->sectionRecords($section));
        $lines = [];
        $lines[] = implode(',', array_map([$this, 'csvEscape'], array_column($meta['fields'], 'label')));

        foreach ($records as $record) {
            $row = [];
            foreach ($meta['fields'] as $field) {
                $row[] = $this->csvEscape((string) ($record[$field['name']] ?? ''));
            }
            $lines[] = implode(',', $row);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=' . $section . '-' . now()->format('Ymd-His') . '.csv',
        ]);
    }

    public function siteContent(Request $request): View
    {
        $content = SiteContent::get();
        $content['showcaseStores'] = $this->normalizeShowcaseStores($content['showcaseStores'] ?? []);

        return view('admin.site-content', [
            'stats' => $this->adminStats(),
            'activeRoute' => 'admin.site-content',
            'pageTitle' => 'Site Content',
            'pageDescription' => 'Manage homepage content and media.',
            'content' => $content,
            'toneOptions' => ['bg-sky-500', 'bg-amber-300 text-slate-900', 'bg-violet-500', 'bg-rose-400', 'bg-emerald-500', 'bg-brand-600'],
        ]);
    }

    public function updateSiteContent(Request $request): RedirectResponse
    {
        $request->validate([
            'hero_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'feature_image_files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'app_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'showcase_logo_files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $payload = array_replace_recursive(
            SiteContent::get(),
            $request->except(['_token', 'hero_image_file', 'feature_image_files', 'app_image_file', 'showcase_logo_files']),
        );

        $payload['footer']['about_links'] = $this->normalizeMultilineList($request->input('footer.about_links', []));
        $payload['footer']['links'] = $this->normalizeMultilineList($request->input('footer.links', []));

        foreach (($payload['catalogSections'] ?? []) as $sectionIndex => $section) {
            foreach (($section['items'] ?? []) as $itemIndex => $item) {
                $payload['catalogSections'][$sectionIndex]['items'][$itemIndex]['features'] = $this->normalizeMultilineList(
                    $request->input("catalogSections.$sectionIndex.items.$itemIndex.features", []),
                );
            }
        }

        $payload['showcaseStores'] = $this->normalizeShowcaseStores($payload['showcaseStores'] ?? []);

        foreach ($request->file('showcase_logo_files', []) as $index => $file) {
            if ($file) {
                $payload['showcaseStores'][$index]['image'] = SiteContent::uploadImage($file, 'showcase-logo');
            }
        }

        SiteContent::put($payload);

        return redirect()->route('admin.site-content')->with('status', 'Saved successfully.');
    }

    private function renderManagedSection(Request $request, string $section): View
    {
        $meta = $this->sectionMeta($section);
        $records = $this->sectionRecords($section);
        $filtered = $this->filterRecords($request, $meta, $records);
        $editId = trim((string) $request->query('edit', ''));
        $editingRecord = $editId !== '' ? collect($records)->firstWhere('id', $editId) : null;

        return view('admin.section', [
            'stats' => $this->adminStats(),
            'activeRoute' => $meta['route'],
            'sectionKey' => $section,
            'pageTitle' => $meta['title'],
            'pageDescription' => $meta['description'],
            'summaryCards' => $this->summaryCards($records, $meta),
            'filters' => [
                'search' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', $this->all())) ?: $this->all(),
                'statusOptions' => $meta['statusOptions'],
                'placeholder' => $meta['searchPlaceholder'],
            ],
            'table' => $this->tableConfig($meta, $filtered),
            'alerts' => $this->sectionAlerts($records, $meta),
            'pricingPlans' => $this->pricingPlans($meta),
            'financeDesk' => $this->financeDesk($records, $meta),
            'aiInsights' => $this->aiInsights($records, $meta),
            'invoicePreview' => $this->invoicePreview($records, $meta),
            'secondaryPanels' => $this->secondaryPanels($records, $meta),
            'form' => [
                'fields' => $meta['fields'],
                'groups' => $meta['groups'] ?? [],
                'createAction' => route('admin.sections.store', ['section' => $section]),
                'editAction' => $editingRecord ? route('admin.sections.edit', ['section' => $section, 'recordId' => $editingRecord['id']]) : null,
                'createOpen' => $request->boolean('create'),
                'editingRecord' => $editingRecord,
                'cancelUrl' => route($meta['route'], $this->persistedFilters($request)),
                'exportUrl' => route('admin.sections.export', ['section' => $section] + $this->persistedFilters($request)),
            ],
        ]);
    }
    private function adminStats(): array
    {
        $stores = $this->sectionRecords('stores');
        $orders = $this->sectionRecords('orders');
        $subscriptions = $this->sectionRecords('subscriptions');
        $payments = $this->sectionRecords('payments');
        $support = $this->sectionRecords('support');
        $salesTotal = array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['total'] ?? '0'), $orders));

        return [
            ['label' => 'إجمالي المتاجر', 'value' => (string) count($stores), 'change' => 'كل الحسابات', 'tone' => 'primary'],
            ['label' => 'المتاجر النشطة', 'value' => (string) count(array_filter($stores, fn (array $store) => ($store['status'] ?? '') === $this->active())), 'change' => 'جاهزة للبيع', 'tone' => 'success'],
            ['label' => 'طلبات اليوم', 'value' => (string) count($orders), 'change' => 'آخر 24 ساعة', 'tone' => 'info'],
            ['label' => 'إجمالي المبيعات', 'value' => number_format($salesTotal) . ' SAR', 'change' => 'طلبات اليوم', 'tone' => 'success'],
            ['label' => 'اشتراكات نشطة', 'value' => (string) count(array_filter($subscriptions, fn (array $sub) => ($sub['status'] ?? '') === $this->active())), 'change' => 'مدفوعة/فعالة', 'tone' => 'primary'],
            ['label' => 'مدفوعات معلقة', 'value' => (string) count(array_filter($payments, fn (array $payment) => ($payment['invoice_status'] ?? '') !== $this->invoicePaid())), 'change' => 'تحتاج متابعة', 'tone' => 'warning'],
            ['label' => 'تذاكر مفتوحة', 'value' => (string) count(array_filter($support, fn (array $ticket) => ($ticket['status'] ?? '') === $this->openLabel())), 'change' => 'SLA نشط', 'tone' => 'danger'],
        ];
    }

    private function summaryCards(array $records, array $meta): array
    {
        $activeCount = count(array_filter($records, fn (array $record) => ($record[$meta['statusField']] ?? null) === ($meta['statusActiveValue'] ?? '')));

        if (($meta['route'] ?? '') === 'admin.payments') {
            $totalRevenue = array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['gross_revenue'] ?? '0'), $records));
            $settlementsPending = array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['settlements_pending'] ?? '0'), $records));
            $invoicesOpen = count(array_filter($records, fn (array $record) => ($record['invoice_status'] ?? '') !== "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}"));

            return [
                ['label' => "\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A} \u{0627}\u{0644}\u{062F}\u{062E}\u{0644}", 'value' => number_format($totalRevenue) . ' SAR', 'change' => "\u{0627}\u{0644}\u{0634}\u{0647}\u{0631} \u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{064A}"],
                ['label' => "\u{062A}\u{0633}\u{0648}\u{064A}\u{0627}\u{062A} \u{0642}\u{064A}\u{062F} \u{0627}\u{0644}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631}", 'value' => number_format($settlementsPending) . ' SAR', 'change' => "\u{064A}\u{062C}\u{0631}\u{064A} \u{0645}\u{062A}\u{0627}\u{0628}\u{0639}\u{062A}\u{0647}\u{0627}"],
                ['label' => "\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631} \u{0645}\u{0641}\u{062A}\u{0648}\u{062D}\u{0629}", 'value' => (string) $invoicesOpen, 'change' => "\u{062A}\u{062D}\u{062A} \u{0627}\u{0644}\u{062A}\u{062D}\u{0635}\u{064A}\u{0644}"],
            ];
        }

        if (($meta['route'] ?? '') === 'admin.subscriptions') {
            $dueSoon = count(array_filter($records, function (array $record): bool {
                $renewalDate = strtotime((string) ($record['renewal_date'] ?? ''));
                if ($renewalDate === false) {
                    return false;
                }

                $days = (int) floor(($renewalDate - time()) / 86400);
                return $days >= 0 && $days <= 30;
            }));

            return [
                ['label' => "\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}\u{0627}\u{062A}", 'value' => (string) count($records), 'change' => "\u{0627}\u{0644}\u{0645}\u{062D}\u{0641}\u{0638}\u{0629} \u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{064A}\u{0629}"],
                ['label' => "\u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}\u{0627}\u{062A} \u{0627}\u{0644}\u{0646}\u{0634}\u{0637}\u{0629}", 'value' => (string) $activeCount, 'change' => "\u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{0629} \u{0627}\u{0644}\u{062C}\u{0627}\u{0631}\u{064A}\u{0629}"],
                ['label' => "\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}\u{0627}\u{062A} \u{0642}\u{0631}\u{064A}\u{0628}\u{0629}", 'value' => (string) $dueSoon, 'change' => "\u{062E}\u{0644}\u{0627}\u{0644} 30 \u{064A}\u{0648}\u{0645}"],
            ];
        }

        return [
            ['label' => 'Total Records', 'value' => (string) count($records), 'change' => 'Updated'],
            ['label' => 'Active', 'value' => (string) $activeCount, 'change' => 'Current status'],
            ['label' => 'Last Update', 'value' => $records !== [] ? ($records[array_key_last($records)]['updated_at_human'] ?? 'N/A') : 'N/A', 'change' => 'Section feed'],
        ];
    }

    private function sectionAlerts(array $records, array $meta): array
    {
        if (($meta['route'] ?? '') === 'admin.payments') {
            $failed = array_values(array_filter($records, fn (array $record) => (float) filter_var((string) ($record['failed_rate'] ?? '0'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) > 2.5));
            $openInvoices = array_values(array_filter($records, fn (array $record) => ($record['invoice_status'] ?? '') !== "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}"));
            $highRisk = array_values(array_filter($records, fn (array $record) => (int) filter_var((string) ($record['risk_score'] ?? '0'), FILTER_SANITIZE_NUMBER_INT) >= 70));

            $alerts = [];
            if ($failed !== []) {
                $alerts[] = [
                    'tone' => 'danger',
                    'title' => "\u{0645}\u{0639}\u{062F}\u{0644} \u{0641}\u{0634}\u{0644} \u{0645}\u{0631}\u{062A}\u{0641}\u{0639}",
                    'description' => count($failed) . ' ' . "\u{0628}\u{0648}\u{0627}\u{0628}\u{0629} \u{062A}\u{062D}\u{062A}\u{0627}\u{062C} \u{062A}\u{062F}\u{062E}\u{0644} \u{062A}\u{0634}\u{063A}\u{064A}\u{0644}\u{064A}.",
                    'items' => array_map(fn (array $record) => ($record['gateway'] ?? 'Gateway') . ' - ' . ($record['failed_rate'] ?? ''), array_slice($failed, 0, 2)),
                ];
            }
            if ($openInvoices !== []) {
                $alerts[] = [
                    'tone' => 'warning',
                    'title' => "\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631} \u{062A}\u{062D}\u{062A} \u{0627}\u{0644}\u{062A}\u{062D}\u{0635}\u{064A}\u{0644}",
                    'description' => count($openInvoices) . ' ' . "\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629} \u{062A}\u{062D}\u{062A}\u{0627}\u{062C} \u{0645}\u{062A}\u{0627}\u{0628}\u{0639}\u{0629} \u{0645}\u{0627}\u{0644}\u{064A}\u{0629}.",
                    'items' => array_map(fn (array $record) => ($record['invoice_number'] ?? 'INV') . ' - ' . ($record['invoice_status'] ?? ''), array_slice($openInvoices, 0, 2)),
                ];
            }
            if ($highRisk !== []) {
                $alerts[] = [
                    'tone' => 'info',
                    'title' => "\u{062A}\u{0646}\u{0628}\u{064A}\u{0647} \u{0630}\u{0643}\u{064A} \u{0644}\u{0644}\u{0645}\u{062E}\u{0627}\u{0637}\u{0631}",
                    'description' => count($highRisk) . ' ' . "\u{0628}\u{0648}\u{0627}\u{0628}\u{0629} \u{062A}\u{062D}\u{0645}\u{0644} \u{062F}\u{0631}\u{062C}\u{0629} \u{0645}\u{062E}\u{0627}\u{0637}\u{0631} \u{0645}\u{0631}\u{062A}\u{0641}\u{0639}\u{0629}.",
                    'items' => array_map(fn (array $record) => ($record['gateway'] ?? 'Gateway') . ' - ' . ($record['risk_score'] ?? ''), array_slice($highRisk, 0, 2)),
                ];
            }

            return $alerts;
        }

        if (($meta['route'] ?? '') !== 'admin.subscriptions') {
            return [];
        }

        $overdueInvoices = array_values(array_filter($records, fn (array $record) => ($record['invoice_status'] ?? '') === "\u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}"));
        $renewalsSoon = array_values(array_filter($records, function (array $record): bool {
            $renewalDate = strtotime((string) ($record['renewal_date'] ?? ''));
            if ($renewalDate === false) {
                return false;
            }

            $days = (int) floor(($renewalDate - time()) / 86400);
            return $days >= 0 && $days <= 14;
        }));
        $lowHealth = array_values(array_filter($records, function (array $record): bool {
            $score = (int) filter_var((string) ($record['health_score'] ?? '0'), FILTER_SANITIZE_NUMBER_INT);
            return $score > 0 && $score < 60;
        }));

        $alerts = [];

        if ($overdueInvoices !== []) {
            $alerts[] = [
                'tone' => 'danger',
                'title' => "\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631} \u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}",
                'description' => count($overdueInvoices) . ' ' . "\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643} \u{064A}\u{062D}\u{062A}\u{0627}\u{062C} \u{0625}\u{0644}\u{0649} \u{0645}\u{062A}\u{0627}\u{0628}\u{0639}\u{0629} \u{0627}\u{0644}\u{062A}\u{062D}\u{0635}\u{064A}\u{0644}.",
                'items' => array_map(fn (array $record) => ($record['store'] ?? 'Store') . ' - ' . ($record['amount'] ?? ''), array_slice($overdueInvoices, 0, 2)),
            ];
        }

        if ($renewalsSoon !== []) {
            $alerts[] = [
                'tone' => 'warning',
                'title' => "\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}\u{0627}\u{062A} \u{0642}\u{0631}\u{064A}\u{0628}\u{0629}",
                'description' => count($renewalsSoon) . ' ' . "\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643} \u{0633}\u{064A}\u{062D}\u{0644} \u{0645}\u{0648}\u{0639}\u{062F} \u{062A}\u{062C}\u{062F}\u{064A}\u{062F}\u{0647} \u{062E}\u{0644}\u{0627}\u{0644} \u{0623}\u{0633}\u{0628}\u{0648}\u{0639}\u{064A}\u{0646}.",
                'items' => array_map(fn (array $record) => ($record['store'] ?? 'Store') . ' - ' . ($record['renewal_date'] ?? ''), array_slice($renewalsSoon, 0, 2)),
            ];
        }

        if ($lowHealth !== []) {
            $alerts[] = [
                'tone' => 'info',
                'title' => "\u{0635}\u{062D}\u{0629} \u{0627}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628} \u{062A}\u{062D}\u{062A} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629}",
                'description' => count($lowHealth) . ' ' . "\u{062D}\u{0633}\u{0627}\u{0628} \u{064A}\u{062D}\u{062A}\u{0627}\u{062C} \u{062E}\u{0637}\u{0629} \u{0627}\u{062D}\u{062A}\u{0641}\u{0627}\u{0638} \u{0623}\u{0641}\u{0636}\u{0644}.",
                'items' => array_map(fn (array $record) => ($record['store'] ?? 'Store') . ' - ' . ($record['health_score'] ?? ''), array_slice($lowHealth, 0, 2)),
            ];
        }

        return $alerts;
    }

    private function pricingPlans(array $meta): array
    {
        if (($meta['route'] ?? '') !== 'admin.subscriptions') {
            return [];
        }

        return [
            [
                'name' => 'Starter',
                'price' => '890 SAR',
                'cycle' => "\u{0634}\u{0647}\u{0631}\u{064A}",
                'featured' => false,
                'audience' => "\u{0644}\u{0644}\u{0645}\u{062A}\u{0627}\u{062C}\u{0631} \u{0627}\u{0644}\u{0646}\u{0627}\u{0634}\u{0626}\u{0629}",
                'features' => [
                    "\u{0645}\u{062A}\u{062C}\u{0631} \u{0648}\u{0627}\u{062D}\u{062F}",
                    "900 \u{0637}\u{0644}\u{0628} \u{0634}\u{0647}\u{0631}\u{064A}\u{0627}",
                    "3 \u{0645}\u{0642}\u{0627}\u{0639}\u{062F} \u{0641}\u{0631}\u{064A}\u{0642}",
                ],
            ],
            [
                'name' => 'Growth',
                'price' => '2,400 SAR',
                'cycle' => "\u{0634}\u{0647}\u{0631}\u{064A}",
                'featured' => true,
                'audience' => "\u{0644}\u{0644}\u{0645}\u{062A}\u{0627}\u{062C}\u{0631} \u{0627}\u{0644}\u{062A}\u{064A} \u{0641}\u{064A} \u{0645}\u{0631}\u{062D}\u{0644}\u{0629} \u{0627}\u{0644}\u{062A}\u{0648}\u{0633}\u{0639}",
                'features' => [
                    "2 \u{0641}\u{0631}\u{0639}",
                    "3,000 \u{0637}\u{0644}\u{0628} \u{0634}\u{0647}\u{0631}\u{064A}\u{0627}",
                    "Priority SLA",
                ],
            ],
            [
                'name' => 'Enterprise',
                'price' => '14,400 SAR',
                'cycle' => "\u{0633}\u{0646}\u{0648}\u{064A}",
                'featured' => false,
                'audience' => "\u{0644}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628}\u{0627}\u{062A} \u{0627}\u{0644}\u{062A}\u{0634}\u{063A}\u{064A}\u{0644}\u{064A}\u{0629} \u{0627}\u{0644}\u{0643}\u{0628}\u{064A}\u{0631}\u{0629}",
                'features' => [
                    "12,000 \u{0637}\u{0644}\u{0628} \u{0634}\u{0647}\u{0631}\u{064A}\u{0627}",
                    "Dedicated SLA",
                    "\u{062A}\u{0643}\u{0627}\u{0645}\u{0644} \u{062A}\u{0634}\u{063A}\u{064A}\u{0644}\u{064A} \u{0645}\u{062A}\u{0642}\u{062F}\u{0645}",
                ],
            ],
        ];
    }

    private function financeDesk(array $records, array $meta): array
    {
        if (($meta['route'] ?? '') !== 'admin.payments') {
            return [];
        }

        return [
            'headline' => [
                ['label' => "\u{0627}\u{0644}\u{062F}\u{062E}\u{0644} \u{0627}\u{0644}\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A}", 'value' => number_format(array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['gross_revenue'] ?? '0'), $records))) . ' SAR'],
                ['label' => "\u{0627}\u{0644}\u{0635}\u{0627}\u{0641}\u{064A}", 'value' => number_format(array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['net_revenue'] ?? '0'), $records))) . ' SAR'],
                ['label' => "\u{0627}\u{0644}\u{0636}\u{0631}\u{0627}\u{0626}\u{0628}", 'value' => number_format(array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['tax_collected'] ?? '0'), $records))) . ' SAR'],
                ['label' => "\u{0627}\u{0644}\u{0627}\u{0633}\u{062A}\u{0631}\u{062C}\u{0627}\u{0639}\u{0627}\u{062A}", 'value' => array_sum(array_map(fn (array $record) => (int) filter_var((string) ($record['refunds'] ?? '0'), FILTER_SANITIZE_NUMBER_INT), $records)) . ''],
            ],
            'ledger' => [
                ['label' => "\u{062A}\u{0633}\u{0648}\u{064A}\u{0627}\u{062A} \u{0642}\u{064A}\u{062F} \u{0627}\u{0644}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631}", 'value' => number_format(array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['settlements_pending'] ?? '0'), $records))) . ' SAR'],
                ['label' => "\u{0645}\u{062A}\u{0648}\u{0633}\u{0637} \u{0627}\u{0644}\u{0633}\u{0644}\u{0629}", 'value' => number_format(array_sum(array_map(fn (array $record) => $this->moneyToNumber($record['average_ticket'] ?? '0'), $records)) / max(count($records), 1)) . ' SAR'],
                ['label' => "\u{0625}\u{062C}\u{0645}\u{0627}\u{0644}\u{064A} \u{0627}\u{0644}\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631}", 'value' => (string) count($records)],
            ],
        ];
    }

    private function aiInsights(array $records, array $meta): array
    {
        if (($meta['route'] ?? '') !== 'admin.payments') {
            return [];
        }

        return array_map(function (array $record): array {
            $failedRate = (float) filter_var((string) ($record['failed_rate'] ?? '0'), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $riskScore = (int) filter_var((string) ($record['risk_score'] ?? '0'), FILTER_SANITIZE_NUMBER_INT);
            $invoiceStatus = (string) ($record['invoice_status'] ?? '');

            $recommendation = "\u{0627}\u{0644}\u{0648}\u{0636}\u{0639} \u{0645}\u{0633}\u{062A}\u{0642}\u{0631} \u{0648}\u{064A}\u{0646}\u{0635}\u{062D} \u{0628}\u{0627}\u{0644}\u{0627}\u{0633}\u{062A}\u{0645}\u{0631}\u{0627}\u{0631} \u{0639}\u{0644}\u{0649} \u{0646}\u{0641}\u{0633} \u{0627}\u{0644}\u{0645}\u{0633}\u{062A}\u{0648}\u{0649}.";
            if ($failedRate > 2.5) {
                $recommendation = "\u{0627}\u{0644}\u{0630}\u{0643}\u{0627}\u{0621} \u{064A}\u{0648}\u{0635}\u{064A} \u{0628}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629} \u{0623}\u{0633}\u{0628}\u{0627}\u{0628} \u{0627}\u{0644}\u{0641}\u{0634}\u{0644} \u{0648}\u{0625}\u{0639}\u{0627}\u{062F}\u{0629} \u{062A}\u{0648}\u{0632}\u{064A}\u{0639} \u{0627}\u{0644}\u{0623}\u{062D}\u{0645}\u{0627}\u{0644}.";
            } elseif ($invoiceStatus !== "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}") {
                $recommendation = "\u{062A}\u{0648}\u{0635}\u{064A}\u{0629} \u{0630}\u{0643}\u{064A}\u{0629}: \u{062A}\u{0635}\u{0639}\u{064A}\u{062F} \u{0645}\u{062A}\u{0627}\u{0628}\u{0639}\u{0629} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629} \u{0642}\u{0628}\u{0644} \u{0645}\u{0648}\u{0639}\u{062F} \u{0627}\u{0644}\u{062A}\u{0633}\u{0648}\u{064A}\u{0629}.";
            } elseif ($riskScore >= 70) {
                $recommendation = "\u{062A}\u{0648}\u{0635}\u{064A}\u{0629} \u{0630}\u{0643}\u{064A}\u{0629}: \u{062A}\u{0641}\u{0639}\u{064A}\u{0644} \u{0642}\u{0648}\u{0627}\u{0639}\u{062F} \u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629} \u{0627}\u{0644}\u{0627}\u{062D}\u{062A}\u{064A}\u{0627}\u{0644} \u{0644}\u{0647}\u{0630}\u{0647} \u{0627}\u{0644}\u{0628}\u{0648}\u{0627}\u{0628}\u{0629}.";
            }

            return [
                'title' => ($record['gateway'] ?? 'Gateway') . ' AI',
                'score' => ($record['risk_score'] ?? '0') . '%',
                'description' => $recommendation,
            ];
        }, array_slice($records, 0, 3));
    }

    private function invoicePreview(array $records, array $meta): array
    {
        if (($meta['route'] ?? '') !== 'admin.payments' || $records === []) {
            return [];
        }

        return $this->invoicePreviewFromRecord($records[0]);
    }

    private function invoicePreviewFromRecord(array $record): array
    {
        $barcodeValue = (string) ($record['invoice_number'] ?? 'INV-SOLVE-0001');

        return [
            'id' => $record['id'] ?? null,
            'number' => $barcodeValue,
            'gateway' => $record['gateway'] ?? 'Gateway',
            'customer' => $record['customer_name'] ?? 'Solve Customer',
            'customer_email' => $record['customer_email'] ?? '',
            'amount' => $record['invoice_amount'] ?? '0 SAR',
            'tax' => $record['tax_amount'] ?? '0 SAR',
            'status' => $record['invoice_status'] ?? '',
            'due_date' => $record['due_date'] ?? '',
            'merchant_id' => $record['merchant_id'] ?? '',
            'barcode_svg' => $this->barcodeSvg($barcodeValue),
        ];
    }

    private function secondaryPanels(array $records, ?array $meta = null): array
    {
        if (($meta['route'] ?? '') === 'admin.payments') {
            return [
                [
                    'title' => "\u{0645}\u{0644}\u{0641} \u{0645}\u{0627}\u{0644}\u{064A}",
                    'entries' => collect($records)->map(fn (array $record) => [
                        'title' => ($record['invoice_number'] ?? 'INV') . ' - ' . ($record['gateway'] ?? ''),
                        'meta' => ($record['gross_revenue'] ?? '') . ' | ' . ($record['invoice_status'] ?? ''),
                    ])->take(3)->values()->all(),
                ],
                [
                    'title' => "\u{062C}\u{062F}\u{0648}\u{0644} \u{0627}\u{0644}\u{062F}\u{062E}\u{0644}",
                    'entries' => collect($records)->map(fn (array $record) => [
                        'title' => ($record['gateway'] ?? 'Gateway') . ' - ' . ($record['net_revenue'] ?? ''),
                        'meta' => ($record['settlement_cycle'] ?? '') . ' | ' . ($record['settlements_pending'] ?? ''),
                    ])->take(3)->values()->all(),
                ],
            ];
        }

        if (($meta['route'] ?? '') === 'admin.subscriptions') {
            return [
                [
                    'title' => "\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}\u{0627}\u{062A} \u{0642}\u{0631}\u{064A}\u{0628}\u{0629}",
                    'entries' => collect($records)->sortBy('renewal_date')->take(3)->map(fn (array $record) => [
                        'title' => ($record['store'] ?? 'Store') . ' - ' . ($record['plan'] ?? ''),
                        'meta' => ($record['renewal_date'] ?? 'N/A') . ' | ' . ($record['billing_cycle'] ?? ''),
                    ])->values()->all(),
                ],
                [
                    'title' => "\u{0645}\u{0624}\u{0634}\u{0631}\u{0627}\u{062A} \u{0627}\u{0644}\u{0641}\u{0648}\u{062A}\u{0631}\u{0629}",
                    'entries' => collect(array_slice(array_reverse($records), 0, 3))->map(fn (array $record) => [
                        'title' => ($record['invoice_status'] ?? 'Invoice') . ' - ' . ($record['owner'] ?? ''),
                        'meta' => ($record['payment_method'] ?? '') . ' | ' . ($record['amount'] ?? ''),
                    ])->all(),
                ],
            ];
        }

        return [[
            'title' => 'Latest Updates',
            'entries' => collect(array_slice(array_reverse($records), 0, 3))->map(fn (array $record) => [
                'title' => $record['name'] ?? $record['gateway'] ?? $record['store'] ?? $record['carrier'] ?? $record['module'] ?? 'Record',
                'meta' => $record['updated_at_human'] ?? 'Updated',
            ])->all(),
        ]];
    }

    private function applyOperationalAction(string $section, array $record, array $meta, string $action): array
    {
        $today = now()->format('Y-m-d');

        return match ($section) {
            'stores' => $this->applyStoreAction($record, $meta, $action),
            'subscriptions' => $this->applySubscriptionAction($record, $meta, $action, $today),
            'payments' => $this->applyPaymentAction($record, $meta, $action, $today),
            'shipping' => $this->applyShippingAction($record, $meta, $action),
            'analytics' => $this->applyAnalyticsAction($record, $meta, $action),
            'partners' => $this->applyPartnersAction($record, $meta, $action),
            'support' => $this->applySupportAction($record, $meta, $action),
            'apps' => $this->applyAppsAction($record, $meta, $action),
            'settings' => $this->applySettingsAction($record, $meta, $action, $today),
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyStoreAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'launch_store' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'onboarding_stage' => 'Ready',
                    'notes' => $this->appendRecordNote($record['notes'] ?? '', 'Store moved to launch-ready status.'),
                    'updated_at_human' => 'Launched now',
                ]),
                'Store is ready for launch.',
            ],
            'move_to_review' => [
                array_merge($record, [
                    $meta['statusField'] => $this->review(),
                    'onboarding_stage' => 'Setup',
                    'notes' => $this->appendRecordNote($record['notes'] ?? '', 'Operational review was requested.'),
                    'updated_at_human' => 'Moved to review',
                ]),
                'Store moved to the review queue.',
            ],
            'suspend_store' => [
                array_merge($record, [
                    $meta['statusField'] => $this->suspended(),
                    'notes' => $this->appendRecordNote($record['notes'] ?? '', 'Store was suspended pending action.'),
                    'updated_at_human' => 'Suspended now',
                ]),
                'Store was suspended.',
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applySubscriptionAction(array $record, array $meta, string $action, string $today): array
    {
        return match ($action) {
            'activate_subscription' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'health_score' => $this->maxPercentValue($record['health_score'] ?? '0%', 80),
                    'success_notes' => $this->appendRecordNote($record['success_notes'] ?? '', 'Account activated and handed to customer success.'),
                    'updated_at_human' => 'Activated now',
                ]),
                'Subscription activated.',
            ],
            'collect_payment' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'invoice_status' => $this->invoicePaid(),
                    'last_payment_date' => $today,
                    'billing_notes' => $this->appendRecordNote($record['billing_notes'] ?? '', 'Payment collected and reconciled.'),
                    'updated_at_human' => 'Payment collected',
                ]),
                'Subscription payment collected.',
            ],
            'renew_subscription' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'invoice_status' => $this->invoicePaid(),
                    'last_payment_date' => $today,
                    'renewal_date' => $this->extendDateByCycle((string) ($record['renewal_date'] ?? $today), (string) ($record['billing_cycle'] ?? 'Monthly')),
                    'billing_notes' => $this->appendRecordNote($record['billing_notes'] ?? '', 'Renewal executed from the operations console.'),
                    'updated_at_human' => 'Renewed now',
                ]),
                'Subscription renewed.',
            ],
            'hold_subscription' => [
                array_merge($record, [
                    $meta['statusField'] => $this->suspended(),
                    'billing_notes' => $this->appendRecordNote($record['billing_notes'] ?? '', 'Subscription was placed on hold.'),
                    'updated_at_human' => 'On hold',
                ]),
                'Subscription placed on hold.',
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyPaymentAction(array $record, array $meta, string $action, string $today): array
    {
        return match ($action) {
            'mark_invoice_paid' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'invoice_status' => $this->invoicePaid(),
                    'settlements_pending' => $this->subtractMoney($record['settlements_pending'] ?? '0 SAR', $record['invoice_amount'] ?? '0 SAR'),
                    'ai_summary' => $this->appendRecordNote($record['ai_summary'] ?? '', 'Invoice was marked as paid and posted in the financial desk.'),
                    'updated_at_human' => 'Collected now',
                    'due_date' => $record['due_date'] ?? $today,
                ]),
                'Invoice collected.',
            ],
            'send_invoice_reminder' => [
                array_merge($record, [
                    'invoice_status' => ($record['invoice_status'] ?? '') === $this->invoicePaid() ? $this->invoicePaid() : $this->invoicePending(),
                    'ai_summary' => $this->appendRecordNote($record['ai_summary'] ?? '', 'A collection reminder was sent to the customer finance contact.'),
                    'updated_at_human' => 'Reminder sent',
                ]),
                'Invoice reminder sent.',
            ],
            'settle_gateway' => [
                array_merge($record, [
                    $meta['statusField'] => $this->active(),
                    'settlements_pending' => '0 SAR',
                    'ai_summary' => $this->appendRecordNote($record['ai_summary'] ?? '', 'Pending settlements were cleared and reconciled.'),
                    'updated_at_human' => 'Settled now',
                ]),
                'Settlements reconciled.',
            ],
            'flag_risk' => [
                array_merge($record, [
                    $meta['statusField'] => $this->monitoring(),
                    'risk_score' => (string) max((int) filter_var((string) ($record['risk_score'] ?? '0'), FILTER_SANITIZE_NUMBER_INT), 75),
                    'ai_summary' => $this->appendRecordNote($record['ai_summary'] ?? '', 'Gateway was escalated to risk monitoring for finance review.'),
                    'updated_at_human' => 'Risk flagged',
                ]),
                'Gateway moved to risk monitoring.',
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyShippingAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'dispatch_carrier' => [
                array_merge($record, [
                    $meta['statusField'] => $this->activeLabel(),
                    'service_level' => $this->dispatchedLabel(),
                    'delay' => '0.8%',
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0623}\u{0643}\u{062F} \u{0625}\u{0631}\u{0633}\u{0627}\u{0644} \u{0627}\u{0644}\u{0634}\u{0627}\u{062D}\u{0646}.",
            ],
            'escalate_carrier' => [
                array_merge($record, [
                    $meta['statusField'] => "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}",
                    'service_level' => $this->escalatedLabel(),
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{062D}\u{0648}\u{064A}\u{0644} \u{0627}\u{0644}\u{0634}\u{0627}\u{062D}\u{0646} \u{0625}\u{0644}\u{0649} \u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629} \u{0627}\u{0644}\u{062E}\u{062F}\u{0645}\u{0629}.",
            ],
            'pause_carrier' => [
                array_merge($record, [
                    $meta['statusField'] => $this->pausedLabel(),
                    'service_level' => $this->pausedLabel(),
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{0625}\u{064A}\u{0642}\u{0627}\u{0641} \u{0627}\u{0644}\u{0634}\u{0627}\u{062D}\u{0646}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyAnalyticsAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'publish_report' => [
                array_merge($record, [
                    $meta['statusField'] => $this->publishedLabel(),
                    'priority' => 'High',
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{0646}\u{0634}\u{0631} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{0646}\u{0634}\u{0631} \u{0627}\u{0644}\u{062A}\u{0642}\u{0631}\u{064A}\u{0631} \u{0644}\u{0644}\u{0623}\u{0637}\u{0631}\u{0627}\u{0641} \u{0627}\u{0644}\u{0645}\u{0639}\u{0646}\u{064A}\u{0629}.",
            ],
            'refresh_report' => [
                array_merge($record, [
                    'period' => 'Updated today',
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0628}\u{064A}\u{0627}\u{0646}\u{0627}\u{062A} \u{0627}\u{0644}\u{062A}\u{0642}\u{0631}\u{064A}\u{0631}.",
            ],
            'archive_report' => [
                array_merge($record, [
                    $meta['statusField'] => $this->archivedLabel(),
                    'updated_at_human' => "\u{062A}\u{0645}\u{062A} \u{0627}\u{0644}\u{0623}\u{0631}\u{0634}\u{0641}\u{0629} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645}\u{062A} \u{0623}\u{0631}\u{0634}\u{0641}\u{0629} \u{0627}\u{0644}\u{062A}\u{0642}\u{0631}\u{064A}\u{0631}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyPartnersAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'approve_partner' => [
                array_merge($record, [
                    $meta['statusField'] => $this->activeLabel(),
                    'lead_time' => '2 days',
                    'updated_at_human' => 'Approved now',
                ]),
                "\u{062A}\u{0645} \u{0627}\u{0639}\u{062A}\u{0645}\u{0627}\u{062F} \u{0627}\u{0644}\u{0634}\u{0631}\u{064A}\u{0643}.",
            ],
            'capacity_review' => [
                array_merge($record, [
                    $meta['statusField'] => "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}",
                    'updated_at_human' => 'Capacity under review',
                ]),
                "\u{0628}\u{062F}\u{0623}\u{062A} \u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629} \u{0633}\u{0639}\u{0629} \u{0627}\u{0644}\u{0634}\u{0631}\u{064A}\u{0643}.",
            ],
            'pause_partner' => [
                array_merge($record, [
                    $meta['statusField'] => $this->pausedLabel(),
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{0625}\u{064A}\u{0642}\u{0627}\u{0641} \u{0627}\u{0644}\u{0634}\u{0631}\u{064A}\u{0643}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applySupportAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'assign_ticket' => [
                array_merge($record, [
                    $meta['statusField'] => $this->assignedLabel(),
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{0648}\u{0632}\u{064A}\u{0639} \u{0627}\u{0644}\u{062A}\u{0630}\u{0643}\u{0631}\u{0629}.",
            ],
            'resolve_ticket' => [
                array_merge($record, [
                    $meta['statusField'] => $this->resolvedLabel(),
                    'sla' => 'Met',
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{0625}\u{063A}\u{0644}\u{0627}\u{0642} \u{0627}\u{0644}\u{062A}\u{0630}\u{0643}\u{0631}\u{0629}.",
            ],
            'escalate_ticket' => [
                array_merge($record, [
                    $meta['statusField'] => $this->escalatedLabel(),
                    'priority' => 'Critical',
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{0635}\u{0639}\u{064A}\u{062F} \u{0627}\u{0644}\u{062A}\u{0630}\u{0643}\u{0631}\u{0629}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyAppsAction(array $record, array $meta, string $action): array
    {
        return match ($action) {
            'release_update' => [
                array_merge($record, [
                    $meta['statusField'] => $this->stableLabel(),
                    'release_date' => now()->format('d M Y'),
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{0627}\u{0644}\u{0625}\u{0635}\u{062F}\u{0627}\u{0631} \u{0645}\u{062A}\u{0627}\u{062D} \u{0627}\u{0644}\u{0622}\u{0646}.",
            ],
            'monitor_app' => [
                array_merge($record, [
                    $meta['statusField'] => $this->monitoringLabel(),
                    'updated_at_human' => "\u{062A}\u{062D}\u{062A} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{062D}\u{0648}\u{064A}\u{0644} \u{0627}\u{0644}\u{062A}\u{0637}\u{0628}\u{064A}\u{0642} \u{0625}\u{0644}\u{0649} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629}.",
            ],
            'rollback_app' => [
                array_merge($record, [
                    $meta['statusField'] => "\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}",
                    'updated_at_human' => "\u{0628}\u{062F}\u{0623} \u{0627}\u{0644}\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}",
                ]),
                "\u{0628}\u{062F}\u{0623} \u{0627}\u{0644}\u{062A}\u{0631}\u{0627}\u{062C}\u{0639} \u{0639}\u{0646} \u{0627}\u{0644}\u{0625}\u{0635}\u{062F}\u{0627}\u{0631}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applySettingsAction(array $record, array $meta, string $action, string $today): array
    {
        return match ($action) {
            'enable_module' => [
                array_merge($record, [
                    $meta['statusField'] => $this->enabledLabel(),
                    'last_review' => $today,
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{0641}\u{0639}\u{064A}\u{0644} \u{0627}\u{0644}\u{0648}\u{062D}\u{062F}\u{0629}.",
            ],
            'review_module' => [
                array_merge($record, [
                    $meta['statusField'] => "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}",
                    'last_review' => $today,
                    'updated_at_human' => "\u{062A}\u{0645}\u{062A} \u{062C}\u{062F}\u{0648}\u{0644}\u{0629} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{062D}\u{0648}\u{064A}\u{0644} \u{0627}\u{0644}\u{0648}\u{062D}\u{062F}\u{0629} \u{0625}\u{0644}\u{0649} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}.",
            ],
            'disable_module' => [
                array_merge($record, [
                    $meta['statusField'] => $this->disabledLabel(),
                    'last_review' => $today,
                    'updated_at_human' => "\u{062A}\u{0645} \u{0627}\u{0644}\u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0622}\u{0646}",
                ]),
                "\u{062A}\u{0645} \u{062A}\u{0639}\u{0637}\u{064A}\u{0644} \u{0627}\u{0644}\u{0648}\u{062D}\u{062F}\u{0629}.",
            ],
            default => [$this->applyFallbackStatus($record, $meta, $action), $this->updateMessage($meta['entityLabel'])],
        };
    }

    private function applyFallbackStatus(array $record, array $meta, string $action): array
    {
        if ($action === '' || ! in_array($action, $meta['statusOptions'] ?? [], true)) {
            return $record;
        }

        $record[$meta['statusField']] = $action;
        $record['updated_at_human'] = 'Updated now';

        return $record;
    }

    private function allowedActionValues(array $record, array $meta): array
    {
        $actions = $this->sectionOperationalActions($record, $meta);

        if ($actions === []) {
            return array_values(array_filter(
                $meta['statusOptions'] ?? [],
                fn (string $status): bool => $this->statusIsAllowed($status, $meta),
            ));
        }

        return array_values(array_filter(array_map(
            fn (array $action): string => (string) ($action['value'] ?? ''),
            $actions,
        ), fn (string $value): bool => $value !== ''));
    }

    private function statusIsAllowed(string $status, array $meta): bool
    {
        return $status !== $this->all() && in_array($status, $meta['statusOptions'] ?? [], true);
    }

    private function sectionOperationalActions(array $record, array $meta): array
    {
        return match ($meta['route'] ?? '') {
            'admin.stores' => [
                $this->buildTableAction("\u{0625}\u{0637}\u{0644}\u{0627}\u{0642} \u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631}", 'action', 'launch_store', ($record['status'] ?? '') === $this->active() && ($record['onboarding_stage'] ?? '') === 'Ready', 'primary'),
                $this->buildTableAction("\u{062A}\u{062D}\u{0648}\u{064A}\u{0644} \u{0644}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'action', 'move_to_review', ($record['status'] ?? '') === $this->review(), 'neutral'),
                $this->buildTableAction("\u{062A}\u{0639}\u{0644}\u{064A}\u{0642} \u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631}", 'action', 'suspend_store', ($record['status'] ?? '') === $this->suspended(), 'danger'),
            ],
            'admin.subscriptions' => [
                $this->buildTableAction("\u{062A}\u{0641}\u{0639}\u{064A}\u{0644} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'action', 'activate_subscription', ($record['status'] ?? '') === $this->active(), 'primary'),
                $this->buildTableAction("\u{062A}\u{062D}\u{0635}\u{064A}\u{0644} \u{0627}\u{0644}\u{062F}\u{0641}\u{0639}\u{0629}", 'action', 'collect_payment', ($record['invoice_status'] ?? '') === $this->invoicePaid(), 'success'),
                $this->buildTableAction("\u{062A}\u{062C}\u{062F}\u{064A}\u{062F} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'action', 'renew_subscription', false, 'primary'),
                $this->buildTableAction("\u{0625}\u{064A}\u{0642}\u{0627}\u{0641} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'action', 'hold_subscription', ($record['status'] ?? '') === $this->suspended(), 'danger'),
            ],
            'admin.payments' => [
                $this->buildTableAction("\u{062A}\u{062D}\u{0635}\u{064A}\u{0644} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629}", 'action', 'mark_invoice_paid', ($record['invoice_status'] ?? '') === $this->invoicePaid(), 'success'),
                $this->buildTableAction("\u{0625}\u{0631}\u{0633}\u{0627}\u{0644} \u{062A}\u{0630}\u{0643}\u{064A}\u{0631}", 'action', 'send_invoice_reminder', false, 'neutral'),
                $this->buildTableAction("\u{062A}\u{0633}\u{0648}\u{064A}\u{0629} \u{0627}\u{0644}\u{0628}\u{0648}\u{0627}\u{0628}\u{0629}", 'action', 'settle_gateway', $this->moneyToNumber($record['settlements_pending'] ?? '0 SAR') <= 0, 'primary'),
                $this->buildTableAction("\u{062A}\u{0635}\u{0639}\u{064A}\u{062F} \u{0627}\u{0644}\u{0645}\u{062E}\u{0627}\u{0637}\u{0631}", 'action', 'flag_risk', ($record['status'] ?? '') === $this->monitoring(), 'danger'),
                $this->buildTableAction("\u{0637}\u{0628}\u{0627}\u{0639}\u{0629} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629}", 'link', route('admin.payments.invoice', ['recordId' => $record['id']]), false, 'neutral'),
            ],
            'admin.shipping' => [
                $this->buildTableAction("\u{0625}\u{0631}\u{0633}\u{0627}\u{0644}", 'action', 'dispatch_carrier', ($record['service_level'] ?? '') === $this->dispatchedLabel(), 'success'),
                $this->buildTableAction("\u{062A}\u{0635}\u{0639}\u{064A}\u{062F}", 'action', 'escalate_carrier', ($record['status'] ?? '') === "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'danger'),
                $this->buildTableAction("\u{0625}\u{064A}\u{0642}\u{0627}\u{0641}", 'action', 'pause_carrier', ($record['status'] ?? '') === $this->pausedLabel(), 'neutral'),
            ],
            'admin.analytics' => [
                $this->buildTableAction("\u{0646}\u{0634}\u{0631}", 'action', 'publish_report', ($record['status'] ?? '') === $this->publishedLabel(), 'success'),
                $this->buildTableAction("\u{062A}\u{062D}\u{062F}\u{064A}\u{062B}", 'action', 'refresh_report', false, 'primary'),
                $this->buildTableAction("\u{0623}\u{0631}\u{0634}\u{0641}\u{0629}", 'action', 'archive_report', ($record['status'] ?? '') === $this->archivedLabel(), 'neutral'),
            ],
            'admin.partners' => [
                $this->buildTableAction("\u{0627}\u{0639}\u{062A}\u{0645}\u{0627}\u{062F}", 'action', 'approve_partner', ($record['status'] ?? '') === $this->activeLabel(), 'success'),
                $this->buildTableAction("\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629} \u{0627}\u{0644}\u{0633}\u{0639}\u{0629}", 'action', 'capacity_review', ($record['status'] ?? '') === "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'primary'),
                $this->buildTableAction("\u{0625}\u{064A}\u{0642}\u{0627}\u{0641}", 'action', 'pause_partner', ($record['status'] ?? '') === $this->pausedLabel(), 'neutral'),
            ],
            'admin.support' => [
                $this->buildTableAction("\u{062A}\u{0648}\u{0632}\u{064A}\u{0639}", 'action', 'assign_ticket', ($record['status'] ?? '') === $this->assignedLabel(), 'primary'),
                $this->buildTableAction("\u{0625}\u{063A}\u{0644}\u{0627}\u{0642}", 'action', 'resolve_ticket', ($record['status'] ?? '') === $this->resolvedLabel(), 'success'),
                $this->buildTableAction("\u{062A}\u{0635}\u{0639}\u{064A}\u{062F}", 'action', 'escalate_ticket', ($record['status'] ?? '') === $this->escalatedLabel(), 'danger'),
            ],
            'admin.apps' => [
                $this->buildTableAction("\u{0625}\u{0637}\u{0644}\u{0627}\u{0642}", 'action', 'release_update', ($record['status'] ?? '') === $this->stableLabel(), 'success'),
                $this->buildTableAction("\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629}", 'action', 'monitor_app', ($record['status'] ?? '') === $this->monitoringLabel(), 'primary'),
                $this->buildTableAction("\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}", 'action', 'rollback_app', ($record['status'] ?? '') === "\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}", 'danger'),
            ],
            'admin.settings' => [
                $this->buildTableAction("\u{062A}\u{0641}\u{0639}\u{064A}\u{0644}", 'action', 'enable_module', ($record['status'] ?? '') === $this->enabledLabel(), 'success'),
                $this->buildTableAction("\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'action', 'review_module', ($record['status'] ?? '') === "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'primary'),
                $this->buildTableAction("\u{062A}\u{0639}\u{0637}\u{064A}\u{0644}", 'action', 'disable_module', ($record['status'] ?? '') === $this->disabledLabel(), 'danger'),
            ],
            default => [],
        };
    }

    private function buildTableAction(string $label, string $kind, string $value, bool $active, string $tone): array
    {
        return [
            'label' => $label,
            'kind' => $kind,
            'value' => $value,
            'active' => $active,
            'classes' => $this->tableActionClasses($tone, $active),
        ];
    }

    private function tableActionClasses(string $tone, bool $active): string
    {
        if ($active) {
            return 'bg-brand-600 text-white';
        }

        return match ($tone) {
            'danger' => 'border border-rose-200 text-rose-700 bg-rose-50',
            'success' => 'border border-emerald-200 text-emerald-700 bg-emerald-50',
            'primary' => 'border border-brand-200 text-brand-700 bg-brand-50',
            default => 'border border-slate-200 text-slate-600 bg-white',
        };
    }

    private function appendRecordNote(string $existing, string $note): string
    {
        $existing = trim($existing);

        return $existing === '' ? $note : $existing . ' ' . $note;
    }

    private function extendDateByCycle(string $date, string $billingCycle): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $modifier = match ($billingCycle) {
            'Annual' => '+1 year',
            'Quarterly' => '+3 months',
            default => '+1 month',
        };

        return date('Y-m-d', strtotime($modifier, $timestamp));
    }

    private function maxPercentValue(string $value, int $minimum): string
    {
        $current = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);

        return max($current, $minimum) . '%';
    }

    private function subtractMoney(mixed $amount, mixed $deduction): string
    {
        return $this->formatMoney(max($this->moneyToNumber($amount) - $this->moneyToNumber($deduction), 0));
    }

    private function formatMoney(float $amount): string
    {
        $decimals = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimals) . ' SAR';
    }

    private function validateRecordPayload(Request $request, array $meta): array
    {
        $rules = [];
        foreach ($meta['fields'] as $field) {
            $type = $field['type'] ?? 'text';
            $required = $field['required'] ?? true;
            $base = [$required ? 'required' : 'nullable'];
            $rules[$field['name']] = match ($type) {
                'email' => array_merge($base, ['string', 'email', 'max:255']),
                'date' => array_merge($base, ['date']),
                'image' => array_merge($base, ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']),
                'file' => array_merge($base, ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120']),
                'textarea' => array_merge($base, ['string', 'max:3000']),
                default => array_merge($base, ['string', 'max:255']),
            };
        }
        return $request->validate($rules);
    }

    private function buildManagedRecordPayload(Request $request, array $meta, array $validated, string $section, array $existing = []): array
    {
        $record = $existing;
        foreach ($meta['fields'] as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            if (in_array($type, ['file', 'image'], true)) {
                $record[$name] = $request->hasFile($name)
                    ? (string) $request->file($name)->store("admin-sections/{$section}/{$name}", 'public')
                    : (string) ($existing[$name] ?? '');
                continue;
            }
            $value = $validated[$name] ?? '';
            $record[$name] = is_string($value) ? trim($value) : (string) $value;
        }
        return $record;
    }

    private function persistedFilters(Request $request): array
    {
        $status = trim((string) $request->input('current_status', $request->query('status', $this->all())));
        return array_filter([
            'q' => trim((string) $request->input('q', $request->query('q', ''))),
            'status' => $status !== $this->all() ? $status : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function normalizeMultilineList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $value), fn ($item) => $item !== ''));
        }
        if ($value === null) {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', trim((string) $value)) ?: [];
        return array_values(array_filter(array_map('trim', $lines), fn ($item) => $item !== ''));
    }

    private function normalizeShowcaseStores(array $stores): array
    {
        $defaults = [
            ['name' => 'Atlas Fashion', 'badge' => 'AF', 'tone' => 'bg-sky-500', 'category' => 'Fashion', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Solar Free', 'badge' => 'SF', 'tone' => 'bg-amber-300 text-slate-900', 'category' => 'Retail', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Shahd Gift', 'badge' => 'SH', 'tone' => 'bg-violet-500', 'category' => 'Gifts', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Tala Schools', 'badge' => 'TS', 'tone' => 'bg-rose-400', 'category' => 'Education', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Rowaa Beauty', 'badge' => 'RB', 'tone' => 'bg-rose-400', 'category' => 'Beauty', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Abaad Home', 'badge' => 'AH', 'tone' => 'bg-emerald-500', 'category' => 'Home', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Lenda Sweets', 'badge' => 'LS', 'tone' => 'bg-amber-300 text-slate-900', 'category' => 'Food', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Zari Abaya', 'badge' => 'ZA', 'tone' => 'bg-brand-600', 'category' => 'Fashion', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'SWAV Store', 'badge' => 'SW', 'tone' => 'bg-sky-500', 'category' => 'Lifestyle', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Book Club', 'badge' => 'BC', 'tone' => 'bg-violet-500', 'category' => 'Books', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Misk Gifts', 'badge' => 'MG', 'tone' => 'bg-rose-400', 'category' => 'Gifts', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
            ['name' => 'Noura Studio', 'badge' => 'NS', 'tone' => 'bg-emerald-500', 'category' => 'Creative', 'metric' => 'Partner', 'image' => '', 'url' => '#'],
        ];

        $normalized = [];
        $count = max(count($stores), count($defaults));

        for ($index = 0; $index < $count; $index++) {
            $store = array_replace($defaults[$index] ?? $defaults[$index % count($defaults)], is_array($stores[$index] ?? null) ? $stores[$index] : []);

            if (trim((string) ($store['name'] ?? '')) === '') {
                continue;
            }

            $normalized[] = [
                'name' => trim((string) ($store['name'] ?? '')),
                'badge' => trim((string) ($store['badge'] ?? '')),
                'tone' => trim((string) ($store['tone'] ?? 'bg-brand-600')),
                'category' => trim((string) ($store['category'] ?? 'Partner')),
                'metric' => trim((string) ($store['metric'] ?? 'Partner')),
                'image' => trim((string) ($store['image'] ?? '')),
                'url' => trim((string) ($store['url'] ?? '#')),
            ];
        }

        return $normalized;
    }

    private function generateRecordId(string $section, array $record): string
    {
        return $section . '-' . Str::slug((string) ($record['name'] ?? $record['gateway'] ?? $record['store'] ?? Arr::first($record))) . '-' . substr((string) now()->timestamp, -4);
    }

    private function csvEscape(string $value): string
    {
        if ($value !== '' && preg_match('/^[=\-+@\t\r]/', $value) === 1) {
            $value = "'" . $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }

    private function moneyToNumber(mixed $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', (string) $value);
    }

    private function barcodeSvg(string $value): string
    {
        $chars = str_split(preg_replace('/[^A-Za-z0-9]/', '', $value) ?: 'SOLVE001');
        $x = 8;
        $bars = '';

        foreach ($chars as $char) {
            $code = ord($char);
            for ($bit = 0; $bit < 7; $bit++) {
                $width = (($code >> $bit) & 1) === 1 ? 3 : 1;
                $bars .= '<rect x="' . $x . '" y="10" width="' . $width . '" height="72" fill="#0f172a"/>';
                $x += $width + 1;
            }
            $x += 2;
        }

        $width = max($x + 8, 180);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' 96" width="100%" height="96" role="img" aria-label="barcode">'
            . '<rect width="100%" height="100%" rx="12" fill="#ffffff"/>'
            . $bars
            . '<text x="50%" y="92" text-anchor="middle" font-size="12" fill="#334155" font-family="Arial, sans-serif">' . e($value) . '</text>'
            . '</svg>';
    }

    private function filterRecords(Request $request, array $meta, array $records): array
    {
        $search = mb_strtolower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', $this->all())) ?: $this->all();

        return array_values(array_filter($records, function (array $record) use ($search, $status, $meta): bool {
            if ($status !== $this->all() && ($record[$meta['statusField']] ?? null) !== $status) {
                return false;
            }
            if ($search === '') {
                return true;
            }
            foreach ($meta['searchFields'] as $field) {
                if (str_contains(mb_strtolower((string) ($record[$field] ?? '')), $search)) {
                    return true;
                }
            }
            return false;
        }));
    }

    private function notifyForRecordChange(string $section, array $record, string $action): void
    {
        $storeId = $record['store_id'] ?? null;

        match ($section) {
            'orders' => PlatformAudit::notify('new_order', 'طلب جديد: ' . ($record['order_number'] ?? $record['id'] ?? ''), 'تم تسجيل طلب داخل النظام.', ['store_id' => $storeId, 'severity' => 'info', 'url' => route('admin.orders')]),
            'payments' => PlatformAudit::notify('new_payment', 'تحديث دفع: ' . ($record['gateway'] ?? $record['id'] ?? ''), 'تم تحديث سجل مالي أو فاتورة.', ['store_id' => $storeId, 'severity' => 'success', 'url' => route('admin.payments')]),
            'subscriptions' => PlatformAudit::notify('subscription_renewal', 'اشتراك يحتاج متابعة', 'راجع حالة التجديد والفوترة.', ['store_id' => $storeId, 'severity' => 'warning', 'url' => route('admin.subscriptions')]),
            'support' => PlatformAudit::notify('support_ticket', 'تذكرة دعم جديدة أو محدثة', $record['ticket'] ?? null, ['store_id' => $storeId, 'severity' => 'warning', 'url' => route('admin.support')]),
            'products' => (($record['status'] ?? '') === 'مخزون منخفض')
                ? PlatformAudit::notify('low_stock', 'منتج منخفض المخزون', $record['product'] ?? null, ['store_id' => $storeId, 'severity' => 'danger', 'url' => route('admin.products')])
                : null,
            default => null,
        };
    }

    private function ensureOnboardingSteps(string $storeId)
    {
        if (! Schema::hasTable('store_onboarding_steps')) {
            return collect($this->defaultOnboardingSteps($storeId));
        }

        foreach ($this->defaultOnboardingSteps($storeId) as $step) {
            StoreOnboardingStep::query()->firstOrCreate(
                ['store_id' => $storeId, 'step_key' => $step['step_key']],
                Arr::except($step, ['store_id', 'step_key']),
            );
        }

        return StoreOnboardingStep::query()->where('store_id', $storeId)->orderBy('id')->get();
    }

    private function defaultOnboardingSteps(string $storeId): array
    {
        return [
            ['store_id' => $storeId, 'step_key' => 'store_identity', 'title' => 'بيانات المتجر', 'status' => 'completed', 'payload' => ['fields' => ['name', 'owner', 'domain']]],
            ['store_id' => $storeId, 'step_key' => 'payments', 'title' => 'إعدادات الدفع', 'status' => 'in_progress', 'payload' => ['providers' => ['Mada', 'Visa', 'Apple Pay']]],
            ['store_id' => $storeId, 'step_key' => 'shipping', 'title' => 'إعدادات الشحن', 'status' => 'pending', 'payload' => ['providers' => ['Aramex', 'SMSA']]],
            ['store_id' => $storeId, 'step_key' => 'first_product', 'title' => 'إضافة أول منتج', 'status' => 'pending', 'payload' => ['required' => ['name', 'price', 'stock', 'image']]],
            ['store_id' => $storeId, 'step_key' => 'domain', 'title' => 'ربط الدومين', 'status' => 'pending', 'payload' => ['dns' => 'CNAME store.solve.sa']],
        ];
    }

    private function ensureMarketplaceApps()
    {
        $defaults = [
            ['name' => 'Mada Payments', 'category' => 'الدفع', 'provider' => 'Solve Pay', 'status' => 'available', 'description' => 'ربط مدى والفواتير والتسويات.'],
            ['name' => 'Aramex Shipping', 'category' => 'الشحن', 'provider' => 'Aramex', 'status' => 'available', 'description' => 'إنشاء وتتبع الشحنات.'],
            ['name' => 'Marketing Pixels', 'category' => 'التسويق', 'provider' => 'Solve Growth', 'status' => 'available', 'description' => 'إدارة بيكسلات الحملات والتحويلات.'],
            ['name' => 'Advanced Analytics', 'category' => 'التحليلات', 'provider' => 'Solve BI', 'status' => 'installed', 'description' => 'تقارير مبيعات وطلبات متقدمة.'],
        ];

        if (! Schema::hasTable('marketplace_apps')) {
            return collect($defaults);
        }

        foreach ($defaults as $app) {
            MarketplaceApp::query()->firstOrCreate(['name' => $app['name']], $app);
        }

        return MarketplaceApp::query()->orderBy('category')->orderBy('name')->get();
    }

    private function ensureStoreSettings(string $storeId)
    {
        $defaults = [
            'identity' => ['store_id' => $storeId, 'legal_name' => 'Store Legal Entity', 'support_email' => 'support@example.sa'],
            'branding' => ['logo' => 'solve-logo.png', 'primary_color' => '#4f46e5', 'accent_color' => '#0ea5e9'],
            'payments' => ['mada' => true, 'visa' => true, 'apple_pay' => true],
            'shipping' => ['default_carrier' => 'Aramex', 'cod_enabled' => false],
            'taxes' => ['vat_enabled' => true, 'vat_rate' => '15%'],
            'invoices' => ['prefix' => 'INV', 'qr_enabled' => true],
        ];

        if (! Schema::hasTable('store_settings')) {
            return (object) array_merge(['store_id' => $storeId], $defaults);
        }

        return StoreSetting::query()->firstOrCreate(['store_id' => $storeId], $defaults);
    }

    private function sectionRecords(string $section): array
    {
        return AdminSectionStore::get($section, $this->defaultRecords()[$section] ?? []);
    }

    private function sectionMeta(string $section): array
    {
        $meta = $this->sectionDefinitions()[$section] ?? null;
        abort_unless($meta, 404);
        return $meta;
    }

    private function tableConfig(array $meta, array $records): array
    {
        $nameField = $meta['table']['name'] ?? $meta['fields'][0]['name'];
        $subField = $meta['table']['sub'] ?? ($meta['fields'][1]['name'] ?? $nameField);
        $detailField = $meta['table']['detail'] ?? ($meta['fields'][2]['name'] ?? $subField);
        $valueField = $meta['table']['value'] ?? ($meta['fields'][3]['name'] ?? $detailField);
        $supportField = $meta['table']['support'] ?? ($meta['fields'][4]['name'] ?? $valueField);

        return [
            'title' => $meta['title'] . ' Ledger',
            'description' => 'Editable and exportable records.',
            'columns' => ['Primary', 'Details', 'Status', 'Value', 'Actions'],
            'rows' => array_map(function (array $record) use ($meta, $nameField, $subField, $detailField, $valueField, $supportField): array {
                $actions = $this->sectionOperationalActions($record, $meta);

                if ($actions === []) {
                    $actions = collect($meta['statusOptions'])
                        ->reject(fn ($option) => $option === $this->all())
                        ->map(fn ($option) => $this->buildTableAction('Set ' . $option, 'status', $option, $option === ($record[$meta['statusField']] ?? ''), 'neutral'))
                        ->values()
                        ->all();
                }

                return [
                    'id' => $record['id'],
                    'cells' => [
                        ['primary' => $record[$nameField] ?? '', 'secondary' => $record[$subField] ?? ''],
                        ['primary' => $record[$detailField] ?? '', 'secondary' => $record[$supportField] ?? ($record['updated_at_human'] ?? '')],
                        ['badge' => $record[$meta['statusField']] ?? ''],
                        ['primary' => $record[$valueField] ?? '', 'secondary' => $record['updated_at_human'] ?? ''],
                    ],
                    'actions' => $actions,
                ];
            }, $records),
        ];
    }
    private function sectionDefinitions(): array
    {
        return [
            'stores' => [
                'route' => 'admin.stores',
                'title' => 'Store Management',
                'description' => 'Step-by-step onboarding wizard for a professional store launch.',
                'entityLabel' => $this->storeLabel(),
                'searchFields' => ['name', 'brand_name', 'owner', 'plan', 'domain', 'city'],
                'statusField' => 'status',
                'statusOptions' => [$this->all(), $this->active(), $this->review(), $this->suspended()],
                'statusActiveValue' => $this->active(),
                'searchPlaceholder' => 'Search by store, owner, domain, or city',
                'groups' => [
                    'identity' => ['label' => 'Store Identity', 'description' => 'Owner, contacts, and branding.'],
                    'commercial' => ['label' => 'Commercial Setup', 'description' => 'Plan, category, status, and domain.'],
                    'operations' => ['label' => 'Operations', 'description' => 'Payments, shipping, and inventory.'],
                    'growth' => ['label' => 'Growth & Launch', 'description' => 'Targets, launch stage, and notes.'],
                    'compliance' => ['label' => 'Files & Compliance', 'description' => 'Official documents and signed contract.'],
                ],
                'table' => ['name' => 'name', 'sub' => 'owner', 'detail' => 'plan', 'value' => 'domain', 'support' => 'city'],
                'fields' => [
                    ['name' => 'name', 'label' => "\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631}", 'group' => 'identity'],
                    ['name' => 'brand_name', 'label' => 'Brand Name', 'group' => 'identity'],
                    ['name' => 'owner', 'label' => 'Owner', 'group' => 'identity'],
                    ['name' => 'owner_email', 'label' => 'Email', 'type' => 'email', 'group' => 'identity'],
                    ['name' => 'owner_phone', 'label' => 'Phone', 'type' => 'tel', 'group' => 'identity'],
                    ['name' => 'business_whatsapp', 'label' => 'Business WhatsApp', 'type' => 'tel', 'group' => 'identity', 'required' => false],
                    ['name' => 'support_email', 'label' => 'Support Email', 'type' => 'email', 'group' => 'identity', 'required' => false],
                    ['name' => 'store_description', 'label' => 'Store Description', 'type' => 'textarea', 'group' => 'identity', 'span' => 'full', 'required' => false],
                    ['name' => 'logo_file', 'label' => 'Store Logo', 'type' => 'image', 'group' => 'identity', 'required' => false],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'group' => 'commercial', 'options' => [$this->active(), $this->review(), $this->suspended()]],
                    ['name' => 'plan', 'label' => 'Plan', 'type' => 'select', 'group' => 'commercial', 'options' => ['Starter', 'Growth', 'Enterprise', 'Enterprise Plus']],
                    ['name' => 'segment', 'label' => 'Category', 'type' => 'select', 'group' => 'commercial', 'options' => ['Fashion', 'Beauty', 'Home', 'Electronics', 'Food']],
                    ['name' => 'domain', 'label' => 'Domain', 'group' => 'commercial'],
                    ['name' => 'city', 'label' => 'City', 'group' => 'commercial'],
                    ['name' => 'launch_date', 'label' => 'Launch Date', 'type' => 'date', 'group' => 'operations'],
                    ['name' => 'team_size', 'label' => 'Team Size', 'group' => 'operations'],
                    ['name' => 'payment_gateway', 'label' => 'Payment Gateway', 'type' => 'select', 'group' => 'operations', 'options' => [$this->mada(), 'Apple Pay', 'Visa', $this->tabby()]],
                    ['name' => 'shipping_partner', 'label' => 'Shipping Partner', 'type' => 'select', 'group' => 'operations', 'options' => [$this->aramex(), 'SMSA', 'Saudi Post']],
                    ['name' => 'inventory_source', 'label' => 'Inventory Source', 'type' => 'select', 'group' => 'operations', 'options' => ['Manual', 'ERP', 'POS', 'CSV']],
                    ['name' => 'monthly_target', 'label' => 'Monthly Target', 'group' => 'growth'],
                    ['name' => 'expected_orders', 'label' => 'Expected Orders', 'group' => 'growth'],
                    ['name' => 'sales', 'label' => 'Current Sales', 'group' => 'growth'],
                    ['name' => 'orders', 'label' => 'Current Orders', 'group' => 'growth'],
                    ['name' => 'created_at', 'label' => 'Created At', 'group' => 'growth'],
                    ['name' => 'onboarding_stage', 'label' => 'Onboarding Stage', 'type' => 'select', 'group' => 'growth', 'options' => ['New', 'Setup', 'Ready']],
                    ['name' => 'notes', 'label' => 'Operational Notes', 'type' => 'textarea', 'group' => 'growth', 'span' => 'full', 'required' => false],
                    ['name' => 'commercial_register_file', 'label' => 'Commercial Register', 'type' => 'file', 'group' => 'compliance', 'required' => false],
                    ['name' => 'tax_certificate_file', 'label' => 'Tax Certificate', 'type' => 'file', 'group' => 'compliance', 'required' => false],
                    ['name' => 'identity_document_file', 'label' => 'Identity Document', 'type' => 'file', 'group' => 'compliance', 'required' => false],
                    ['name' => 'contract_file', 'label' => 'Signed Contract', 'type' => 'file', 'group' => 'compliance', 'required' => false],
                ],
            ],
            'orders' => $this->simpleSection('admin.orders', 'إدارة الطلبات', 'طلب', ['order_number', 'store', 'customer', 'status', 'total', 'payment_status', 'shipping_status', 'created_at'], ['order_number', 'store', 'customer'], [
                'description' => 'متابعة الطلبات، تغيير الحالة، طباعة الفواتير، وتتبع الشحن لكل متجر.',
                'statusOptions' => [$this->all(), 'جديد', 'قيد المعالجة', 'تم الشحن', 'مكتمل', 'ملغي'],
                'statusActiveValue' => 'مكتمل',
                'searchPlaceholder' => 'ابحث برقم الطلب أو المتجر أو العميل',
                'table' => ['name' => 'order_number', 'sub' => 'store', 'detail' => 'customer', 'value' => 'total', 'support' => 'shipping_status'],
            ]),
            'products' => $this->simpleSection('admin.products', 'إدارة المنتجات', 'منتج', ['product', 'store', 'category', 'status', 'price', 'stock', 'sku', 'updated_at_human'], ['product', 'store', 'category', 'sku'], [
                'description' => 'إدارة المنتجات والتصنيفات والمخزون والأسعار والصور وحالات النشر.',
                'statusOptions' => [$this->all(), 'منشور', 'مسودة', 'مخزون منخفض', 'موقوف'],
                'statusActiveValue' => 'منشور',
                'searchPlaceholder' => 'ابحث باسم المنتج أو SKU أو المتجر',
                'table' => ['name' => 'product', 'sub' => 'store', 'detail' => 'category', 'value' => 'price', 'support' => 'stock'],
            ]),
            'customers' => $this->simpleSection('admin.customers', 'إدارة العملاء', 'عميل', ['customer', 'store', 'email', 'status', 'orders', 'total_spent', 'last_order', 'notes'], ['customer', 'store', 'email'], [
                'description' => 'عرض العملاء، تفاصيل الإنفاق، الطلبات السابقة، الحالة والملاحظات الداخلية.',
                'statusOptions' => [$this->all(), 'نشط', 'VIP', 'بحاجة متابعة', 'موقوف'],
                'statusActiveValue' => 'نشط',
                'searchPlaceholder' => 'ابحث باسم العميل أو البريد أو المتجر',
                'table' => ['name' => 'customer', 'sub' => 'store', 'detail' => 'email', 'value' => 'total_spent', 'support' => 'last_order'],
            ]),
            'subscriptions' => [
                'route' => 'admin.subscriptions',
                'title' => 'Subscriptions',
                'description' => 'Structured subscription lifecycle with billing, renewals, and account health.',
                'entityLabel' => $this->subscriptionLabel(),
                'searchFields' => ['store', 'owner', 'plan', 'account_manager', 'invoice_status'],
                'statusField' => 'status',
                'statusOptions' => [$this->all(), $this->active(), $this->trial(), $this->pendingPayment(), $this->suspended(), $this->expired()],
                'statusActiveValue' => $this->active(),
                'searchPlaceholder' => 'Search by store, owner, plan, or account manager',
                'groups' => [
                    'identity' => ['label' => "\u{0647}\u{0648}\u{064A}\u{0629} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'description' => "\u{0628}\u{064A}\u{0627}\u{0646}\u{0627}\u{062A} \u{0627}\u{0644}\u{0639}\u{0645}\u{064A}\u{0644} \u{0648}\u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631} \u{0648}\u{0645}\u{062F}\u{064A}\u{0631} \u{0627}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628}."],
                    'commercial' => ['label' => "\u{0627}\u{0644}\u{0628}\u{0627}\u{0642}\u{0629} \u{0648}\u{0627}\u{0644}\u{062A}\u{0639}\u{0627}\u{0642}\u{062F}", 'description' => "\u{0627}\u{0644}\u{062E}\u{0637}\u{0629} \u{0648}\u{062F}\u{0648}\u{0631}\u{0629} \u{0627}\u{0644}\u{0641}\u{0648}\u{062A}\u{0631}\u{0629} \u{0648}\u{0646}\u{0648}\u{0639} \u{0627}\u{0644}\u{0639}\u{0642}\u{062F}."],
                    'billing' => ['label' => "\u{0627}\u{0644}\u{0641}\u{0648}\u{062A}\u{0631}\u{0629} \u{0648}\u{0627}\u{0644}\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}", 'description' => "\u{0627}\u{0644}\u{0645}\u{0628}\u{0644}\u{063A}\u{060C} \u{0637}\u{0631}\u{064A}\u{0642}\u{0629} \u{0627}\u{0644}\u{062F}\u{0641}\u{0639}\u{060C} \u{0648}\u{0645}\u{0648}\u{0627}\u{0639}\u{064A}\u{062F} \u{0627}\u{0644}\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}."],
                    'success' => ['label' => "\u{0627}\u{0644}\u{0627}\u{0633}\u{062A}\u{062E}\u{062F}\u{0627}\u{0645} \u{0648}\u{0627}\u{0644}\u{0635}\u{062D}\u{0629}", 'description' => "\u{0627}\u{0644}\u{062D}\u{062F}\u{0648}\u{062F} \u{0627}\u{0644}\u{062A}\u{0634}\u{063A}\u{064A}\u{0644}\u{064A}\u{0629} \u{0648}\u{0645}\u{0624}\u{0634}\u{0631}\u{0627}\u{062A} \u{062C}\u{0627}\u{0647}\u{0632}\u{064A}\u{0629} \u{0627}\u{0644}\u{0627}\u{0633}\u{062A}\u{0645}\u{0631}\u{0627}\u{0631}."],
                    'documents' => ['label' => "\u{0627}\u{0644}\u{0648}\u{062B}\u{0627}\u{0626}\u{0642} \u{0648}\u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'description' => "\u{0645}\u{0644}\u{0641}\u{0627}\u{062A} \u{0627}\u{0644}\u{0639}\u{0642}\u{062F} \u{0648}\u{0627}\u{0644}\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631} \u{0648}\u{0645}\u{0644}\u{0627}\u{062D}\u{0638}\u{0627}\u{062A} \u{0627}\u{0644}\u{062A}\u{0645}\u{062F}\u{064A}\u{062F}."],
                ],
                'table' => ['name' => 'store', 'sub' => 'owner', 'detail' => 'plan', 'value' => 'amount', 'support' => 'renewal_date'],
                'fields' => [
                    ['name' => 'store', 'label' => "\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631}", 'group' => 'identity'],
                    ['name' => 'owner', 'label' => "\u{0627}\u{0633}\u{0645} \u{0635}\u{0627}\u{062D}\u{0628} \u{0627}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628}", 'group' => 'identity'],
                    ['name' => 'owner_email', 'label' => "\u{0627}\u{0644}\u{0628}\u{0631}\u{064A}\u{062F} \u{0627}\u{0644}\u{0625}\u{0644}\u{0643}\u{062A}\u{0631}\u{0648}\u{0646}\u{064A}", 'type' => 'email', 'group' => 'identity'],
                    ['name' => 'owner_phone', 'label' => "\u{0631}\u{0642}\u{0645} \u{0627}\u{0644}\u{062C}\u{0648}\u{0627}\u{0644}", 'type' => 'tel', 'group' => 'identity'],
                    ['name' => 'account_manager', 'label' => "\u{0645}\u{062F}\u{064A}\u{0631} \u{0627}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628}", 'group' => 'identity'],
                    ['name' => 'plan', 'label' => "\u{0627}\u{0644}\u{0628}\u{0627}\u{0642}\u{0629}", 'type' => 'select', 'group' => 'commercial', 'options' => ['Starter', 'Growth', 'Scale', 'Enterprise', 'Enterprise Plus']],
                    ['name' => 'status', 'label' => "\u{0627}\u{0644}\u{062D}\u{0627}\u{0644}\u{0629}", 'type' => 'select', 'group' => 'commercial', 'options' => [$this->active(), $this->trial(), $this->pendingPayment(), $this->suspended(), $this->expired()]],
                    ['name' => 'contract_type', 'label' => "\u{0646}\u{0648}\u{0639} \u{0627}\u{0644}\u{062A}\u{0639}\u{0627}\u{0642}\u{062F}", 'type' => 'select', 'group' => 'commercial', 'options' => ["\u{0633}\u{0646}\u{0648}\u{064A}", "\u{0631}\u{0628}\u{0639} \u{0633}\u{0646}\u{0648}\u{064A}", "\u{0634}\u{0647}\u{0631}\u{064A}"]],
                    ['name' => 'billing_cycle', 'label' => "\u{062F}\u{0648}\u{0631}\u{0629} \u{0627}\u{0644}\u{0641}\u{0648}\u{062A}\u{0631}\u{0629}", 'type' => 'select', 'group' => 'commercial', 'options' => ['Monthly', 'Quarterly', 'Annual']],
                    ['name' => 'renewal_mode', 'label' => "\u{0622}\u{0644}\u{064A}\u{0629} \u{0627}\u{0644}\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}", 'type' => 'select', 'group' => 'commercial', 'options' => [$this->automatic(), $this->manualRenewal()]],
                    ['name' => 'amount', 'label' => "\u{0642}\u{064A}\u{0645}\u{0629} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'group' => 'billing'],
                    ['name' => 'currency', 'label' => "\u{0627}\u{0644}\u{0639}\u{0645}\u{0644}\u{0629}", 'type' => 'select', 'group' => 'billing', 'options' => ['SAR', 'USD', 'AED']],
                    ['name' => 'payment_method', 'label' => "\u{0637}\u{0631}\u{064A}\u{0642}\u{0629} \u{0627}\u{0644}\u{062F}\u{0641}\u{0639}", 'type' => 'select', 'group' => 'billing', 'options' => [$this->mada(), 'Visa', 'Bank Transfer', $this->tabby()]],
                    ['name' => 'invoice_status', 'label' => "\u{062D}\u{0627}\u{0644}\u{0629} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629}", 'type' => 'select', 'group' => 'billing', 'options' => ["\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}", "\u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}"]],
                    ['name' => 'start_date', 'label' => "\u{062A}\u{0627}\u{0631}\u{064A}\u{062E} \u{0628}\u{062F}\u{0621} \u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}", 'type' => 'date', 'group' => 'billing'],
                    ['name' => 'renewal_date', 'label' => "\u{062A}\u{0627}\u{0631}\u{064A}\u{062E} \u{0627}\u{0644}\u{062A}\u{062C}\u{062F}\u{064A}\u{062F}", 'type' => 'date', 'group' => 'billing'],
                    ['name' => 'last_payment_date', 'label' => "\u{0622}\u{062E}\u{0631} \u{062F}\u{0641}\u{0639}\u{0629}", 'type' => 'date', 'group' => 'billing', 'required' => false],
                    ['name' => 'branches', 'label' => "\u{0639}\u{062F}\u{062F} \u{0627}\u{0644}\u{0641}\u{0631}\u{0648}\u{0639}", 'group' => 'success'],
                    ['name' => 'staff_seats', 'label' => "\u{0645}\u{0642}\u{0627}\u{0639}\u{062F} \u{0627}\u{0644}\u{0641}\u{0631}\u{064A}\u{0642}", 'group' => 'success'],
                    ['name' => 'orders_limit', 'label' => "\u{062D}\u{062F} \u{0627}\u{0644}\u{0637}\u{0644}\u{0628}\u{0627}\u{062A} \u{0627}\u{0644}\u{0634}\u{0647}\u{0631}\u{064A}", 'group' => 'success'],
                    ['name' => 'health_score', 'label' => "\u{0645}\u{0624}\u{0634}\u{0631} \u{0635}\u{062D}\u{0629} \u{0627}\u{0644}\u{062D}\u{0633}\u{0627}\u{0628}", 'group' => 'success'],
                    ['name' => 'support_sla', 'label' => "\u{0645}\u{0633}\u{062A}\u{0648}\u{0649} SLA", 'type' => 'select', 'group' => 'success', 'options' => ['Standard', 'Priority', 'Dedicated']],
                    ['name' => 'success_notes', 'label' => "\u{0645}\u{0644}\u{0627}\u{062D}\u{0638}\u{0627}\u{062A} \u{0627}\u{0644}\u{0627}\u{062D}\u{062A}\u{0641}\u{0627}\u{0638}", 'type' => 'textarea', 'group' => 'success', 'span' => 'full', 'required' => false],
                    ['name' => 'contract_file', 'label' => "\u{0627}\u{0644}\u{0639}\u{0642}\u{062F} \u{0627}\u{0644}\u{0645}\u{0648}\u{0642}\u{0639}", 'type' => 'file', 'group' => 'documents', 'required' => false],
                    ['name' => 'invoice_file', 'label' => "\u{0646}\u{0633}\u{062E}\u{0629} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629}", 'type' => 'file', 'group' => 'documents', 'required' => false],
                    ['name' => 'billing_notes', 'label' => "\u{0645}\u{0644}\u{0627}\u{062D}\u{0638}\u{0627}\u{062A} \u{0627}\u{0644}\u{0641}\u{0648}\u{062A}\u{0631}\u{0629} \u{0648}\u{0627}\u{0644}\u{062A}\u{0645}\u{062F}\u{064A}\u{062F}", 'type' => 'textarea', 'group' => 'documents', 'span' => 'full', 'required' => false],
                ],
            ],
            'payments' => [
                'route' => 'admin.payments',
                'title' => 'Payments',
                'description' => 'Financial operations, invoices, revenue visibility, and AI-assisted payment health.',
                'entityLabel' => $this->paymentLabel(),
                'searchFields' => ['gateway', 'region', 'invoice_number', 'customer_name'],
                'statusField' => 'status',
                'statusOptions' => [$this->all(), $this->active(), $this->monitoring(), $this->stopped()],
                'statusActiveValue' => $this->active(),
                'searchPlaceholder' => 'Search gateway, invoice, customer, or region',
                'groups' => [
                    'gateway' => ['label' => "\u{0628}\u{064A}\u{0627}\u{0646}\u{0627}\u{062A} \u{0627}\u{0644}\u{0628}\u{0648}\u{0627}\u{0628}\u{0629}", 'description' => "\u{0623}\u{062F}\u{0627}\u{0621} \u{0627}\u{0644}\u{0628}\u{0648}\u{0627}\u{0628}\u{0629} \u{0648}\u{0645}\u{0641}\u{0627}\u{062A}\u{064A}\u{062D} \u{0627}\u{0644}\u{062A}\u{0633}\u{0648}\u{064A}\u{0629}."],
                    'finance' => ['label' => "\u{0627}\u{0644}\u{0645}\u{0644}\u{0641} \u{0627}\u{0644}\u{0645}\u{0627}\u{0644}\u{064A}", 'description' => "\u{062F}\u{062E}\u{0644}\u{060C} \u{0635}\u{0627}\u{0641}\u{064A}\u{060C} \u{0636}\u{0631}\u{0627}\u{0626}\u{0628} \u{0648}\u{062A}\u{0633}\u{0648}\u{064A}\u{0627}\u{062A}."],
                    'invoice' => ['label' => "\u{0627}\u{0644}\u{0641}\u{0648}\u{0627}\u{062A}\u{064A}\u{0631}", 'description' => "\u{0628}\u{064A}\u{0627}\u{0646}\u{0627}\u{062A} \u{0627}\u{0644}\u{0641}\u{0627}\u{062A}\u{0648}\u{0631}\u{0629} \u{0648}\u{0627}\u{0644}\u{0639}\u{0645}\u{064A}\u{0644} \u{0648}\u{062D}\u{0627}\u{0644}\u{0629} \u{0627}\u{0644}\u{062A}\u{062D}\u{0635}\u{064A}\u{0644}."],
                    'ai' => ['label' => "\u{0627}\u{0644}\u{0630}\u{0643}\u{0627}\u{0621} \u{0648}\u{0627}\u{0644}\u{0645}\u{062E}\u{0627}\u{0637}\u{0631}", 'description' => "\u{0625}\u{0634}\u{0627}\u{0631}\u{0627}\u{062A} \u{0627}\u{0644}\u{0645}\u{062E}\u{0627}\u{0637}\u{0631} \u{0648}\u{0627}\u{0644}\u{062A}\u{0648}\u{0635}\u{064A}\u{0627}\u{062A} \u{0627}\u{0644}\u{062A}\u{0634}\u{063A}\u{064A}\u{0644}\u{064A}\u{0629}."],
                ],
                'table' => ['name' => 'gateway', 'sub' => 'invoice_number', 'detail' => 'customer_name', 'value' => 'gross_revenue', 'support' => 'settlements_pending'],
                'fields' => [
                    ['name' => 'gateway', 'label' => 'Gateway', 'group' => 'gateway'],
                    ['name' => 'region', 'label' => 'Region', 'group' => 'gateway'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'group' => 'gateway', 'options' => [$this->active(), $this->monitoring(), $this->stopped()]],
                    ['name' => 'merchant_id', 'label' => 'Merchant ID', 'group' => 'gateway', 'required' => false],
                    ['name' => 'success_rate', 'label' => 'Success Rate', 'group' => 'gateway'],
                    ['name' => 'failed_rate', 'label' => 'Failed Rate', 'group' => 'gateway'],
                    ['name' => 'refunds', 'label' => 'Refunds', 'group' => 'gateway'],
                    ['name' => 'settlement_cycle', 'label' => 'Settlement Cycle', 'group' => 'gateway'],
                    ['name' => 'gross_revenue', 'label' => 'Gross Revenue', 'group' => 'finance', 'required' => false],
                    ['name' => 'net_revenue', 'label' => 'Net Revenue', 'group' => 'finance', 'required' => false],
                    ['name' => 'tax_collected', 'label' => 'Tax Collected', 'group' => 'finance', 'required' => false],
                    ['name' => 'settlements_pending', 'label' => 'Settlements Pending', 'group' => 'finance', 'required' => false],
                    ['name' => 'average_ticket', 'label' => 'Average Ticket', 'group' => 'finance', 'required' => false],
                    ['name' => 'invoice_number', 'label' => 'Invoice Number', 'group' => 'invoice', 'required' => false],
                    ['name' => 'invoice_status', 'label' => 'Invoice Status', 'type' => 'select', 'group' => 'invoice', 'options' => ["\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}", "\u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}"], 'required' => false],
                    ['name' => 'invoice_amount', 'label' => 'Invoice Amount', 'group' => 'invoice', 'required' => false],
                    ['name' => 'tax_amount', 'label' => 'Tax Amount', 'group' => 'invoice', 'required' => false],
                    ['name' => 'customer_name', 'label' => 'Customer Name', 'group' => 'invoice', 'required' => false],
                    ['name' => 'customer_email', 'label' => 'Customer Email', 'type' => 'email', 'group' => 'invoice', 'required' => false],
                    ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date', 'group' => 'invoice', 'required' => false],
                    ['name' => 'risk_score', 'label' => 'Risk Score', 'group' => 'ai', 'required' => false],
                    ['name' => 'ai_summary', 'label' => 'AI Summary', 'type' => 'textarea', 'group' => 'ai', 'span' => 'full', 'required' => false],
                    ['name' => 'invoice_file', 'label' => 'Invoice File', 'type' => 'file', 'group' => 'invoice', 'required' => false],
                ],
            ],
            'shipping' => $this->simpleSection('admin.shipping', 'Shipping', 'Shipping Partner', ['carrier', 'coverage', 'service_level', 'status', 'deliveries', 'delay', 'score'], ['carrier', 'coverage', 'service_level'], [
                'description' => "\u{062A}\u{0634}\u{063A}\u{064A}\u{0644} \u{0634}\u{0631}\u{0643}\u{0627}\u{062A} \u{0627}\u{0644}\u{0634}\u{062D}\u{0646} \u{0648}\u{0627}\u{0644}\u{062A}\u{0635}\u{0639}\u{064A}\u{062F} \u{0648}\u{0627}\u{0633}\u{062A}\u{0645}\u{0631}\u{0627}\u{0631}\u{064A}\u{0629} \u{0627}\u{0644}\u{062E}\u{062F}\u{0645}\u{0629}.",
                'statusOptions' => [$this->all(), $this->activeLabel(), $this->review(), $this->pausedLabel()],
                'statusActiveValue' => $this->activeLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{0634}\u{0627}\u{062D}\u{0646} \u{0623}\u{0648} \u{0646}\u{0637}\u{0627}\u{0642} \u{0627}\u{0644}\u{062A}\u{063A}\u{0637}\u{064A}\u{0629} \u{0623}\u{0648} \u{0645}\u{0633}\u{062A}\u{0648}\u{0649} \u{0627}\u{0644}\u{062E}\u{062F}\u{0645}\u{0629}",
                'table' => ['name' => 'carrier', 'sub' => 'coverage', 'detail' => 'service_level', 'value' => 'deliveries', 'support' => 'delay'],
            ]),
            'coupons' => $this->simpleSection('admin.coupons', 'الكوبونات والعروض', 'كوبون', ['code', 'store_scope', 'discount', 'status', 'starts_at', 'ends_at', 'usage_limit', 'used'], ['code', 'store_scope', 'discount'], [
                'description' => 'إنشاء الكوبونات والعروض وربطها بمتجر محدد أو كل المتاجر مع تتبع الاستخدام.',
                'statusOptions' => [$this->all(), 'نشط', 'مجدول', 'منتهي', 'موقوف'],
                'statusActiveValue' => 'نشط',
                'searchPlaceholder' => 'ابحث برمز الكوبون أو نطاق المتجر',
                'table' => ['name' => 'code', 'sub' => 'store_scope', 'detail' => 'discount', 'value' => 'used', 'support' => 'ends_at'],
            ]),
            'analytics' => $this->simpleSection('admin.analytics', 'Analytics', 'Report', ['report', 'description', 'owner', 'audience', 'status', 'period', 'metric', 'priority'], ['report', 'owner', 'metric'], [
                'description' => "\u{0625}\u{062F}\u{0627}\u{0631}\u{0629} \u{0627}\u{0644}\u{062A}\u{0642}\u{0627}\u{0631}\u{064A}\u{0631} \u{0648}\u{0627}\u{0644}\u{0646}\u{0634}\u{0631} \u{0648}\u{062F}\u{0648}\u{0631}\u{0627}\u{062A} \u{062A}\u{062D}\u{062F}\u{064A}\u{062B} \u{0627}\u{0644}\u{0628}\u{064A}\u{0627}\u{0646}\u{0627}\u{062A}.",
                'statusOptions' => [$this->all(), $this->publishedLabel(), $this->draftLabel(), $this->archivedLabel()],
                'statusActiveValue' => $this->publishedLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{062A}\u{0642}\u{0631}\u{064A}\u{0631} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{0633}\u{0624}\u{0648}\u{0644} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{0624}\u{0634}\u{0631}",
                'table' => ['name' => 'report', 'sub' => 'owner', 'detail' => 'metric', 'value' => 'period', 'support' => 'priority'],
            ]),
            'partners' => $this->simpleSection('admin.partners', 'Partners', 'Service', ['service', 'category', 'owner', 'capacity', 'status', 'requests', 'lead_time'], ['service', 'category', 'owner'], [
                'description' => "\u{062C}\u{0627}\u{0647}\u{0632}\u{064A}\u{0629} \u{0627}\u{0644}\u{0634}\u{0631}\u{0643}\u{0627}\u{0621} \u{0648}\u{0627}\u{0644}\u{0633}\u{0639}\u{0629} \u{0648}\u{0627}\u{0644}\u{0627}\u{0639}\u{062A}\u{0645}\u{0627}\u{062F}\u{0627}\u{062A}.",
                'statusOptions' => [$this->all(), $this->activeLabel(), $this->review(), $this->pausedLabel()],
                'statusActiveValue' => $this->activeLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{062E}\u{062F}\u{0645}\u{0629} \u{0623}\u{0648} \u{0627}\u{0644}\u{0641}\u{0626}\u{0629} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{0633}\u{0624}\u{0648}\u{0644}",
                'table' => ['name' => 'service', 'sub' => 'category', 'detail' => 'owner', 'value' => 'capacity', 'support' => 'lead_time'],
            ]),
            'support' => $this->simpleSection('admin.support', 'Support', 'Ticket', ['ticket', 'store', 'type', 'priority', 'assignee', 'channel', 'status', 'sla'], ['ticket', 'store', 'assignee'], [
                'description' => "\u{0625}\u{062F}\u{0627}\u{0631}\u{0629} \u{0627}\u{0644}\u{062F}\u{0639}\u{0645} \u{0645}\u{0639} \u{0627}\u{0644}\u{062A}\u{0648}\u{0632}\u{064A}\u{0639} \u{0648}\u{0627}\u{0644}\u{062A}\u{0635}\u{0639}\u{064A}\u{062F} \u{0648}\u{0645}\u{062A}\u{0627}\u{0628}\u{0639}\u{0629} SLA.",
                'statusOptions' => [$this->all(), $this->openLabel(), $this->assignedLabel(), $this->resolvedLabel(), $this->escalatedLabel()],
                'statusActiveValue' => $this->resolvedLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0631}\u{0642}\u{0645} \u{0627}\u{0644}\u{062A}\u{0630}\u{0643}\u{0631}\u{0629} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{0648}\u{0638}\u{0641}",
                'table' => ['name' => 'ticket', 'sub' => 'store', 'detail' => 'priority', 'value' => 'assignee', 'support' => 'sla'],
            ]),
            'apps' => $this->simpleSection('admin.apps', 'Apps', 'App Version', ['platform', 'version', 'status', 'users', 'health', 'store_rating', 'release_date'], ['platform', 'version'], [
                'description' => "\u{062A}\u{0634}\u{063A}\u{064A}\u{0644} \u{0627}\u{0644}\u{0625}\u{0635}\u{062F}\u{0627}\u{0631}\u{0627}\u{062A} \u{0648}\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629} \u{0627}\u{0644}\u{0635}\u{062D}\u{0629} \u{0648}\u{062C}\u{0627}\u{0647}\u{0632}\u{064A}\u{0629} \u{0627}\u{0644}\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}.",
                'statusOptions' => [$this->all(), $this->stableLabel(), $this->monitoringLabel(), $this->rollbackLabel()],
                'statusActiveValue' => $this->stableLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{0645}\u{0646}\u{0635}\u{0629} \u{0623}\u{0648} \u{0627}\u{0644}\u{0625}\u{0635}\u{062F}\u{0627}\u{0631}",
                'table' => ['name' => 'platform', 'sub' => 'version', 'detail' => 'health', 'value' => 'users', 'support' => 'release_date'],
            ]),
            'settings' => $this->simpleSection('admin.settings', 'Settings', 'Setting Module', ['module', 'description', 'scope', 'dependencies', 'owner', 'status', 'last_review', 'environment'], ['module', 'scope', 'owner'], [
                'description' => "\u{062A}\u{0634}\u{063A}\u{064A}\u{0644} \u{0627}\u{0644}\u{0625}\u{0639}\u{062F}\u{0627}\u{062F}\u{0627}\u{062A} \u{0648}\u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0627}\u{062A} \u{0648}\u{0627}\u{0644}\u{062A}\u{062D}\u{0643}\u{0645} \u{0628}\u{0627}\u{0644}\u{0625}\u{0646}\u{062A}\u{0627}\u{062C}.",
                'statusOptions' => [$this->all(), $this->enabledLabel(), $this->review(), $this->disabledLabel()],
                'statusActiveValue' => $this->enabledLabel(),
                'searchPlaceholder' => "\u{0627}\u{0628}\u{062D}\u{062B} \u{0628}\u{0627}\u{0633}\u{0645} \u{0627}\u{0644}\u{0648}\u{062D}\u{062F}\u{0629} \u{0623}\u{0648} \u{0627}\u{0644}\u{0646}\u{0637}\u{0627}\u{0642} \u{0623}\u{0648} \u{0627}\u{0644}\u{0645}\u{0633}\u{0624}\u{0648}\u{0644}",
                'table' => ['name' => 'module', 'sub' => 'scope', 'detail' => 'owner', 'value' => 'environment', 'support' => 'last_review'],
            ]),
            'staff' => $this->simpleSection('admin.staff', 'الموظفين والصلاحيات', 'مستخدم', ['name', 'email', 'role', 'status', 'store_scope', 'permissions', 'last_login', 'notes'], ['name', 'email', 'role', 'store_scope'], [
                'description' => 'إدارة الموظفين والأدوار والصلاحيات وربط المستخدمين بنطاق متجر محدد.',
                'statusOptions' => [$this->all(), 'نشط', 'موقوف', 'بانتظار التفعيل'],
                'statusActiveValue' => 'نشط',
                'searchPlaceholder' => 'ابحث باسم الموظف أو الدور أو المتجر',
                'table' => ['name' => 'name', 'sub' => 'role', 'detail' => 'store_scope', 'value' => 'permissions', 'support' => 'last_login'],
            ]),
        ];
    }

    private function defaultRecords(): array
    {
        return [
            'stores' => [
                ['id' => 'store-atlas', 'name' => "\u{0645}\u{062A}\u{062C}\u{0631} \u{0623}\u{0637}\u{0644}\u{0633}", 'brand_name' => 'Atlas Fashion', 'owner' => 'Sara Alharbi', 'owner_email' => 'sara@atlas.sa', 'owner_phone' => '+966500000001', 'status' => $this->active(), 'plan' => 'Enterprise', 'segment' => 'Fashion', 'domain' => 'atlas.solve.sa', 'city' => 'Riyadh', 'launch_date' => '2026-01-15', 'team_size' => '12', 'payment_gateway' => $this->mada(), 'shipping_partner' => $this->aramex(), 'inventory_source' => 'ERP', 'monthly_target' => '450,000 SAR', 'expected_orders' => '2,400', 'sales' => '418,200 SAR', 'orders' => '2,418', 'created_at' => '15 Jan 2026', 'onboarding_stage' => 'Ready', 'notes' => 'Stable store.', 'updated_at_human' => '12 minutes ago'],
                ['id' => 'store-abaad', 'name' => 'Abaad', 'brand_name' => 'Abaad Home', 'owner' => 'Mohammed', 'owner_email' => 'mohammed@abaad.sa', 'owner_phone' => '+966500000002', 'status' => $this->review(), 'plan' => 'Growth', 'segment' => 'Home', 'domain' => 'abaad.solve.sa', 'city' => 'Jeddah', 'launch_date' => '2026-02-01', 'team_size' => '6', 'payment_gateway' => 'Apple Pay', 'shipping_partner' => 'SMSA', 'inventory_source' => 'POS', 'monthly_target' => '180,000 SAR', 'expected_orders' => '940', 'sales' => '94,300 SAR', 'orders' => '711', 'created_at' => '01 Feb 2026', 'onboarding_stage' => 'Setup', 'notes' => 'Pending policy review.', 'updated_at_human' => '35 minutes ago'],
                ['id' => 'store-rowaa', 'name' => 'Rowaa', 'brand_name' => 'Rowaa Beauty', 'owner' => 'Noura', 'owner_email' => 'noura@rowaa.sa', 'owner_phone' => '+966500000003', 'status' => $this->review(), 'plan' => 'Starter', 'segment' => 'Beauty', 'domain' => 'rowaa.solve.sa', 'city' => 'Dammam', 'launch_date' => '2026-03-04', 'team_size' => '4', 'payment_gateway' => 'Visa', 'shipping_partner' => 'Saudi Post', 'inventory_source' => 'CSV', 'monthly_target' => '95,000 SAR', 'expected_orders' => '520', 'sales' => '22,900 SAR', 'orders' => '143', 'created_at' => '04 Mar 2026', 'onboarding_stage' => 'New', 'notes' => 'Awaiting payment setup.', 'updated_at_human' => '1 hour ago'],
                ['id' => 'store-shahd', 'name' => 'Shahd', 'brand_name' => 'Shahd Foods', 'owner' => 'Shahd', 'owner_email' => 'shahd@foods.sa', 'owner_phone' => '+966500000004', 'status' => $this->suspended(), 'plan' => 'Growth', 'segment' => 'Food', 'domain' => 'shahd.solve.sa', 'city' => 'Qassim', 'launch_date' => '2026-03-09', 'team_size' => '5', 'payment_gateway' => $this->tabby(), 'shipping_partner' => $this->aramex(), 'inventory_source' => 'Manual', 'monthly_target' => '130,000 SAR', 'expected_orders' => '780', 'sales' => '0 SAR', 'orders' => '0', 'created_at' => '09 Mar 2026', 'onboarding_stage' => 'Setup', 'notes' => 'Waiting for compliance files.', 'updated_at_human' => '2 hours ago'],
            ],
            'orders' => [
                ['id' => 'order-1001', 'order_number' => 'ORD-1001', 'store' => 'متجر أطلس', 'customer' => 'نورة سالم', 'status' => 'مكتمل', 'total' => '820 SAR', 'payment_status' => 'مدفوع', 'shipping_status' => 'تم التسليم', 'created_at' => '2026-05-12', 'updated_at_human' => '8 minutes ago'],
                ['id' => 'order-1002', 'order_number' => 'ORD-1002', 'store' => 'Abaad', 'customer' => 'محمد العتيبي', 'status' => 'قيد المعالجة', 'total' => '1,240 SAR', 'payment_status' => 'مدفوع', 'shipping_status' => 'بانتظار التجهيز', 'created_at' => '2026-05-12', 'updated_at_human' => '18 minutes ago'],
                ['id' => 'order-1003', 'order_number' => 'ORD-1003', 'store' => 'Rowaa', 'customer' => 'لمى فهد', 'status' => 'تم الشحن', 'total' => '510 SAR', 'payment_status' => 'معلق', 'shipping_status' => 'في الطريق', 'created_at' => '2026-05-11', 'updated_at_human' => '1 hour ago'],
            ],
            'products' => [
                ['id' => 'product-atlas-1', 'product' => 'عباية أطلس كلاسيك', 'store' => 'متجر أطلس', 'category' => 'Fashion', 'status' => 'منشور', 'price' => '320 SAR', 'stock' => '48', 'sku' => 'AT-100', 'updated_at_human' => '12 minutes ago'],
                ['id' => 'product-rowaa-1', 'product' => 'سيروم ترطيب', 'store' => 'Rowaa', 'category' => 'Beauty', 'status' => 'مخزون منخفض', 'price' => '145 SAR', 'stock' => '4', 'sku' => 'RB-18', 'updated_at_human' => '50 minutes ago'],
                ['id' => 'product-abaad-1', 'product' => 'طقم ضيافة منزلي', 'store' => 'Abaad', 'category' => 'Home', 'status' => 'منشور', 'price' => '690 SAR', 'stock' => '22', 'sku' => 'AB-220', 'updated_at_human' => '2 hours ago'],
            ],
            'customers' => [
                ['id' => 'customer-noura', 'customer' => 'نورة سالم', 'store' => 'متجر أطلس', 'email' => 'noura@example.sa', 'status' => 'VIP', 'orders' => '8', 'total_spent' => '4,120 SAR', 'last_order' => '2026-05-12', 'notes' => 'عميلة عالية القيمة', 'updated_at_human' => '15 minutes ago'],
                ['id' => 'customer-mohammed', 'customer' => 'محمد العتيبي', 'store' => 'Abaad', 'email' => 'mohammed@example.sa', 'status' => 'نشط', 'orders' => '5', 'total_spent' => '2,840 SAR', 'last_order' => '2026-05-12', 'notes' => 'يفضل الدفع بمدى', 'updated_at_human' => '30 minutes ago'],
                ['id' => 'customer-lama', 'customer' => 'لمى فهد', 'store' => 'Rowaa', 'email' => 'lama@example.sa', 'status' => 'بحاجة متابعة', 'orders' => '2', 'total_spent' => '510 SAR', 'last_order' => '2026-05-11', 'notes' => 'طلب مفتوح', 'updated_at_human' => '1 hour ago'],
            ],
            'subscriptions' => [
                ['id' => 'subscription-atlas', 'store' => "\u{0645}\u{062A}\u{062C}\u{0631} \u{0623}\u{0637}\u{0644}\u{0633}", 'owner' => 'Sara Alharbi', 'owner_email' => 'sara@atlas.sa', 'owner_phone' => '+966500000001', 'account_manager' => 'Nada', 'plan' => 'Enterprise', 'status' => $this->active(), 'contract_type' => "\u{0633}\u{0646}\u{0648}\u{064A}", 'billing_cycle' => 'Annual', 'renewal_mode' => $this->automatic(), 'amount' => '14,400 SAR', 'currency' => 'SAR', 'payment_method' => $this->mada(), 'invoice_status' => "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", 'start_date' => '2026-01-15', 'renewal_date' => '2027-01-15', 'last_payment_date' => '2026-01-15', 'branches' => '4', 'staff_seats' => '18', 'orders_limit' => '12,000', 'health_score' => '94%', 'support_sla' => 'Dedicated', 'success_notes' => 'Healthy enterprise account with strong renewal readiness.', 'billing_notes' => 'Annual invoice settled in full.', 'updated_at_human' => '20 minutes ago'],
                ['id' => 'subscription-abaad', 'store' => 'Abaad', 'owner' => 'Mohammed Almutairi', 'owner_email' => 'mohammed@abaad.sa', 'owner_phone' => '+966500000002', 'account_manager' => 'Reem', 'plan' => 'Growth', 'status' => $this->trial(), 'contract_type' => "\u{0634}\u{0647}\u{0631}\u{064A}", 'billing_cycle' => 'Monthly', 'renewal_mode' => $this->manualRenewal(), 'amount' => '2,400 SAR', 'currency' => 'SAR', 'payment_method' => 'Visa', 'invoice_status' => "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}", 'start_date' => '2026-03-20', 'renewal_date' => '2026-04-20', 'last_payment_date' => '2026-03-20', 'branches' => '2', 'staff_seats' => '7', 'orders_limit' => '3,000', 'health_score' => '71%', 'support_sla' => 'Priority', 'success_notes' => 'Trial account nearing commercial conversion.', 'billing_notes' => 'Pending first post-trial invoice.', 'updated_at_human' => '45 minutes ago'],
                ['id' => 'subscription-rowaa', 'store' => 'Rowaa', 'owner' => 'Noura Salem', 'owner_email' => 'noura@rowaa.sa', 'owner_phone' => '+966500000003', 'account_manager' => 'Layan', 'plan' => 'Starter', 'status' => $this->pendingPayment(), 'contract_type' => "\u{0634}\u{0647}\u{0631}\u{064A}", 'billing_cycle' => 'Monthly', 'renewal_mode' => $this->manualRenewal(), 'amount' => '890 SAR', 'currency' => 'SAR', 'payment_method' => 'Bank Transfer', 'invoice_status' => "\u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}", 'start_date' => '2026-02-04', 'renewal_date' => '2026-04-10', 'last_payment_date' => '2026-03-04', 'branches' => '1', 'staff_seats' => '3', 'orders_limit' => '900', 'health_score' => '48%', 'support_sla' => 'Standard', 'success_notes' => 'Payment follow-up required before auto-upgrade.', 'billing_notes' => 'Outstanding invoice needs reconciliation.', 'updated_at_human' => '2 hours ago'],
            ],
            'payments' => [
                ['id' => 'payment-mada', 'gateway' => $this->mada(), 'region' => 'KSA', 'status' => $this->active(), 'merchant_id' => 'MADA-KSA-01', 'success_rate' => '98.3%', 'failed_rate' => '1.7%', 'refunds' => '28', 'settlement_cycle' => '48 hours', 'gross_revenue' => '486,000 SAR', 'net_revenue' => '472,100 SAR', 'tax_collected' => '63,390 SAR', 'settlements_pending' => '42,000 SAR', 'average_ticket' => '214 SAR', 'invoice_number' => 'INV-PAY-240401', 'invoice_status' => "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}", 'invoice_amount' => '38,500 SAR', 'tax_amount' => '5,025 SAR', 'customer_name' => 'Atlas Fashion', 'customer_email' => 'finance@atlas.sa', 'due_date' => '2026-04-06', 'risk_score' => '22', 'ai_summary' => 'Gateway performance is stable and suitable for high-volume routing.', 'updated_at_human' => '18 minutes ago'],
                ['id' => 'payment-visa', 'gateway' => 'Visa', 'region' => 'GCC', 'status' => $this->monitoring(), 'merchant_id' => 'VISA-GCC-09', 'success_rate' => '95.8%', 'failed_rate' => '3.2%', 'refunds' => '14', 'settlement_cycle' => '72 hours', 'gross_revenue' => '214,000 SAR', 'net_revenue' => '203,900 SAR', 'tax_collected' => '27,913 SAR', 'settlements_pending' => '57,300 SAR', 'average_ticket' => '188 SAR', 'invoice_number' => 'INV-PAY-240402', 'invoice_status' => "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}", 'invoice_amount' => '21,400 SAR', 'tax_amount' => '2,792 SAR', 'customer_name' => 'Abaad Home', 'customer_email' => 'ops@abaad.sa', 'due_date' => '2026-04-08', 'risk_score' => '74', 'ai_summary' => 'Retry patterns increased in the last cycle; shift overflow traffic to Mada and review issuer responses.', 'updated_at_human' => '42 minutes ago'],
                ['id' => 'payment-bank', 'gateway' => 'Bank Transfer', 'region' => 'KSA', 'status' => $this->monitoring(), 'merchant_id' => 'BANK-KSA-12', 'success_rate' => '93.1%', 'failed_rate' => '2.9%', 'refunds' => '4', 'settlement_cycle' => '24 hours', 'gross_revenue' => '96,000 SAR', 'net_revenue' => '95,200 SAR', 'tax_collected' => '12,522 SAR', 'settlements_pending' => '18,400 SAR', 'average_ticket' => '420 SAR', 'invoice_number' => 'INV-PAY-240403', 'invoice_status' => "\u{0645}\u{062A}\u{0623}\u{062E}\u{0631}\u{0629}", 'invoice_amount' => '9,600 SAR', 'tax_amount' => '1,252 SAR', 'customer_name' => 'Rowaa Beauty', 'customer_email' => 'billing@rowaa.sa', 'due_date' => '2026-04-03', 'risk_score' => '68', 'ai_summary' => 'Collections risk is rising because the invoice is overdue and cash settlement is slower than target.', 'updated_at_human' => '1 hour ago'],
            ],
            'shipping' => [['id' => 'shipping-aramex', 'carrier' => $this->aramex(), 'coverage' => 'Local', 'service_level' => "\u{0645}\u{0645}\u{062A}\u{0627}\u{0632}", 'status' => $this->activeLabel(), 'deliveries' => '4280', 'delay' => '2.1%', 'score' => '4.8/5', 'updated_at_human' => '25 minutes ago']],
            'coupons' => [
                ['id' => 'coupon-welcome', 'code' => 'WELCOME20', 'store_scope' => 'كل المتاجر', 'discount' => '20%', 'status' => 'نشط', 'starts_at' => '2026-05-01', 'ends_at' => '2026-05-31', 'usage_limit' => '1000', 'used' => '248', 'updated_at_human' => '20 minutes ago'],
                ['id' => 'coupon-atlas', 'code' => 'ATLASVIP', 'store_scope' => 'متجر أطلس', 'discount' => '15%', 'status' => 'مجدول', 'starts_at' => '2026-05-15', 'ends_at' => '2026-06-01', 'usage_limit' => '300', 'used' => '0', 'updated_at_human' => '1 hour ago'],
            ],
            'analytics' => [['id' => 'analytics-sales', 'report' => 'Daily Sales', 'description' => 'Sales performance report', 'owner' => 'Growth Team', 'audience' => 'Management', 'status' => $this->publishedLabel(), 'period' => 'Last 30 days', 'metric' => 'GMV', 'priority' => 'High', 'updated_at_human' => '10 minutes ago']],
            'partners' => [['id' => 'partner-photo', 'service' => 'Product Photography', 'category' => 'Creative', 'owner' => 'Partner Team', 'capacity' => '18 stores/week', 'status' => $this->activeLabel(), 'requests' => '12', 'lead_time' => '3 days', 'updated_at_human' => '28 minutes ago']],
            'support' => [['id' => 'support-1', 'ticket' => 'Payment integration issue', 'store' => 'Rowaa', 'type' => 'Payment', 'priority' => 'High', 'assignee' => 'Ahmed', 'channel' => 'WhatsApp', 'status' => $this->openLabel(), 'sla' => '4 hours', 'updated_at_human' => '14 minutes ago']],
            'apps' => [['id' => 'app-ios', 'platform' => 'iOS', 'version' => '4.2.0', 'status' => $this->stableLabel(), 'users' => '18400', 'health' => '99.2%', 'store_rating' => '4.8', 'release_date' => '12 Mar 2026', 'updated_at_human' => '22 minutes ago']],
            'settings' => [['id' => 'setting-auth', 'module' => 'Admin Roles', 'description' => 'Permissions control', 'scope' => 'Dashboard', 'dependencies' => 'SSO', 'owner' => 'Security', 'status' => $this->enabledLabel(), 'last_review' => '10 Mar 2026', 'environment' => 'Production', 'updated_at_human' => '1 day ago']],
            'staff' => [
                ['id' => 'staff-admin', 'name' => 'مدير المنصة', 'email' => 'admin@solve.sa', 'role' => 'Super Admin', 'status' => 'نشط', 'store_scope' => 'كل المتاجر', 'permissions' => 'كل الصلاحيات', 'last_login' => 'منذ 5 دقائق', 'notes' => 'حساب رئيسي', 'updated_at_human' => '5 minutes ago'],
                ['id' => 'staff-partner-atlas', 'name' => 'سارة الحربي', 'email' => 'sara@atlas.sa', 'role' => 'Partner Admin', 'status' => 'نشط', 'store_scope' => 'store-atlas', 'permissions' => 'إدارة المتجر فقط', 'last_login' => 'منذ 12 دقيقة', 'notes' => 'حساب شريك', 'updated_at_human' => '12 minutes ago'],
                ['id' => 'staff-support', 'name' => 'فريق الدعم', 'email' => 'support@solve.sa', 'role' => 'Staff User', 'status' => 'نشط', 'store_scope' => 'الدعم الفني', 'permissions' => 'قراءة وتحديث التذاكر', 'last_login' => 'منذ ساعة', 'notes' => 'صلاحيات محدودة', 'updated_at_human' => '1 hour ago'],
            ],
        ];
    }

    private function simpleSection(string $route, string $title, string $entityLabel, array $fields, array $searchFields, array $extra = []): array
    {
        $definition = [
            'route' => $route,
            'title' => $title,
            'description' => $title . ' records.',
            'entityLabel' => $entityLabel,
            'searchFields' => $searchFields,
            'statusField' => 'status',
            'statusOptions' => [$this->all(), 'Active', "\u{062A}\u{062D}\u{0648}\u{064A}\u{0644} \u{0644}\u{0644}\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}", 'Paused'],
            'statusActiveValue' => $this->activeLabel(),
            'searchPlaceholder' => 'Search records',
            'fields' => collect($fields)->map(fn ($name) => ['name' => $name, 'label' => Str::title(str_replace('_', ' ', $name))])->all(),
        ];

        return array_replace_recursive($definition, $extra);
    }

    private function addMessage(string $entity): string { return "\u{062A}\u{0645}\u{062A} \u{0625}\u{0636}\u{0627}\u{0641}\u{0629} " . $entity . " \u{062C}\u{062F}\u{064A}\u{062F} \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}."; }
    private function editMessage(string $entity): string { return "\u{062A}\u{0645} \u{062A}\u{0639}\u{062F}\u{064A}\u{0644} " . $entity . " \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}."; }
    private function updateMessage(string $entity): string { return "\u{062A}\u{0645} \u{062A}\u{062D}\u{062F}\u{064A}\u{062B} " . $entity . " \u{0628}\u{0646}\u{062C}\u{0627}\u{062D}."; }
    private function storeLabel(): string { return "\u{0627}\u{0644}\u{0645}\u{062A}\u{062C}\u{0631}"; }
    private function paymentLabel(): string { return "\u{0628}\u{0648}\u{0627}\u{0628}\u{0629} \u{0627}\u{0644}\u{062F}\u{0641}\u{0639}"; }
    private function subscriptionLabel(): string { return "\u{0627}\u{0644}\u{0627}\u{0634}\u{062A}\u{0631}\u{0627}\u{0643}"; }
    private function all(): string { return "\u{0627}\u{0644}\u{0643}\u{0644}"; }
    private function active(): string { return "\u{0646}\u{0634}\u{0637}"; }
    private function review(): string { return "\u{0645}\u{0631}\u{0627}\u{062C}\u{0639}\u{0629}"; }
    private function suspended(): string { return "\u{0645}\u{0639}\u{0644}\u{0642}"; }
    private function trial(): string { return "\u{062A}\u{062C}\u{0631}\u{064A}\u{0628}\u{064A}"; }
    private function pendingPayment(): string { return "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}"; }
    private function expired(): string { return "\u{0645}\u{0646}\u{062A}\u{0647}\u{064A}"; }
    private function monitoring(): string { return "\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629}"; }
    private function stopped(): string { return "\u{0645}\u{062A}\u{0648}\u{0642}\u{0641}"; }
    private function mada(): string { return "\u{0645}\u{062F}\u{0649}"; }
    private function tabby(): string { return "\u{062A}\u{0627}\u{0628}\u{064A}"; }
    private function automatic(): string { return "\u{062A}\u{062C}\u{062F}\u{064A}\u{062F} \u{0622}\u{0644}\u{064A}"; }
    private function manualRenewal(): string { return "\u{062A}\u{062C}\u{062F}\u{064A}\u{062F} \u{064A}\u{062F}\u{0648}\u{064A}"; }
    private function aramex(): string { return "\u{0623}\u{0631}\u{0627}\u{0645}\u{0643}\u{0633}"; }
    private function invoicePaid(): string { return "\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0629}"; }
    private function invoicePending(): string { return "\u{0628}\u{0627}\u{0646}\u{062A}\u{0638}\u{0627}\u{0631} \u{0627}\u{0644}\u{0633}\u{062F}\u{0627}\u{062F}"; }
    private function activeLabel(): string { return "\u{0646}\u{0634}\u{0637}"; }
    private function pausedLabel(): string { return "\u{0645}\u{0648}\u{0642}\u{0648}\u{0641}"; }
    private function dispatchedLabel(): string { return "\u{062A}\u{0645} \u{0627}\u{0644}\u{0625}\u{0631}\u{0633}\u{0627}\u{0644}"; }
    private function escalatedLabel(): string { return "\u{0645}\u{0635}\u{0639}\u{062F}"; }
    private function publishedLabel(): string { return "\u{0645}\u{0646}\u{0634}\u{0648}\u{0631}"; }
    private function draftLabel(): string { return "\u{0645}\u{0633}\u{0648}\u{062F}\u{0629}"; }
    private function archivedLabel(): string { return "\u{0645}\u{0624}\u{0631}\u{0634}\u{0641}"; }
    private function openLabel(): string { return "\u{0645}\u{0641}\u{062A}\u{0648}\u{062D}"; }
    private function assignedLabel(): string { return "\u{0645}\u{0648}\u{0632}\u{0639}"; }
    private function resolvedLabel(): string { return "\u{0645}\u{063A}\u{0644}\u{0642}"; }
    private function stableLabel(): string { return "\u{0645}\u{0633}\u{062A}\u{0642}\u{0631}"; }
    private function monitoringLabel(): string { return "\u{062A}\u{062D}\u{062A} \u{0627}\u{0644}\u{0645}\u{0631}\u{0627}\u{0642}\u{0628}\u{0629}"; }
    private function rollbackLabel(): string { return "\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}"; }
    private function enabledLabel(): string { return "\u{0645}\u{0641}\u{0639}\u{0644}"; }
    private function disabledLabel(): string { return "\u{0645}\u{0639}\u{0637}\u{0644}"; }
}

