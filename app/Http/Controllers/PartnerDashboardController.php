<?php

namespace App\Http\Controllers;

use App\Models\PlatformRecord;
use App\Support\PartnerAnalytics;
use App\Support\PartnerApps;
use App\Support\PartnerChannels;
use App\Support\PartnerCustomers;
use App\Support\PartnerDashboardSummary;
use App\Support\PartnerMarketing;
use App\Support\PartnerOrders;
use App\Support\PartnerProducts;
use App\Support\PartnerSettings;
use App\Support\PartnerSettingsSuite;
use App\Support\PartnerServices;
use App\Support\PartnerSolveAi;
use App\Support\PartnerSmartInsights;
use App\Support\PartnerStorefront;
use App\Support\PartnerTenantStore;
use App\Support\PartnerThemeIntelligence;
use App\Support\PartnerWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PartnerDashboardController extends Controller
{
    public function dashboard(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.dashboard');
        $payload['dashboard'] = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);
        $payload['smart'] = PartnerSmartInsights::forPartner($payload['partner'], $request);

        return view('partner.dashboard', $payload);
    }

    public function dashboardSummaryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.dashboard.summary');

        return response()->json(PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request));
    }

    public function dashboardChartsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.dashboard.charts');
        $summary = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'period' => $summary['period'],
            'charts' => $summary['charts'],
        ]);
    }

    public function dashboardLatestOrdersApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.dashboard.latest-orders');
        $summary = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'latestOrders' => $summary['latestOrders'],
        ]);
    }

    public function dashboardAlertsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.dashboard.alerts');
        $summary = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'alerts' => $summary['alerts'],
        ]);
    }

    public function dashboardSmartApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.dashboard.smart');

        return response()->json(PartnerSmartInsights::forPartner($payload['partner'], $request));
    }

    public function aiAssistantApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.ai.assistant');
        $data = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:800'],
        ]);

        return response()->json(PartnerSmartInsights::assistant($payload['partner'], $data, $payload['partnerUser']));
    }

    public function storeStatusApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.store.status');
        $summary = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'name' => $payload['partner']['name'],
            'status' => $payload['partner']['status'] ?? 'نشط',
            'plan' => $payload['partner']['plan'] ?? 'Starter',
            'subscription' => $summary['subscription'],
            'setupProgress' => $summary['setupProgress'],
            'generated_at' => $summary['meta']['generated_at'],
        ]);
    }

    public function exportDashboardSummary(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.dashboard.export');
        $summary = PartnerDashboardSummary::forPartner($payload['partner'], $payload['partnerUser'], $request);
        $lines = ["metric,value,hint,store_id,period_days"];

        foreach ($summary['kpis'] as $kpi) {
            $lines[] = implode(',', array_map(fn (mixed $value) => '"' . str_replace('"', '""', (string) $value) . '"', [
                $kpi['label'],
                $kpi['value'],
                $kpi['hint'],
                $payload['partner']['store_id'],
                $summary['period']['days'],
            ]));
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=dashboard-summary-' . $payload['partner']['store_id'] . '-' . now()->format('Ymd-His') . '.csv',
        ]);
    }

    public function activities(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.activities');
        $payload['activeSection'] = 'dashboard';
        $payload['activePage'] = 'activities';
        $payload['activityPage'] = PartnerDashboardSummary::activitiesForPartner($payload['partner'], $request);

        $this->rememberRecent($request, 'آخر النشاطات', route('partner.activities', $request->query()));

        return view('partner.dashboard.activities', $payload);
    }

    public function activitiesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.activities');

        return response()->json(PartnerDashboardSummary::activitiesForPartner($payload['partner'], $request));
    }

    public function notifications(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.notifications');
        $payload['activeSection'] = 'dashboard';
        $payload['activePage'] = 'notifications';
        $payload['notificationsPage'] = PartnerDashboardSummary::notificationsForPartner($payload['partner'], $request);

        $this->rememberRecent($request, 'الإشعارات', route('partner.notifications', $request->query()));

        return view('partner.dashboard.notifications', $payload);
    }

    public function notificationsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.notifications');

        return response()->json(PartnerDashboardSummary::notificationsForPartner($payload['partner'], $request));
    }

    public function orders(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders');
        $payload['activeSection'] = 'orders';
        $payload['activePage'] = 'all';
        $payload['ordersPage'] = PartnerOrders::list($payload['partner'], $request);

        return view('partner.orders.index', $payload);
    }

    public function orderShow(Request $request, string $order): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders.show');
        $payload['activeSection'] = 'orders';
        $payload['activePage'] = 'all';
        $payload['order'] = PartnerOrders::findForStore($payload['partner'], $order);

        return view('partner.orders.show', $payload);
    }

    public function manualOrder(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders.manual');
        $payload['activeSection'] = 'orders';
        $payload['activePage'] = 'manual';
        $payload['orderProducts'] = collect(PartnerProducts::list($payload['partner'], $request)['products'])
            ->map(fn (array $product) => [
                'id' => $product['id'],
                'name' => $product['name'] ?? $product['product'] ?? $product['id'],
                'sku' => $product['sku'] ?? $product['id'],
                'price' => (float) preg_replace('/[^\d.]/', '', (string) ($product['price'] ?? 0)),
                'stock' => (int) ($product['stock'] ?? 0),
                'image' => $product['image'] ?? null,
                'status' => $product['status'] ?? '',
            ])
            ->values()
            ->all();

        return view('partner.orders.manual', $payload);
    }

    public function storeManualOrder(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.manual.store');
        $validated = $this->validateManualOrder($request);

        $order = PartnerOrders::createManual($payload['partner'], $validated, $payload['partnerUser']);

        return redirect()->route('partner.orders.show', ['order' => $order['id']])->with('status', 'تم إنشاء الطلب اليدوي بنجاح.');
    }

    public function abandonedCarts(Request $request): View
    {
        return $this->ordersRelatedView($request, 'abandoned-carts', 'abandoned_carts', 'السلات المتروكة');
    }

    public function returns(Request $request): View
    {
        return $this->ordersRelatedView($request, 'returns', 'returns', 'المرتجعات');
    }

    public function shipments(Request $request): View
    {
        return $this->ordersRelatedView($request, 'shipments', 'shipments', 'الشحنات');
    }

    public function remindAbandonedCart(Request $request, string $cart): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.abandoned-carts.remind');
        PartnerOrders::remindCart($payload['partner'], $cart, $payload['partnerUser']);

        return back()->with('status', 'تم إرسال تذكير السلة وإنشاء كوبون استرجاع.');
    }

    public function convertAbandonedCart(Request $request, string $cart): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.abandoned-carts.convert');
        $order = PartnerOrders::convertCartToOrder($payload['partner'], $cart, $payload['partnerUser']);

        return redirect()->route('partner.orders.show', ['order' => $order['id']])->with('status', 'تم تحويل السلة إلى طلب.');
    }

    public function updateReturnStatus(Request $request, string $return): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.returns.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);
        PartnerOrders::updateRelatedStatus($payload['partner'], 'returns', $return, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة المرتجع.');
    }

    public function updateShipmentStatus(Request $request, string $shipment): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.shipments.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);
        PartnerOrders::updateRelatedStatus($payload['partner'], 'shipments', $shipment, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة الشحنة.');
    }

    public function updateOrderStatus(Request $request, string $order): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.status');
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerOrders::ORDER_STATUSES))],
        ]);

        PartnerOrders::updateStatus($payload['partner'], $order, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة الطلب.');
    }

    public function addOrderNote(Request $request, string $order): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.notes');
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        PartnerOrders::addNote($payload['partner'], $order, $validated['note'], $payload['partnerUser']);

        return back()->with('status', 'تمت إضافة الملاحظة الداخلية.');
    }

    public function orderInvoice(Request $request, string $order): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders.invoice');
        $payload['order'] = PartnerOrders::findForStore($payload['partner'], $order);

        return view('partner.orders.invoice', $payload);
    }

    public function orderShippingLabel(Request $request, string $order): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders.shipping-label');
        $payload['order'] = PartnerOrders::findForStore($payload['partner'], $order);

        return view('partner.orders.shipping-label', $payload);
    }

    public function ordersApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders');

        return response()->json(PartnerOrders::list($payload['partner'], $request));
    }

    public function orderShowApi(Request $request, string $order): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.show');

        return response()->json(PartnerOrders::findForStore($payload['partner'], $order));
    }

    public function storeManualOrderApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.manual');
        $order = PartnerOrders::createManual($payload['partner'], $this->validateManualOrder($request), $payload['partnerUser']);

        return response()->json($order, 201);
    }

    public function updateOrderStatusApi(Request $request, string $order): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.status');
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerOrders::ORDER_STATUSES))],
        ]);

        return response()->json(PartnerOrders::updateStatus($payload['partner'], $order, $validated['status'], $payload['partnerUser']));
    }

    public function addOrderNoteApi(Request $request, string $order): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.notes');
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        return response()->json(PartnerOrders::addNote($payload['partner'], $order, $validated['note'], $payload['partnerUser']));
    }

    public function orderTimelineApi(Request $request, string $order): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.timeline');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'order_id' => $order,
            'timeline' => PartnerOrders::timeline($payload['partner'], $order),
        ]);
    }

    public function exportOrders(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.orders.export');

        return PartnerOrders::exportCsv($payload['partner'], $request);
    }

    public function exportOrdersApi(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.api.orders.export');

        return PartnerOrders::exportCsv($payload['partner'], $request);
    }

    public function bulkOrders(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.orders.bulk');
        $validated = $request->validate([
            'order_ids' => ['required', 'array'],
            'order_ids.*' => ['required', 'string', 'max:120'],
            'bulk_status' => ['required', 'in:' . implode(',', array_keys(PartnerOrders::ORDER_STATUSES))],
        ]);

        foreach ($validated['order_ids'] as $orderId) {
            PartnerOrders::updateStatus($payload['partner'], $orderId, $validated['bulk_status'], $payload['partnerUser']);
        }

        return back()->with('status', 'تم تطبيق الإجراء الجماعي على الطلبات المحددة.');
    }

    public function abandonedCartsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.abandoned-carts');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerOrders::relatedRows($payload['partner'], 'abandoned_carts'),
        ]);
    }

    public function remindAbandonedCartApi(Request $request, string $cart): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.abandoned-carts.remind');

        return response()->json(PartnerOrders::remindCart($payload['partner'], $cart, $payload['partnerUser']));
    }

    public function returnsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.returns');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerOrders::relatedRows($payload['partner'], 'returns'),
        ]);
    }

    public function updateReturnStatusApi(Request $request, string $return): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.returns.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);

        return response()->json(PartnerOrders::updateRelatedStatus($payload['partner'], 'returns', $return, $validated['status'], $payload['partnerUser']));
    }

    public function shipmentsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.shipments');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerOrders::relatedRows($payload['partner'], 'shipments'),
        ]);
    }

    public function updateShipmentStatusApi(Request $request, string $shipment): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.shipments.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);

        return response()->json(PartnerOrders::updateRelatedStatus($payload['partner'], 'shipments', $shipment, $validated['status'], $payload['partnerUser']));
    }

    public function products(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.products');
        $payload['activeSection'] = 'products';
        $payload['activePage'] = 'all';
        $payload['productsPage'] = PartnerProducts::list($payload['partner'], $request);

        return view('partner.products.index', $payload);
    }

    public function newProduct(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.products.new');
        $payload['activeSection'] = 'products';
        $payload['activePage'] = 'all';
        $payload['product'] = null;

        return view('partner.products.form', $payload);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.store');
        $validated = $this->validateProduct($request);
        $product = PartnerProducts::create($payload['partner'], $validated, $this->storeProductImage($request), $payload['partnerUser']);

        return redirect()->route('partner.products.edit', ['product' => $product['id']])->with('status', 'تم إنشاء المنتج بنجاح.');
    }

    public function editProduct(Request $request, string $product): View
    {
        $payload = $this->tenantPayload($request, 'partner.products.edit');
        $payload['activeSection'] = 'products';
        $payload['activePage'] = 'all';
        $payload['product'] = PartnerProducts::findForStore($payload['partner'], $product);

        return view('partner.products.form', $payload);
    }

    public function updateProduct(Request $request, string $product): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.update');
        $validated = $this->validateProduct($request);
        PartnerProducts::update($payload['partner'], $product, $validated, $this->storeProductImage($request), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث المنتج.');
    }

    public function deleteProduct(Request $request, string $product): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.delete');
        PartnerProducts::delete($payload['partner'], $product, $payload['partnerUser']);

        return redirect()->route('partner.products')->with('status', 'تم حذف المنتج.');
    }

    public function duplicateProduct(Request $request, string $product): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.duplicate');
        $copy = PartnerProducts::duplicate($payload['partner'], $product, $payload['partnerUser']);

        return redirect()->route('partner.products.edit', ['product' => $copy['id']])->with('status', 'تم تكرار المنتج كمسودة.');
    }

    public function bulkProducts(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.bulk');
        $validated = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(PartnerProducts::PRODUCT_STATUSES))],
            'category' => ['nullable', 'string', 'max:120'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        PartnerProducts::bulk($payload['partner'], $validated['product_ids'], $validated, $payload['partnerUser']);

        return back()->with('status', 'تم تطبيق الإجراء الجماعي على المنتجات المحددة.');
    }

    public function exportProducts(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.products.export');

        return PartnerProducts::exportCsv($payload['partner'], $request);
    }

    public function pauseProduct(Request $request, string $product): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.products.pause');
        PartnerProducts::pause($payload['partner'], $product, $payload['partnerUser']);

        return back()->with('status', 'تم إيقاف المنتج.');
    }

    public function productCategories(Request $request): View
    {
        return $this->productsRelatedView($request, 'categories', 'product_categories', 'التصنيفات');
    }

    public function productInventory(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.products.inventory');
        $payload['activeSection'] = 'products';
        $payload['activePage'] = 'stock';
        $payload['title'] = 'المخزون';
        $payload['rows'] = PartnerProducts::inventory($payload['partner'], $request);
        $payload['section'] = 'inventory';

        return view('partner.products.related', $payload);
    }

    public function productOptions(Request $request): View
    {
        return $this->productsRelatedView($request, 'options-library', 'product_options', 'مكتبة الخيارات');
    }

    public function productFilters(Request $request): View
    {
        return $this->productsRelatedView($request, 'filters', 'product_filters', 'معايير التصفية');
    }

    public function productCustomFields(Request $request): View
    {
        return $this->productsRelatedView($request, 'custom-fields', 'product_custom_fields', 'الحقول المخصصة');
    }

    public function productsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products');

        return response()->json(PartnerProducts::list($payload['partner'], $request));
    }

    public function productShowApi(Request $request, string $product): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.show');

        return response()->json(PartnerProducts::findForStore($payload['partner'], $product));
    }

    public function storeProductApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.store');
        $product = PartnerProducts::create($payload['partner'], $this->validateProduct($request), $this->storeProductImage($request), $payload['partnerUser']);

        return response()->json($product, 201);
    }

    public function updateProductApi(Request $request, string $product): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.update');

        return response()->json(PartnerProducts::update($payload['partner'], $product, $this->validateProduct($request), $this->storeProductImage($request), $payload['partnerUser']));
    }

    public function deleteProductApi(Request $request, string $product): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.delete');
        $result = PartnerProducts::delete($payload['partner'], $product, $payload['partnerUser']);

        return response()->json(['deleted' => $result === [], 'product' => $result]);
    }

    public function duplicateProductApi(Request $request, string $product): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.duplicate');

        return response()->json(PartnerProducts::duplicate($payload['partner'], $product, $payload['partnerUser']), 201);
    }

    public function bulkProductsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.bulk');
        $validated = $request->validate([
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['required', 'string', 'max:120'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(PartnerProducts::PRODUCT_STATUSES))],
            'category' => ['nullable', 'string', 'max:120'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json([
            'updated' => PartnerProducts::bulk($payload['partner'], $validated['product_ids'], $validated, $payload['partnerUser']),
        ]);
    }

    public function exportProductsApi(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.export');

        return PartnerProducts::exportCsv($payload['partner'], $request);
    }

    public function importProductsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.import');
        $validated = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.name' => ['nullable', 'string', 'max:180'],
            'rows.*.sku' => ['nullable', 'string', 'max:80'],
            'rows.*.price' => ['nullable', 'numeric', 'min:0'],
            'rows.*.stock' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(['created' => PartnerProducts::importRows($payload['partner'], $validated['rows'], $payload['partnerUser'])], 201);
    }

    public function productMediaApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.media');
        $validated = $request->validate([
            'product_id' => ['nullable', 'string', 'max:120'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_primary' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $path = $this->storeProductImage($request);

        return response()->json(PartnerProducts::addMedia($payload['partner'], $validated['product_id'] ?? null, (string) $path, [
            'is_primary' => (bool) ($validated['is_primary'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'mime' => $request->file('image')?->getMimeType(),
            'size' => $request->file('image')?->getSize(),
        ], $payload['partnerUser']), 201);
    }

    public function deleteProductMediaApi(Request $request, string $media): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.media.delete');
        PartnerProducts::deleteMedia($payload['partner'], $media, $payload['partnerUser']);

        return response()->json(['deleted' => true]);
    }

    public function productRelatedApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.' . $section);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerProducts::relatedRows($payload['partner'], $this->productSectionName($section)),
        ]);
    }

    public function storeProductRelatedApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.' . $section . '.store');

        return response()->json(PartnerProducts::createRelated($payload['partner'], $this->productSectionName($section), $this->validateProductRelated($request, $section), $payload['partnerUser']), 201);
    }

    public function updateProductRelatedApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.' . $section . '.update');

        return response()->json(PartnerProducts::updateRelated($payload['partner'], $this->productSectionName($section), $record, $this->validateProductRelated($request, $section), $payload['partnerUser']));
    }

    public function deleteProductRelatedApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.products.' . $section . '.delete');
        PartnerProducts::deleteRelated($payload['partner'], $this->productSectionName($section), $record, $payload['partnerUser']);

        return response()->json(['deleted' => true]);
    }

    public function inventoryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.inventory');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerProducts::inventory($payload['partner'], $request),
        ]);
    }

    public function updateInventoryApi(Request $request, string $product): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.inventory.update');
        $validated = $request->validate([
            'stock' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        return response()->json(PartnerProducts::updateInventory($payload['partner'], $product, $validated['stock'], $validated['reason'], $payload['partnerUser']));
    }

    public function inventoryLogsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.inventory.logs');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'rows' => PartnerProducts::inventoryLogs($payload['partner']),
        ]);
    }

    public function customers(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.customers');
        $payload['activeSection'] = 'customers';
        $payload['activePage'] = 'all';
        $payload['customersPage'] = PartnerCustomers::list($payload['partner'], $request);

        return view('partner.customers.index', $payload);

        return $this->resourceView($request, 'customers', 'العملاء', 'قائمة عملاء المتجر وسجل الإنفاق.');
    }

    public function customerShow(Request $request, string $customer): View
    {
        $payload = $this->tenantPayload($request, 'partner.customers.show');
        $payload['activeSection'] = 'customers';
        $payload['activePage'] = 'all';
        $payload['customer'] = PartnerCustomers::findForStore($payload['partner'], $customer);

        return view('partner.customers.show', $payload);
    }

    public function updateCustomer(Request $request, string $customer): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.update');
        PartnerCustomers::update($payload['partner'], $customer, $this->validateCustomer($request), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث بيانات العميل.');
    }

    public function addCustomerNote(Request $request, string $customer): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.notes');
        $validated = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        PartnerCustomers::addNote($payload['partner'], $customer, $validated['note'], $payload['partnerUser']);

        return back()->with('status', 'تمت إضافة الملاحظة الداخلية.');
    }

    public function addCustomerTags(Request $request, string $customer): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.tags');
        $validated = $request->validate(['tags' => ['required', 'string', 'max:300']]);
        PartnerCustomers::addTags($payload['partner'], $customer, $validated['tags'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث وسوم العميل.');
    }

    public function exportCustomers(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.customers.export');

        return PartnerCustomers::exportCsv($payload['partner'], $request);
    }

    public function customerGroups(Request $request): View
    {
        return $this->customersRelatedView($request, 'groups', 'customer_groups', 'مجموعات العملاء');
    }

    public function customerReviews(Request $request): View
    {
        return $this->customersRelatedView($request, 'reviews', 'customer_reviews', 'التقييمات');
    }

    public function customerQuestions(Request $request): View
    {
        return $this->customersRelatedView($request, 'questions', 'customer_questions', 'الأسئلة');
    }

    public function backInStock(Request $request): View
    {
        return $this->customersRelatedView($request, 'stock-notifications', 'back_in_stock_alerts', 'إشعارات توفر المخزون');
    }

    public function storeCustomerGroup(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.groups.store');
        PartnerCustomers::createGroup($payload['partner'], $this->validateCustomerGroup($request), $payload['partnerUser']);

        return back()->with('status', 'تم إنشاء مجموعة العملاء.');
    }

    public function updateCustomerGroup(Request $request, string $group): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.groups.update');
        PartnerCustomers::updateGroup($payload['partner'], $group, $this->validateCustomerGroup($request), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث مجموعة العملاء.');
    }

    public function deleteCustomerGroup(Request $request, string $group): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.groups.delete');
        PartnerCustomers::deleteGroup($payload['partner'], $group, $payload['partnerUser']);

        return back()->with('status', 'تم حذف مجموعة العملاء.');
    }

    public function updateReviewStatus(Request $request, string $review): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.reviews.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerCustomers::REVIEW_STATUSES))]]);
        PartnerCustomers::updateReviewStatus($payload['partner'], $review, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة التقييم.');
    }

    public function replyReview(Request $request, string $review): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.reviews.reply');
        $validated = $request->validate(['reply' => ['required', 'string', 'max:1000']]);
        PartnerCustomers::replyReview($payload['partner'], $review, $validated['reply'], $payload['partnerUser']);

        return back()->with('status', 'تم إرسال الرد على التقييم.');
    }

    public function replyQuestion(Request $request, string $question): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.questions.reply');
        $validated = $request->validate(['reply' => ['required', 'string', 'max:1000']]);
        PartnerCustomers::replyQuestion($payload['partner'], $question, $validated['reply'], $payload['partnerUser']);

        return back()->with('status', 'تم إرسال الرد على السؤال.');
    }

    public function updateQuestionStatus(Request $request, string $question): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.questions.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerCustomers::QUESTION_STATUSES))]]);
        PartnerCustomers::updateQuestionStatus($payload['partner'], $question, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة السؤال.');
    }

    public function notifyBackInStock(Request $request, string $alert): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.customers.back-in-stock.notify');
        PartnerCustomers::notifyBackInStock($payload['partner'], $alert, $payload['partnerUser']);

        return back()->with('status', 'تم إرسال إشعار توفر المخزون.');
    }

    public function customersApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers');

        return response()->json(PartnerCustomers::list($payload['partner'], $request));
    }

    public function customerShowApi(Request $request, string $customer): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers.show');

        return response()->json(PartnerCustomers::findForStore($payload['partner'], $customer));
    }

    public function updateCustomerApi(Request $request, string $customer): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers.update');

        return response()->json(PartnerCustomers::update($payload['partner'], $customer, $this->validateCustomer($request), $payload['partnerUser']));
    }

    public function addCustomerNoteApi(Request $request, string $customer): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers.notes');
        $validated = $request->validate(['note' => ['required', 'string', 'max:1000']]);

        return response()->json(PartnerCustomers::addNote($payload['partner'], $customer, $validated['note'], $payload['partnerUser']));
    }

    public function addCustomerTagsApi(Request $request, string $customer): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers.tags');
        $validated = $request->validate(['tags' => ['required', 'string', 'max:300']]);

        return response()->json(PartnerCustomers::addTags($payload['partner'], $customer, $validated['tags'], $payload['partnerUser']));
    }

    public function exportCustomersApi(Request $request): Response
    {
        $payload = $this->tenantPayload($request, 'partner.api.customers.export');

        return PartnerCustomers::exportCsv($payload['partner'], $request);
    }

    public function customerGroupsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customer-groups');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'rows' => PartnerCustomers::relatedRows($payload['partner'], 'customer_groups')]);
    }

    public function storeCustomerGroupApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customer-groups.store');

        return response()->json(PartnerCustomers::createGroup($payload['partner'], $this->validateCustomerGroup($request), $payload['partnerUser']), 201);
    }

    public function updateCustomerGroupApi(Request $request, string $group): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customer-groups.update');

        return response()->json(PartnerCustomers::updateGroup($payload['partner'], $group, $this->validateCustomerGroup($request), $payload['partnerUser']));
    }

    public function deleteCustomerGroupApi(Request $request, string $group): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.customer-groups.delete');
        PartnerCustomers::deleteGroup($payload['partner'], $group, $payload['partnerUser']);

        return response()->json(['deleted' => true]);
    }

    public function reviewsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.reviews');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'rows' => PartnerCustomers::relatedRows($payload['partner'], 'customer_reviews')]);
    }

    public function updateReviewStatusApi(Request $request, string $review): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.reviews.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerCustomers::REVIEW_STATUSES))]]);

        return response()->json(PartnerCustomers::updateReviewStatus($payload['partner'], $review, $validated['status'], $payload['partnerUser']));
    }

    public function replyReviewApi(Request $request, string $review): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.reviews.reply');
        $validated = $request->validate(['reply' => ['required', 'string', 'max:1000']]);

        return response()->json(PartnerCustomers::replyReview($payload['partner'], $review, $validated['reply'], $payload['partnerUser']));
    }

    public function questionsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.questions');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'rows' => PartnerCustomers::relatedRows($payload['partner'], 'customer_questions')]);
    }

    public function replyQuestionApi(Request $request, string $question): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.questions.reply');
        $validated = $request->validate(['reply' => ['required', 'string', 'max:1000']]);

        return response()->json(PartnerCustomers::replyQuestion($payload['partner'], $question, $validated['reply'], $payload['partnerUser']));
    }

    public function updateQuestionStatusApi(Request $request, string $question): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.questions.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerCustomers::QUESTION_STATUSES))]]);

        return response()->json(PartnerCustomers::updateQuestionStatus($payload['partner'], $question, $validated['status'], $payload['partnerUser']));
    }

    public function backInStockApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.back-in-stock');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'rows' => PartnerCustomers::relatedRows($payload['partner'], 'back_in_stock_alerts')]);
    }

    public function notifyBackInStockApi(Request $request, string $alert): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.back-in-stock.notify');

        return response()->json(PartnerCustomers::notifyBackInStock($payload['partner'], $alert, $payload['partnerUser']));
    }

    public function payments(Request $request): View
    {
        return $this->resourceView($request, 'payments', 'المدفوعات', 'عمليات الدفع والتسويات الخاصة بالمتجر.');
    }

    public function marketing(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.marketing');
        $payload['activeSection'] = 'marketing';
        $payload['activePage'] = 'overview';
        $payload['marketing'] = PartnerMarketing::summary($payload['partner']);

        return view('partner.marketing.index', $payload);
    }

    public function marketingCoupons(Request $request): View
    {
        return $this->marketingRelatedView($request, 'coupons', 'marketing_coupons', 'الكوبونات والخصومات');
    }

    public function marketingCampaigns(Request $request): View
    {
        return $this->marketingRelatedView($request, 'campaigns', 'marketing_campaigns', 'الحملات التسويقية');
    }

    public function marketingBundles(Request $request): View
    {
        return $this->marketingRelatedView($request, 'bundles', 'marketing_bundles', 'الحزم التسويقية');
    }

    public function marketingLoyalty(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.loyalty');
        $payload['activeSection'] = 'marketing';
        $payload['activePage'] = 'loyalty';
        $payload['title'] = 'برنامج الولاء';
        $payload['loyalty'] = PartnerMarketing::loyalty($payload['partner']);

        return view('partner.marketing.loyalty', $payload);
    }

    public function marketingAffiliate(Request $request): View
    {
        return $this->marketingRelatedView($request, 'affiliate', 'marketing_affiliate_links', 'التسويق بالعمولة');
    }

    public function marketingAds(Request $request): View
    {
        return $this->marketingRelatedView($request, 'ads', 'marketing_ads_integrations', 'الإعلانات والتتبع');
    }

    public function marketingAbandonedCarts(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.abandoned-carts');
        $payload['activeSection'] = 'marketing';
        $payload['activePage'] = 'abandoned-carts';
        $payload['title'] = 'السلات المتروكة';
        $payload['rows'] = PartnerOrders::relatedRows($payload['partner'], 'abandoned_carts');

        return view('partner.marketing.abandoned-carts', $payload);
    }

    public function storeMarketingRecord(Request $request, string $section): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.' . $section . '.store');
        PartnerMarketing::create($payload['partner'], $this->marketingSectionName($section), $this->validateMarketing($request, $section), $payload['partnerUser']);

        return back()->with('status', 'تم إنشاء السجل التسويقي.');
    }

    public function updateMarketingRecord(Request $request, string $section, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.' . $section . '.update');
        PartnerMarketing::update($payload['partner'], $this->marketingSectionName($section), $record, $this->validateMarketing($request, $section), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث السجل التسويقي.');
    }

    public function deleteMarketingRecord(Request $request, string $section, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.' . $section . '.delete');
        PartnerMarketing::delete($payload['partner'], $this->marketingSectionName($section), $record, $payload['partnerUser']);

        return back()->with('status', 'تم حذف السجل التسويقي.');
    }

    public function updateMarketingStatus(Request $request, string $section, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.' . $section . '.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerMarketing::STATUSES))]]);
        PartnerMarketing::updateStatus($payload['partner'], $this->marketingSectionName($section), $record, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث الحالة.');
    }

    public function createAbandonedCartCoupon(Request $request, string $cart): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.abandoned-carts.coupon');
        PartnerMarketing::createAbandonedCartCoupon($payload['partner'], $cart, $payload['partnerUser']);

        return back()->with('status', 'تم إنشاء كوبون استرجاع للسلة.');
    }

    public function storefront(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'overview';
        $payload['storefront'] = PartnerStorefront::summary($payload['partner']);

        $this->rememberRecent($request, 'المتجر الإلكتروني', route('partner.storefront'));

        return view('partner.storefront.index', $payload);
    }

    public function storefrontThemes(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'themes';
        $payload['title'] = 'القوالب';
        $payload['title'] = 'القوالب الذكية';
        $payload['storefrontPage'] = PartnerStorefront::themes($payload['partner'], $request);
        $payload['themeMarketplace'] = PartnerStorefront::themeMarketplace($payload['partner'], $request);
        $payload['themeIntelligence'] = PartnerThemeIntelligence::overview($payload['partner'], $request);

        return view('partner.storefront.themes', $payload);
    }

    public function storefrontCustomize(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.customize');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'customize';
        $payload['title'] = 'تعديل واجهة المتجر';
        $payload['storefront'] = PartnerStorefront::summary($payload['partner']);
        $payload['storefrontPage'] = PartnerStorefront::themes($payload['partner'], $request);
        $payload['settings'] = PartnerStorefront::storeSettings($payload['partner']);
        $payload['pages'] = PartnerStorefront::list($payload['partner'], 'storefront_pages', $request);
        $payload['banners'] = PartnerStorefront::list($payload['partner'], 'storefront_banners', $request);
        $payload['navigation'] = PartnerStorefront::navigation($payload['partner']);
        $payload['domain'] = PartnerStorefront::domain($payload['partner']);
        $payload['seo'] = PartnerStorefront::seo($payload['partner']);
        $payload['builder'] = PartnerStorefront::builder($payload['partner']);
        $payload['builderSections'] = PartnerStorefront::builderSections($payload['partner'], $request);

        $this->rememberRecent($request, 'تعديل واجهة المتجر', route('partner.storefront.customize'));

        return view('partner.storefront.customize', $payload);
    }

    public function storefrontBuilder(Request $request): View
    {
        return $this->storefrontCustomize($request);
    }

    public function updateStorefrontBuilder(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.builder.update');
        PartnerStorefront::updateBuilder($payload['partner'], $this->validateStorefrontBuilder($request), $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… Ø­ÙØ¸ Ù…Ø³ÙˆØ¯Ø© Ù…Ø­Ø±Ø± Ø§Ù„ÙˆØ§Ø¬Ù‡Ø©.');
    }

    public function publishStorefrontBuilder(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.builder.publish');
        PartnerStorefront::publishBuilder($payload['partner'], $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… Ù†Ø´Ø± ØªØºÙŠÙŠØ±Ø§Øª Ø§Ù„ÙˆØ§Ø¬Ù‡Ø© ÙˆØ±Ø¨Ø·Ù‡Ø§ Ø¨Ø§Ù„Ù…ØªØ¬Ø±.');
    }

    public function rollbackStorefrontBuilder(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.builder.rollback');
        PartnerStorefront::rollbackBuilder($payload['partner'], $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… Ø§Ù„Ø±Ø¬ÙˆØ¹ Ù„Ø¢Ø®Ø± Ù†Ø³Ø®Ø© Ù…Ù†Ø´ÙˆØ±Ø©.');
    }

    public function storeStorefrontSection(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.sections.store');
        PartnerStorefront::createBuilderSection($payload['partner'], $this->validateStorefrontSection($request), $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… Ø¥Ø¶Ø§ÙØ© Ù‚Ø³Ù… Ø¬Ø¯ÙŠØ¯ Ù„Ù„ÙˆØ§Ø¬Ù‡Ø©.');
    }

    public function updateStorefrontSection(Request $request, string $sectionRecord): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.sections.update');
        PartnerStorefront::updateBuilderSection($payload['partner'], $sectionRecord, $this->validateStorefrontSection($request), $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ù‚Ø³Ù….');
    }

    public function reorderStorefrontSections(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.sections.reorder');
        PartnerStorefront::reorderBuilderSections($payload['partner'], $this->validateStorefrontSectionOrder($request)['order'], $payload['partnerUser']);

        return back()->with('status', 'تم حفظ ترتيب أقسام الواجهة.');
    }

    public function deleteStorefrontSection(Request $request, string $sectionRecord): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.sections.delete');
        PartnerStorefront::deleteBuilderSection($payload['partner'], $sectionRecord, $payload['partnerUser']);

        return back()->with('status', 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ù‚Ø³Ù… Ù…Ù† Ø§Ù„Ù…Ø³ÙˆØ¯Ø©.');
    }

    public function storefrontPages(Request $request): View
    {
        return $this->storefrontRelatedView($request, 'pages', 'storefront_pages', 'الصفحات');
    }

    public function storefrontBanners(Request $request): View
    {
        return $this->storefrontRelatedView($request, 'banners', 'storefront_banners', 'البنرات والعروض');
    }

    public function storefrontNavigation(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.navigation');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'navigation';
        $payload['title'] = 'القوائم والتنقل';
        $payload['navigation'] = PartnerStorefront::navigation($payload['partner']);

        return view('partner.storefront.navigation', $payload);
    }

    public function storefrontDomain(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.domain');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'domain';
        $payload['title'] = 'الدومين';
        $payload['domain'] = PartnerStorefront::domain($payload['partner']);

        return view('partner.storefront.domain', $payload);
    }

    public function storefrontSeo(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.seo');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'seo';
        $payload['title'] = 'SEO';
        $payload['seo'] = PartnerStorefront::seo($payload['partner']);

        return view('partner.storefront.seo', $payload);
    }

    public function storefrontSettings(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.settings');
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = 'settings';
        $payload['title'] = 'إعدادات المتجر';
        $payload['settings'] = PartnerStorefront::storeSettings($payload['partner']);

        return view('partner.storefront.settings', $payload);
    }

    public function activateStorefrontTheme(Request $request, string $theme): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.activate');
        PartnerStorefront::activateTheme($payload['partner'], $theme, $payload['partnerUser']);

        return back()->with('status', 'تم تفعيل القالب وربطه بواجهة المتجر.');
    }

    public function customizeStorefrontTheme(Request $request, string $theme): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.customize');
        PartnerStorefront::customizeTheme($payload['partner'], $theme, $this->validateThemeCustomization($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ تخصيصات القالب.');
    }

    public function applyStorefrontThemePreset(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.apply-preset');
        $data = $request->validate([
            'preset_key' => ['required', 'string', 'max:80'],
        ]);

        PartnerThemeIntelligence::applyPreset($payload['partner'], $data['preset_key'], $payload['partnerUser']);

        return back()->with('status', 'تم تطبيق توصية ذكاء القوالب على واجهة المتجر.');
    }

    public function generateStorefrontTheme(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.generate');
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
        ]);

        $generated = PartnerThemeIntelligence::generate($payload['partner'], $data, $payload['partnerUser']);

        return back()->with('status', 'تم توليد تجربة واجهة ذكية: ' . ($generated['name'] ?? 'Solve AI Theme') . '.');
    }

    public function generateStorefrontThemeBanners(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.generate-banners');
        $data = $request->validate([
            'season' => ['nullable', 'string', 'max:80'],
        ]);

        $generated = PartnerThemeIntelligence::generateBanners($payload['partner'], $data, $payload['partnerUser']);

        return back()->with('status', 'تم توليد ' . ($generated['count'] ?? 0) . ' بنرات ذكية قابلة للاستخدام داخل واجهة المتجر.');
    }

    public function installStorefrontTheme(Request $request, string $theme): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.install');
        PartnerStorefront::installTheme($payload['partner'], $theme, $payload['partnerUser']);

        return back()->with('status', 'تم تثبيت القالب كمسودة. يمكنك معاينته ثم نشره عند الجاهزية.');
    }

    public function favoriteStorefrontTheme(Request $request, string $theme): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.favorite');
        PartnerStorefront::favoriteTheme($payload['partner'], $theme, $payload['partnerUser']);

        return back()->with('status', 'تم تحديث مفضلة القالب.');
    }

    public function publishStorefrontTheme(Request $request, string $theme): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.themes.publish');
        PartnerStorefront::publishTheme($payload['partner'], $theme, $payload['partnerUser']);

        return back()->with('status', 'تم نشر القالب وربطه بواجهة المتجر.');
    }

    public function storeStorefrontRecord(Request $request, string $section): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.' . $section . '.store');
        $method = $section === 'pages' ? 'createPage' : 'createBanner';
        $validator = $section === 'pages' ? 'validateStorefrontPage' : 'validateStorefrontBanner';
        PartnerStorefront::$method($payload['partner'], $this->{$validator}($request), $payload['partnerUser']);

        return back()->with('status', 'تم إنشاء السجل بنجاح.');
    }

    public function updateStorefrontRecord(Request $request, string $section, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.' . $section . '.update');
        $method = $section === 'pages' ? 'updatePage' : 'updateBanner';
        $validator = $section === 'pages' ? 'validateStorefrontPage' : 'validateStorefrontBanner';
        PartnerStorefront::$method($payload['partner'], $record, $this->{$validator}($request), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث السجل بنجاح.');
    }

    public function deleteStorefrontRecord(Request $request, string $section, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.' . $section . '.delete');
        $method = $section === 'pages' ? 'deletePage' : 'deleteBanner';
        PartnerStorefront::$method($payload['partner'], $record, $payload['partnerUser']);

        return back()->with('status', 'تم حذف السجل بعد التأكيد.');
    }

    public function reorderStorefrontBanners(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.banners.reorder');
        PartnerStorefront::reorderBanners($payload['partner'], $this->validateBannerReorder($request)['order'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث ترتيب البنرات.');
    }

    public function updateStorefrontNavigation(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.navigation.update');
        PartnerStorefront::updateNavigation($payload['partner'], $this->validateNavigation($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ القوائم.');
    }

    public function connectStorefrontDomain(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.domain.connect');
        PartnerStorefront::connectDomain($payload['partner'], $this->validateDomain($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ الدومين، بانتظار تحقق DNS.');
    }

    public function verifyStorefrontDomain(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.domain.verify');
        PartnerStorefront::verifyDomain($payload['partner'], $payload['partnerUser']);

        return back()->with('status', 'تم التحقق من DNS وتفعيل SSL.');
    }

    public function updateStorefrontDomainStatus(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.domain.status');
        PartnerStorefront::updateDomainStatus($payload['partner'], $request->validate(['active' => ['nullable', 'boolean']]), $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة الدومين.');
    }

    public function updateStorefrontSeo(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.seo.update');
        PartnerStorefront::updateSeo($payload['partner'], $this->validateSeo($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات SEO.');
    }

    public function updateStorefrontSettings(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.settings.update');
        PartnerStorefront::updateStoreSettings($payload['partner'], $this->validateStoreSettings($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات واجهة المتجر.');
    }

    public function storefrontSummaryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.summary');

        return response()->json(PartnerStorefront::summary($payload['partner']));
    }

    public function storefrontConversionApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.conversion');

        return response()->json($this->storefrontConversionReport($payload['partner']));
    }

    public function storefrontBuilderApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.builder');

        return response()->json(PartnerStorefront::builder($payload['partner']));
    }

    public function updateStorefrontBuilderApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.builder.update');

        return response()->json(PartnerStorefront::updateBuilder($payload['partner'], $this->validateStorefrontBuilder($request), $payload['partnerUser']));
    }

    public function publishStorefrontBuilderApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.builder.publish');

        return response()->json(PartnerStorefront::publishBuilder($payload['partner'], $payload['partnerUser']));
    }

    public function rollbackStorefrontBuilderApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.builder.rollback');

        return response()->json(PartnerStorefront::rollbackBuilder($payload['partner'], $payload['partnerUser']));
    }

    public function storefrontSectionsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.sections');

        return response()->json(PartnerStorefront::builderSections($payload['partner'], $request));
    }

    public function storeStorefrontSectionApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.sections.store');

        return response()->json(PartnerStorefront::createBuilderSection($payload['partner'], $this->validateStorefrontSection($request), $payload['partnerUser']), 201);
    }

    public function updateStorefrontSectionApi(Request $request, string $sectionRecord): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.sections.update');

        return response()->json(PartnerStorefront::updateBuilderSection($payload['partner'], $sectionRecord, $this->validateStorefrontSection($request), $payload['partnerUser']));
    }

    public function reorderStorefrontSectionsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.sections.reorder');

        return response()->json(PartnerStorefront::reorderBuilderSections($payload['partner'], $this->validateStorefrontSectionOrder($request)['order'], $payload['partnerUser']));
    }

    public function deleteStorefrontSectionApi(Request $request, string $sectionRecord): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.sections.delete');
        PartnerStorefront::deleteBuilderSection($payload['partner'], $sectionRecord, $payload['partnerUser']);

        return response()->json(['deleted' => true, 'store_id' => $payload['partner']['store_id']]);
    }

    public function uploadStorefrontMediaApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.media.upload');
        $validated = $request->validate([
            'media' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg', 'max:12288'],
            'type' => ['nullable', 'in:image,video,poster,banner,section'],
        ]);

        $file = $request->file('media');
        $mime = (string) $file?->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');
        $type = $validated['type'] ?? null;

        abort_if(in_array($type, ['image', 'poster', 'banner'], true) && $isVideo, 422, 'اختر صورة صالحة لهذا الحقل.');
        abort_if($type === 'video' && ! $isVideo, 422, 'اختر ملف فيديو صالح.');

        $path = $this->storeStorefrontMediaFile($request, $payload['partner']);

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'path' => $path,
            'url' => asset((string) $path),
            'mime' => $mime,
            'size' => $file?->getSize(),
            'type' => $isVideo ? 'video' : 'image',
        ], 201);
    }

    public function themesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes');

        return response()->json(PartnerStorefront::themes($payload['partner'], $request));
    }

    public function themeCategoriesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.categories');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'categories' => PartnerStorefront::themeCategories(),
        ]);
    }

    public function themeShowApi(Request $request, string $theme): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.show');

        return response()->json(PartnerStorefront::findTheme($payload['partner'], $theme));
    }

    public function installThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.install');
        $data = $request->validate(['theme_id' => ['required', 'string', 'max:120']]);

        return response()->json(PartnerStorefront::installTheme($payload['partner'], $data['theme_id'], $payload['partnerUser']), 201);
    }

    public function previewThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.preview');
        $data = $request->validate(['theme_id' => ['required', 'string', 'max:120'], 'device' => ['nullable', 'string', 'max:40']]);

        return response()->json(PartnerStorefront::previewTheme($payload['partner'], $data['theme_id'], $request));
    }

    public function favoriteThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.favorite');
        $data = $request->validate(['theme_id' => ['required', 'string', 'max:120']]);

        return response()->json(PartnerStorefront::favoriteTheme($payload['partner'], $data['theme_id'], $payload['partnerUser']));
    }

    public function publishThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.publish');
        $data = $request->validate(['theme_id' => ['required', 'string', 'max:120']]);

        return response()->json(PartnerStorefront::publishTheme($payload['partner'], $data['theme_id'], $payload['partnerUser']));
    }

    public function rollbackThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.rollback');

        return response()->json(PartnerStorefront::rollbackTheme($payload['partner'], $payload['partnerUser']));
    }

    public function publicThemeMarketplaceApi(Request $request): JsonResponse
    {
        return response()->json(PartnerStorefront::publicThemeMarketplace($request));
    }

    public function storeThemeReviewApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme_id' => ['required', 'string', 'max:120'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json(PartnerStorefront::createThemeReview($data), 201);
    }

    public function activateThemeApi(Request $request, string $theme): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.activate');

        return response()->json(PartnerStorefront::activateTheme($payload['partner'], $theme, $payload['partnerUser']));
    }

    public function customizeThemeApi(Request $request, string $theme): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.customize');

        return response()->json(PartnerStorefront::customizeTheme($payload['partner'], $theme, $this->validateThemeCustomization($request), $payload['partnerUser']));
    }

    public function customizeActiveThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.customize-active');
        $themes = collect(PartnerStorefront::themes($payload['partner'], $request)['rows'] ?? []);
        $theme = $themes->firstWhere('active', true) ?? $themes->first();
        abort_unless($theme && isset($theme['id']), 404);

        return response()->json(PartnerStorefront::customizeTheme($payload['partner'], (string) $theme['id'], $this->validateThemeCustomization($request), $payload['partnerUser']));
    }

    public function themeSettingsApi(Request $request, string $theme): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.settings');

        return response()->json(PartnerStorefront::themeSettings($payload['partner'], $theme));
    }

    public function themeIntelligenceApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.intelligence');

        return response()->json(PartnerThemeIntelligence::overview($payload['partner'], $request));
    }

    public function generateThemeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.generate');
        $data = $request->validate([
            'prompt' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json(PartnerThemeIntelligence::generate($payload['partner'], $data, $payload['partnerUser']), 201);
    }

    public function applyThemePresetApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.apply-preset');
        $data = $request->validate([
            'preset_key' => ['required', 'string', 'max:80'],
        ]);

        return response()->json(PartnerThemeIntelligence::applyPreset($payload['partner'], $data['preset_key'], $payload['partnerUser']));
    }

    public function themeAutoStyleApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.auto-style');

        return response()->json(PartnerThemeIntelligence::autoStyle($payload['partner']));
    }

    public function generateThemeBannersApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.generate-banners');
        $data = $request->validate([
            'season' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(PartnerThemeIntelligence::generateBanners($payload['partner'], $data, $payload['partnerUser']), 201);
    }

    public function themeAnalyticsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.analytics');

        return response()->json(PartnerThemeIntelligence::analyticsSnapshot($payload['partner']));
    }

    public function themeRankingApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.themes.ranking');

        return response()->json(PartnerThemeIntelligence::marketplaceRanking($payload['partner']));
    }

    public function storefrontListApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.' . $section);

        return response()->json(PartnerStorefront::list($payload['partner'], $this->storefrontSectionName($section), $request));
    }

    public function storefrontShowApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.' . $section . '.show');

        return response()->json(PartnerStorefront::find($payload['partner'], $this->storefrontSectionName($section), $record));
    }

    public function storeStorefrontApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.' . $section . '.store');
        $method = $section === 'pages' ? 'createPage' : 'createBanner';
        $validator = $section === 'pages' ? 'validateStorefrontPage' : 'validateStorefrontBanner';

        return response()->json(PartnerStorefront::$method($payload['partner'], $this->{$validator}($request), $payload['partnerUser']), 201);
    }

    public function updateStorefrontApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.' . $section . '.update');
        $method = $section === 'pages' ? 'updatePage' : 'updateBanner';
        $validator = $section === 'pages' ? 'validateStorefrontPage' : 'validateStorefrontBanner';

        return response()->json(PartnerStorefront::$method($payload['partner'], $record, $this->{$validator}($request), $payload['partnerUser']));
    }

    public function deleteStorefrontApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.storefront.' . $section . '.delete');
        $method = $section === 'pages' ? 'deletePage' : 'deleteBanner';
        PartnerStorefront::$method($payload['partner'], $record, $payload['partnerUser']);

        return response()->json(['deleted' => true, 'store_id' => $payload['partner']['store_id']]);
    }

    public function reorderBannersApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.banners.reorder');

        return response()->json(PartnerStorefront::reorderBanners($payload['partner'], $this->validateBannerReorder($request)['order'], $payload['partnerUser']));
    }

    public function navigationStorefrontApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.navigation');

        return response()->json(PartnerStorefront::navigation($payload['partner']));
    }

    public function updateNavigationStorefrontApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.navigation.update');

        return response()->json(PartnerStorefront::updateNavigation($payload['partner'], $this->validateNavigation($request), $payload['partnerUser']));
    }

    public function domainApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.domain');

        return response()->json(PartnerStorefront::domain($payload['partner']));
    }

    public function connectDomainApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.domain.connect');

        return response()->json(PartnerStorefront::connectDomain($payload['partner'], $this->validateDomain($request), $payload['partnerUser']), 201);
    }

    public function verifyDomainApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.domain.verify');

        return response()->json(PartnerStorefront::verifyDomain($payload['partner'], $payload['partnerUser']));
    }

    public function updateDomainStatusApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.domain.status');

        return response()->json(PartnerStorefront::updateDomainStatus($payload['partner'], $request->validate(['active' => ['nullable', 'boolean']]), $payload['partnerUser']));
    }

    public function seoApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.seo');

        return response()->json(PartnerStorefront::seo($payload['partner']));
    }

    public function updateSeoApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.seo.update');

        return response()->json(PartnerStorefront::updateSeo($payload['partner'], $this->validateSeo($request), $payload['partnerUser']));
    }

    public function storeSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.store-settings');

        return response()->json(PartnerStorefront::storeSettings($payload['partner']));
    }

    public function updateStoreSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.store-settings.update');

        return response()->json(PartnerStorefront::updateStoreSettings($payload['partner'], $this->validateStoreSettings($request), $payload['partnerUser']));
    }

    private function storefrontConversionReport(array $partner): array
    {
        if (! Schema::hasTable('platform_records')) {
            return ['store_id' => $partner['store_id'], 'events' => [], 'funnel' => [], 'recommendations' => []];
        }

        $events = PlatformRecord::query()
            ->where('section', 'storefront_events')
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload ?? []);

        $carts = PlatformRecord::query()
            ->where('section', 'abandoned_carts')
            ->where('store_id', $partner['store_id'])
            ->where('payload->source', 'storefront')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload ?? []);

        $orders = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->where('payload->source_channel', 'storefront')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload ?? []);

        $counts = $events->groupBy(fn (array $event) => $event['event'] ?? 'unknown')->map->count();
        $views = (int) ($counts['page_view'] ?? 0);
        $productViews = (int) ($counts['product_view'] ?? 0);
        $adds = (int) ($counts['add_to_cart'] ?? 0);
        $checkouts = (int) ($counts['checkout_started'] ?? 0);
        $orderCreated = max((int) ($counts['order_created'] ?? 0), $orders->count());

        return [
            'store_id' => $partner['store_id'],
            'cards' => [
                ['label' => 'زيارات المتجر', 'value' => $views, 'hint' => 'page_view'],
                ['label' => 'مشاهدات المنتجات', 'value' => $productViews, 'hint' => 'product_view'],
                ['label' => 'إضافات للسلة', 'value' => $adds, 'hint' => 'add_to_cart'],
                ['label' => 'طلبات من المتجر', 'value' => $orderCreated, 'hint' => 'storefront orders'],
            ],
            'funnel' => [
                ['step' => 'زيارة', 'count' => $views, 'rate' => '100%'],
                ['step' => 'مشاهدة منتج', 'count' => $productViews, 'rate' => $this->rate($productViews, $views)],
                ['step' => 'إضافة للسلة', 'count' => $adds, 'rate' => $this->rate($adds, max(1, $productViews))],
                ['step' => 'بدء الدفع', 'count' => $checkouts, 'rate' => $this->rate($checkouts, max(1, $adds))],
                ['step' => 'طلب مكتمل', 'count' => $orderCreated, 'rate' => $this->rate($orderCreated, max(1, $checkouts ?: $adds))],
            ],
            'abandoned_carts' => [
                'count' => $carts->count(),
                'value' => $this->formatStorefrontMoney($carts->sum(fn (array $cart) => $this->storefrontMoney($cart['total'] ?? 0))),
                'recovery_message' => 'نسيت منتجاتك في السلة؟ أكمل طلبك الآن واستفد من عروض المتجر.',
            ],
            'top_products' => $events
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->map(fn (Collection $items, string $productId) => ['product_id' => $productId, 'events' => $items->count()])
                ->sortByDesc('events')
                ->take(5)
                ->values()
                ->all(),
            'recommendations' => $this->storefrontGrowthRecommendations($views, $productViews, $adds, $checkouts, $orderCreated, $carts->count()),
        ];
    }

    private function storefrontGrowthRecommendations(int $views, int $productViews, int $adds, int $checkouts, int $orders, int $abandonedCarts): array
    {
        $recommendations = [];

        if ($views > 0 && $productViews < max(1, (int) floor($views * .35))) {
            $recommendations[] = ['priority' => 'high', 'title' => 'ارفع ظهور المنتجات في الصفحة الرئيسية', 'body' => 'مشاهدات المنتجات أقل من المتوقع مقارنة بالزيارات. اجعل المنتجات المميزة والبنرات أوضح.'];
        }

        if ($productViews > 0 && $adds < max(1, (int) floor($productViews * .08))) {
            $recommendations[] = ['priority' => 'high', 'title' => 'حسّن صفحة المنتج', 'body' => 'نسبة الإضافة للسلة منخفضة. أظهر Trust Badges والتقييمات والسعر بوضوح.'];
        }

        if ($adds > 0 && $checkouts < max(1, (int) floor($adds * .45))) {
            $recommendations[] = ['priority' => 'medium', 'title' => 'قلل تردد الدفع', 'body' => 'هناك إضافة للسلة بدون بدء Checkout كافٍ. فعّل رسالة استرجاع السلة أو كوبون قصير.'];
        }

        if ($abandonedCarts > 0) {
            $recommendations[] = ['priority' => 'medium', 'title' => 'شغّل استرجاع السلات', 'body' => 'يوجد ' . $abandonedCarts . ' سلات من واجهة المتجر جاهزة للتذكير.'];
        }

        if ($orders === 0) {
            $recommendations[] = ['priority' => 'high', 'title' => 'اختبر عرضاً تجريبياً', 'body' => 'لا توجد طلبات من المتجر حتى الآن. جرّب عرض شحن مجاني أو كوبون أول طلب.'];
        }

        return $recommendations ?: [
            ['priority' => 'low', 'title' => 'الأداء مستقر', 'body' => 'استمر في متابعة المنتجات الأعلى مشاهدة وأضف Upsell للمنتجات الرائجة.'],
        ];
    }

    private function rate(int $value, int $base): string
    {
        return $base > 0 ? round(($value / $base) * 100, 1) . '%' : '0%';
    }

    private function storefrontMoney(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private function formatStorefrontMoney(float|int $amount): string
    {
        return number_format((float) $amount, 2) . ' ر.س';
    }

    public function services(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.services');
        $payload['activeSection'] = 'services';
        $payload['activePage'] = 'overview';
        $payload['services'] = PartnerServices::summary($payload['partner'], $request);

        $this->rememberRecent($request, 'الخدمات', route('partner.services'));

        return view('partner.services.index', $payload);
    }

    public function serviceLogistics(Request $request): View
    {
        return $this->serviceTypedView($request, 'logistics', 'اللوجستيات');
    }

    public function servicePaymentGateways(Request $request): View
    {
        return $this->serviceTypedView($request, 'payment-gateways', 'بوابات الدفع');
    }

    public function serviceWhatsapp(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.services.whatsapp');
        $payload['activeSection'] = 'services';
        $payload['activePage'] = 'whatsapp';
        $payload['title'] = 'واتساب';
        $payload['whatsapp'] = PartnerServices::whatsapp($payload['partner']);

        return view('partner.services.whatsapp', $payload);
    }

    public function serviceFinancing(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.services.financing');
        $payload['activeSection'] = 'services';
        $payload['activePage'] = 'financing';
        $payload['title'] = 'التمويل';
        $payload['financing'] = PartnerServices::financing($payload['partner']);

        return view('partner.services.financing', $payload);
    }

    public function serviceGrowth(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.services.growth');
        $payload['activeSection'] = 'services';
        $payload['activePage'] = 'growth';
        $payload['title'] = 'النمو';
        $payload['growth'] = PartnerServices::growth($payload['partner']);

        return view('partner.services.growth', $payload);
    }

    public function updateServiceStatus(Request $request, string $service): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerServices::STATUSES))]]);
        PartnerServices::updateServiceStatus($payload['partner'], $service, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة الخدمة.');
    }

    public function testService(Request $request, string $service): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.test');
        $result = PartnerServices::testService($payload['partner'], $service, $payload['partnerUser']);

        return back()->with('status', $result['message']);
    }

    public function updateTypedServiceSettings(Request $request, string $record, string $type): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.' . $type . '.settings');
        PartnerServices::updateTypedSettings($payload['partner'], $type, $record, $this->validateTypedService($request, $type), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات الخدمة.');
    }

    public function testTypedService(Request $request, string $record, string $type): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.' . $type . '.test');
        $result = PartnerServices::testTyped($payload['partner'], $type, $record, $payload['partnerUser']);

        return back()->with('status', $result['message']);
    }

    public function updateWhatsappSettings(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.whatsapp.settings');
        PartnerServices::updateWhatsapp($payload['partner'], $this->validateWhatsapp($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات واتساب.');
    }

    public function testWhatsapp(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.whatsapp.test');
        $result = PartnerServices::testWhatsapp($payload['partner'], $payload['partnerUser']);

        return back()->with('status', $result['message']);
    }

    public function updateFinancingSettings(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.financing.settings');
        PartnerServices::updateFinancing($payload['partner'], $this->validateFinancing($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات التمويل.');
    }

    public function updateFinancingRequestStatus(Request $request, string $record): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.services.financing.requests.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);
        PartnerServices::updateFinancingRequest($payload['partner'], $record, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث طلب التمويل.');
    }

    public function servicesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services');

        return response()->json(PartnerServices::summary($payload['partner'], $request));
    }

    public function serviceShowApi(Request $request, string $service): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.show');

        return response()->json(PartnerServices::service($payload['partner'], $service));
    }

    public function updateServiceStatusApi(Request $request, string $service): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerServices::STATUSES))]]);

        return response()->json(PartnerServices::updateServiceStatus($payload['partner'], $service, $validated['status'], $payload['partnerUser']));
    }

    public function testServiceApi(Request $request, string $service): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.test');

        return response()->json(PartnerServices::testService($payload['partner'], $service, $payload['partnerUser']));
    }

    public function typedServicesApi(Request $request, string $type): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.' . $type);

        return response()->json(PartnerServices::typed($payload['partner'], $type, $request));
    }

    public function updateTypedServiceApi(Request $request, string $record, string $type): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.' . $type . '.settings');

        return response()->json(PartnerServices::updateTypedSettings($payload['partner'], $type, $record, $this->validateTypedService($request, $type), $payload['partnerUser']));
    }

    public function testTypedServiceApi(Request $request, string $record, string $type): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.' . $type . '.test');

        return response()->json(PartnerServices::testTyped($payload['partner'], $type, $record, $payload['partnerUser']));
    }

    public function typedServiceStatusApi(Request $request, string $record, string $type): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.' . $type . '.status');

        return response()->json(PartnerServices::status($payload['partner'], $type, $record));
    }

    public function whatsappApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.whatsapp');

        return response()->json(PartnerServices::whatsapp($payload['partner']));
    }

    public function updateWhatsappApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.whatsapp.settings');

        return response()->json(PartnerServices::updateWhatsapp($payload['partner'], $this->validateWhatsapp($request), $payload['partnerUser']));
    }

    public function testWhatsappApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.whatsapp.test');

        return response()->json(PartnerServices::testWhatsapp($payload['partner'], $payload['partnerUser']));
    }

    public function whatsappLogsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.whatsapp.logs');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'logs' => PartnerServices::whatsapp($payload['partner'])['logs']]);
    }

    public function financingApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.financing');

        return response()->json(PartnerServices::financing($payload['partner']));
    }

    public function updateFinancingApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.financing.settings');

        return response()->json(PartnerServices::updateFinancing($payload['partner'], $this->validateFinancing($request), $payload['partnerUser']));
    }

    public function financingRequestsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.financing.requests');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'requests' => PartnerServices::financing($payload['partner'])['requests']]);
    }

    public function updateFinancingRequestApi(Request $request, string $record): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.financing.requests.status');
        $validated = $request->validate(['status' => ['required', 'string', 'max:80']]);

        return response()->json(PartnerServices::updateFinancingRequest($payload['partner'], $record, $validated['status'], $payload['partnerUser']));
    }

    public function growthApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.growth');

        return response()->json(PartnerServices::growth($payload['partner']));
    }

    public function growthRecommendationsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.services.growth.recommendations');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'recommendations' => PartnerServices::growth($payload['partner'])['recommendations']]);
    }

    public function channels(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.channels');
        $payload['activeSection'] = 'channels';
        $payload['activePage'] = 'overview';
        $payload['channels'] = PartnerChannels::summary($payload['partner'], $request);

        return view('partner.channels.index', $payload);
    }

    public function channelStorefront(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.channels.storefront');
        $payload['activeSection'] = 'channels';
        $payload['activePage'] = 'storefront';
        $payload['storefrontChannel'] = PartnerChannels::storefront($payload['partner']);

        return view('partner.channels.storefront', $payload);
    }

    public function channelMarketplaces(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.channels.marketplaces');
        $this->authorizeWorkspacePage($payload, 'channels', 'marketplaces');
        $payload['activeSection'] = 'channels';
        $payload['activePage'] = 'marketplaces';
        $payload['marketplaces'] = PartnerChannels::marketplaces($payload['partner'], $request);

        return view('partner.channels.marketplaces', $payload);
    }

    public function channelMobileApp(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.channels.mobile-app');
        $this->authorizeWorkspacePage($payload, 'channels', 'mobile-app');
        $payload['activeSection'] = 'channels';
        $payload['activePage'] = 'mobile-app';
        $payload['mobileApp'] = PartnerChannels::mobileApp($payload['partner']);

        return view('partner.channels.mobile-app', $payload);
    }

    public function channelPos(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.channels.pos');
        $this->authorizeWorkspacePage($payload, 'channels', 'pos');
        $payload['activeSection'] = 'channels';
        $payload['activePage'] = 'pos';
        $payload['pos'] = PartnerChannels::pos($payload['partner']);

        return view('partner.channels.pos', $payload);
    }

    public function updateChannelStatus(Request $request, string $channel): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerChannels::STATUSES))]]);
        PartnerChannels::updateStatus($payload['partner'], $channel, $validated['status'], $payload['partnerUser']);

        return back()->with('status', 'تم تحديث حالة القناة.');
    }

    public function syncChannel(Request $request, string $channel): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.sync');
        $result = PartnerChannels::sync($payload['partner'], $channel, $payload['partnerUser']);

        return back()->with('status', $result['success'] ? 'تمت المزامنة بنجاح.' : 'القناة تحتاج تفعيل قبل المزامنة.');
    }

    public function updateChannelStorefront(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.storefront.settings');
        PartnerChannels::updateStorefront($payload['partner'], $request->validate([
            'visibility' => ['nullable', 'string', 'max:80'],
            'domain_status' => ['nullable', 'string', 'max:80'],
            'theme_status' => ['nullable', 'string', 'max:80'],
        ]), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات قناة المتجر.');
    }

    public function updateChannelMarketplace(Request $request, string $marketplace): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.marketplaces.settings');
        PartnerChannels::updateMarketplace($payload['partner'], $marketplace, $this->validateMarketplace($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات منصة البيع.');
    }

    public function syncChannelMarketplace(Request $request, string $marketplace, string $type): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.marketplaces.sync');
        PartnerChannels::syncMarketplace($payload['partner'], $marketplace, $type, $payload['partnerUser']);

        return back()->with('status', 'تم تشغيل مزامنة المنصة.');
    }

    public function updateChannelMobileApp(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.mobile-app.settings');
        PartnerChannels::updateMobileApp($payload['partner'], $this->validateMobileApp($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات تطبيق الجوال.');
    }

    public function testChannelMobilePush(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.mobile-app.push-test');
        $result = PartnerChannels::pushTest($payload['partner'], $payload['partnerUser']);

        return back()->with('status', $result['message']);
    }

    public function updateChannelPos(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.pos.settings');
        PartnerChannels::updatePos($payload['partner'], $this->validatePos($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات POS.');
    }

    public function createChannelPosDevice(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.channels.pos.devices.store');
        PartnerChannels::createPosDevice($payload['partner'], $this->validatePosDevice($request), $payload['partnerUser']);

        return back()->with('status', 'تمت إضافة جهاز POS.');
    }

    public function channelsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels');

        return response()->json(PartnerChannels::summary($payload['partner'], $request));
    }

    public function channelShowApi(Request $request, string $channel): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.show');

        return response()->json(PartnerChannels::channel($payload['partner'], $channel));
    }

    public function updateChannelStatusApi(Request $request, string $channel): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerChannels::STATUSES))]]);

        return response()->json(PartnerChannels::updateStatus($payload['partner'], $channel, $validated['status'], $payload['partnerUser']));
    }

    public function channelSyncStatusApi(Request $request, string $channel): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.sync-status');

        return response()->json(PartnerChannels::syncStatus($payload['partner'], $channel));
    }

    public function syncChannelApi(Request $request, string $channel): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.sync');

        return response()->json(PartnerChannels::sync($payload['partner'], $channel, $payload['partnerUser']));
    }

    public function channelLogsApi(Request $request, string $channel): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.logs');

        return response()->json(PartnerChannels::logs($payload['partner'], $channel));
    }

    public function channelStorefrontApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.storefront');

        return response()->json(PartnerChannels::storefront($payload['partner']));
    }

    public function updateChannelStorefrontApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.storefront.settings');

        return response()->json(PartnerChannels::updateStorefront($payload['partner'], $request->validate([
            'visibility' => ['nullable', 'string', 'max:80'],
            'domain_status' => ['nullable', 'string', 'max:80'],
            'theme_status' => ['nullable', 'string', 'max:80'],
        ]), $payload['partnerUser']));
    }

    public function channelMarketplacesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.marketplaces');

        return response()->json(PartnerChannels::marketplaces($payload['partner'], $request));
    }

    public function connectMarketplaceApi(Request $request, string $marketplace): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.marketplaces.connect');

        return response()->json(PartnerChannels::connectMarketplace($payload['partner'], $marketplace, $this->validateMarketplace($request), $payload['partnerUser']));
    }

    public function updateMarketplaceApi(Request $request, string $marketplace): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.marketplaces.settings');

        return response()->json(PartnerChannels::updateMarketplace($payload['partner'], $marketplace, $this->validateMarketplace($request), $payload['partnerUser']));
    }

    public function syncMarketplaceProductsApi(Request $request, string $marketplace): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.marketplaces.sync-products');

        return response()->json(PartnerChannels::syncMarketplace($payload['partner'], $marketplace, 'products', $payload['partnerUser']));
    }

    public function syncMarketplaceOrdersApi(Request $request, string $marketplace): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.marketplaces.sync-orders');

        return response()->json(PartnerChannels::syncMarketplace($payload['partner'], $marketplace, 'orders', $payload['partnerUser']));
    }

    public function channelMobileAppApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.mobile-app');

        return response()->json(PartnerChannels::mobileApp($payload['partner']));
    }

    public function updateMobileAppApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.mobile-app.settings');

        return response()->json(PartnerChannels::updateMobileApp($payload['partner'], $this->validateMobileApp($request), $payload['partnerUser']));
    }

    public function mobilePushTestApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.mobile-app.push-test');

        return response()->json(PartnerChannels::pushTest($payload['partner'], $payload['partnerUser']));
    }

    public function channelPosApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos');

        return response()->json(PartnerChannels::pos($payload['partner']));
    }

    public function updatePosApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos.settings');

        return response()->json(PartnerChannels::updatePos($payload['partner'], $this->validatePos($request), $payload['partnerUser']));
    }

    public function posDevicesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos.devices');

        return response()->json(['store_id' => $payload['partner']['store_id'], 'devices' => PartnerChannels::pos($payload['partner'])['devices']]);
    }

    public function storePosDeviceApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos.devices.store');

        return response()->json(PartnerChannels::createPosDevice($payload['partner'], $this->validatePosDevice($request), $payload['partnerUser']), 201);
    }

    public function updatePosDeviceApi(Request $request, string $device): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos.devices.update');

        return response()->json(PartnerChannels::updatePosDevice($payload['partner'], $device, $this->validatePosDevice($request, false), $payload['partnerUser']));
    }

    public function posReportsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.channels.pos.reports');

        return response()->json(PartnerChannels::posReports($payload['partner']));
    }

    public function apps(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'overview';
        $payload['apps'] = PartnerApps::summary($payload['partner'], $request);

        return view('partner.apps.index', $payload);
    }

    public function appsMarketplace(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.marketplace');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'marketplace';
        $payload['apps'] = PartnerApps::marketplace($payload['partner'], $request);

        return view('partner.apps.marketplace', $payload);
    }

    public function appsInstalled(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.installed');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'installed';
        $payload['apps'] = PartnerApps::installed($payload['partner'], $request);

        return view('partner.apps.installed', $payload);
    }

    public function appShow(Request $request, string $app): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.show');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'marketplace';
        $payload['appPage'] = PartnerApps::app($payload['partner'], $app);

        return view('partner.apps.show', $payload);
    }

    public function appSettings(Request $request, string $app): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.settings');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'installed';
        $payload['appPage'] = PartnerApps::app($payload['partner'], $app);

        return view('partner.apps.settings', $payload);
    }

    public function appsAutomations(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.automations');
        $this->authorizeWorkspacePage($payload, 'apps', 'automation');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'automation';
        $payload['automations'] = PartnerApps::automations($payload['partner'], $request);

        return view('partner.apps.automations', $payload);
    }

    public function appsAi(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.ai');
        $this->authorizeWorkspacePage($payload, 'apps', 'ai');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'ai';
        $payload['ai'] = [
            'tools' => PartnerApps::aiTools($payload['partner']),
            'usage' => PartnerApps::aiUsage($payload['partner']),
            'recommendations' => PartnerApps::aiRecommendations($payload['partner']),
        ];

        return view('partner.apps.ai', $payload);
    }

    public function solveAi(Request $request): View
    {
        return $this->solveAiView($request, 'home');
    }

    public function solveAiTools(Request $request): View
    {
        return $this->solveAiView($request, 'tools');
    }

    public function solveAiChat(Request $request): View
    {
        return $this->solveAiView($request, 'chat');
    }

    public function solveAiHistory(Request $request): View
    {
        return $this->solveAiView($request, 'history');
    }

    public function solveAiSettings(Request $request): View
    {
        return $this->solveAiView($request, 'settings');
    }

    private function solveAiView(Request $request, string $mode): View
    {
        $payload = $this->tenantPayload($request, 'partner.apps.solve-ai');
        $this->authorizeWorkspacePage($payload, 'apps', 'solve-ai');
        $payload['activeSection'] = 'apps';
        $payload['activePage'] = 'solve-ai';
        $payload['solveAi'] = PartnerSolveAi::dashboard($payload['partner'], $mode);

        return view('partner.apps.solve-ai', $payload);
    }

    public function installApp(Request $request, string $app): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.install');
        PartnerApps::install($payload['partner'], $app, $payload['partnerUser']);

        return back()->with('status', 'تم تثبيت التطبيق.');
    }

    public function uninstallApp(Request $request, string $app): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.uninstall');
        PartnerApps::uninstall($payload['partner'], $app, $payload['partnerUser']);

        return redirect()->route('partner.apps.installed')->with('status', 'تمت إزالة التطبيق.');
    }

    public function updateAppSettings(Request $request, string $app): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.settings.update');
        PartnerApps::updateSettings($payload['partner'], $app, $this->validateAppSettings($request), $payload['partnerUser']);

        return back()->with('status', 'تم حفظ إعدادات التطبيق.');
    }

    public function testApp(Request $request, string $app): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.test');
        $result = PartnerApps::test($payload['partner'], $app, $payload['partnerUser']);

        return back()->with('status', $result['message']);
    }

    public function createAutomation(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.automations.store');
        PartnerApps::createAutomation($payload['partner'], $this->validateAutomation($request), $payload['partnerUser']);

        return back()->with('status', 'تم إنشاء قاعدة الأتمتة.');
    }

    public function generateAi(Request $request): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.apps.ai.generate');
        $result = PartnerApps::aiGenerate($payload['partner'], $this->validateAiGenerate($request), $payload['partnerUser']);

        return back()->with('status', $result['output']);
    }

    public function appsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps');

        return response()->json(PartnerApps::summary($payload['partner'], $request));
    }

    public function appsMarketplaceApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.marketplace');

        return response()->json(PartnerApps::marketplace($payload['partner'], $request));
    }

    public function appsInstalledApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.installed');

        return response()->json(PartnerApps::installed($payload['partner'], $request));
    }

    public function appShowApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.show');

        return response()->json(PartnerApps::app($payload['partner'], $app));
    }

    public function installAppApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.install');

        return response()->json(PartnerApps::install($payload['partner'], $app, $payload['partnerUser']), 201);
    }

    public function uninstallAppApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.uninstall');
        PartnerApps::uninstall($payload['partner'], $app, $payload['partnerUser']);

        return response()->json(['deleted' => true, 'store_id' => $payload['partner']['store_id']]);
    }

    public function updateAppStatusApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerApps::STATUSES))]]);

        return response()->json(PartnerApps::updateStatus($payload['partner'], $app, $validated['status'], $payload['partnerUser']));
    }

    public function appSettingsApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.settings');

        return response()->json(PartnerApps::settings($payload['partner'], $app));
    }

    public function updateAppSettingsApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.settings.update');

        return response()->json(PartnerApps::updateSettings($payload['partner'], $app, $this->validateAppSettings($request), $payload['partnerUser']));
    }

    public function testAppApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.test');

        return response()->json(PartnerApps::test($payload['partner'], $app, $payload['partnerUser']));
    }

    public function appLogsApi(Request $request, string $app): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.apps.logs');

        return response()->json(PartnerApps::logs($payload['partner'], $app));
    }

    public function automationsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations');

        return response()->json(PartnerApps::automations($payload['partner'], $request));
    }

    public function storeAutomationApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations.store');

        return response()->json(PartnerApps::createAutomation($payload['partner'], $this->validateAutomation($request), $payload['partnerUser']), 201);
    }

    public function updateAutomationApi(Request $request, string $automation): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations.update');

        return response()->json(PartnerApps::updateAutomation($payload['partner'], $automation, $this->validateAutomation($request, false), $payload['partnerUser']));
    }

    public function deleteAutomationApi(Request $request, string $automation): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations.delete');
        PartnerApps::deleteAutomation($payload['partner'], $automation, $payload['partnerUser']);

        return response()->json(['deleted' => true, 'store_id' => $payload['partner']['store_id']]);
    }

    public function updateAutomationStatusApi(Request $request, string $automation): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations.status');
        $validated = $request->validate(['status' => ['required', 'in:installed,disabled']]);

        return response()->json(PartnerApps::updateAutomationStatus($payload['partner'], $automation, $validated['status'], $payload['partnerUser']));
    }

    public function automationLogsApi(Request $request, string $automation): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.automations.logs');

        return response()->json(PartnerApps::automationLogs($payload['partner'], $automation));
    }

    public function aiToolsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.ai.tools');

        return response()->json(PartnerApps::aiTools($payload['partner']));
    }

    public function aiGenerateApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.ai.generate');

        return response()->json(PartnerApps::aiGenerate($payload['partner'], $this->validateAiGenerate($request), $payload['partnerUser']));
    }

    public function aiUsageApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.ai.usage');

        return response()->json(PartnerApps::aiUsage($payload['partner']));
    }

    public function aiRecommendationsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.ai.recommendations');

        return response()->json(PartnerApps::aiRecommendations($payload['partner']));
    }

    public function solveAiToolsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.tools');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'tools' => PartnerSolveAi::tools($payload['partner']),
            'usage' => PartnerSolveAi::usage($payload['partner']),
        ]);
    }

    public function solveAiGenerateApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.generate');

        return response()->json(PartnerSolveAi::generate($payload['partner'], $this->validateSolveAi($request), $payload['partnerUser']));
    }

    public function solveAiImproveApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.improve');

        return response()->json(PartnerSolveAi::generate($payload['partner'], $this->validateSolveAi($request), $payload['partnerUser'], 'improve'));
    }

    public function solveAiAnalyzeApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.analyze');

        return response()->json(PartnerSolveAi::generate($payload['partner'], $this->validateSolveAi($request), $payload['partnerUser'], 'analyze'));
    }

    public function solveAiApplyApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.apply');

        return response()->json(PartnerSolveAi::apply($payload['partner'], $this->validateSolveAiApply($request), $payload['partnerUser']));
    }

    public function solveAiChatApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.chat');

        return response()->json(PartnerSolveAi::chat($payload['partner'], $this->validateSolveAi($request, false), $payload['partnerUser']));
    }

    public function solveAiChatHistoryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.chat.history');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'history' => PartnerSolveAi::history($payload['partner'], 50),
        ]);
    }

    public function deleteSolveAiChatApi(Request $request, string $chat): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.chat.delete');
        PartnerSolveAi::deleteChat($payload['partner'], $chat, $payload['partnerUser']);

        return response()->json(['deleted' => true, 'store_id' => $payload['partner']['store_id']]);
    }

    public function solveAiUsageApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.solve-ai.usage');

        return response()->json(PartnerSolveAi::usage($payload['partner']));
    }

    public function marketingSummaryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.summary');

        return response()->json(PartnerMarketing::summary($payload['partner']));
    }

    public function marketingListApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section);

        return response()->json(PartnerMarketing::list($payload['partner'], $this->marketingSectionName($section), $request));
    }

    public function marketingShowApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section . '.show');

        return response()->json(PartnerMarketing::find($payload['partner'], $this->marketingSectionName($section), $record));
    }

    public function storeMarketingApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section . '.store');

        return response()->json(PartnerMarketing::create($payload['partner'], $this->marketingSectionName($section), $this->validateMarketing($request, $section), $payload['partnerUser']), 201);
    }

    public function updateMarketingApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section . '.update');

        return response()->json(PartnerMarketing::update($payload['partner'], $this->marketingSectionName($section), $record, $this->validateMarketing($request, $section), $payload['partnerUser']));
    }

    public function deleteMarketingApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section . '.delete');
        PartnerMarketing::delete($payload['partner'], $this->marketingSectionName($section), $record, $payload['partnerUser']);

        return response()->json(['deleted' => true]);
    }

    public function updateMarketingStatusApi(Request $request, string $record, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.marketing.' . $section . '.status');
        $validated = $request->validate(['status' => ['required', 'in:' . implode(',', array_keys(PartnerMarketing::STATUSES))]]);

        return response()->json(PartnerMarketing::updateStatus($payload['partner'], $this->marketingSectionName($section), $record, $validated['status'], $payload['partnerUser']));
    }

    public function couponUsageApi(Request $request, string $coupon): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.coupons.usage');

        return response()->json(PartnerMarketing::couponUsage($payload['partner'], $coupon));
    }

    public function campaignAnalyticsApi(Request $request, string $campaign): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.campaigns.analytics');

        return response()->json(PartnerMarketing::campaignAnalytics($payload['partner'], $campaign));
    }

    public function loyaltyApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.loyalty');

        return response()->json(PartnerMarketing::loyalty($payload['partner']));
    }

    public function updateLoyaltySettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.loyalty.settings');
        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'points_per_currency' => ['required', 'integer', 'min:1'],
            'point_value' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(PartnerMarketing::updateLoyaltySettings($payload['partner'], $validated, $payload['partnerUser']));
    }

    public function createAbandonedCartCouponApi(Request $request, string $cart): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.abandoned-carts.coupon');

        return response()->json(PartnerMarketing::createAbandonedCartCoupon($payload['partner'], $cart, $payload['partnerUser']), 201);
    }

    public function adsReportsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.ads.reports');

        return response()->json(PartnerMarketing::adsReports($payload['partner']));
    }

    public function analytics(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.analytics');
        $payload['activeSection'] = 'analytics';
        $payload['activePage'] = 'overview';
        $payload['analytics'] = PartnerAnalytics::overview($payload['partner'], $request);
        $payload['analytics'] = $this->filterAnalyticsTabs($payload['analytics'], $payload['partnerUser'], $payload['partner']);

        $this->rememberRecent($request, $payload['analytics']['title'], route('partner.analytics', $request->query()));

        return view('partner.analytics.index', $payload);
    }

    public function analyticsReport(Request $request, string $report): View
    {
        $payload = $this->tenantPayload($request, 'partner.analytics.' . $report);
        $this->authorizeAnalyticsReport($report, $payload['partnerUser'], $payload['partner']);
        $payload['activeSection'] = 'analytics';
        $payload['activePage'] = $report;
        $payload['analytics'] = PartnerAnalytics::report($payload['partner'], $report, $request);
        $payload['analytics'] = $this->filterAnalyticsTabs($payload['analytics'], $payload['partnerUser'], $payload['partner']);

        $this->rememberRecent($request, $payload['analytics']['title'], route('partner.analytics.' . $report, $request->query()));

        return view('partner.analytics.index', $payload);
    }

    public function analyticsReportApi(Request $request, string $report): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.analytics.' . $report);
        $this->authorizeAnalyticsReport($report, $payload['partnerUser'], $payload['partner']);

        return response()->json($this->filterAnalyticsTabs(
            PartnerAnalytics::report($payload['partner'], $report, $request),
            $payload['partnerUser'],
            $payload['partner']
        ));
    }

    public function analyticsSummaryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.analytics.summary');
        $this->authorizeAnalyticsReport('overview', $payload['partnerUser'], $payload['partner']);

        return response()->json($this->filterAnalyticsTabs(
            PartnerAnalytics::summary($payload['partner'], $request),
            $payload['partnerUser'],
            $payload['partner']
        ));
    }

    public function analyticsOfficialReportApi(Request $request, string $report): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.analytics.' . $report);
        $this->authorizeAnalyticsReport($report, $payload['partnerUser'], $payload['partner']);

        return response()->json($this->filterAnalyticsTabs(
            PartnerAnalytics::report($payload['partner'], $report, $request),
            $payload['partnerUser'],
            $payload['partner']
        ));
    }

    public function exportAnalyticsApi(Request $request): Response
    {
        $validated = $request->validate([
            'report' => ['nullable', 'string', 'in:overview,live,sales,inventory,customers,finance,marketing,operations,products,payments'],
            'format' => ['nullable', 'string', 'in:csv,excel,xlsx,pdf'],
        ]);
        $report = $validated['report'] ?? 'overview';
        $payload = $this->tenantPayload($request, 'api.partner.analytics.export.' . $report);
        $this->authorizeAnalyticsReport($report, $payload['partnerUser'], $payload['partner']);

        return PartnerAnalytics::export($payload['partner'], $report, $request);
    }

    public function exportAnalytics(Request $request, string $report): Response
    {
        $payload = $this->tenantPayload($request, 'partner.analytics.export.' . $report);
        $this->authorizeAnalyticsReport($report, $payload['partnerUser'], $payload['partner']);

        return PartnerAnalytics::export($payload['partner'], $report, $request);
    }

    public function settings(Request $request): View
    {
        $payload = $this->tenantPayload($request, 'partner.settings');
        $payload['activeSection'] = 'settings';
        $payload['activePage'] = 'store';
        $payload['settingsGroups'] = PartnerSettings::groups($payload['partner']);

        return view('partner.settings.index', $payload);
    }

    public function settingsSection(Request $request, string $section): View|RedirectResponse
    {
        if ($section === 'storefront') {
            return redirect()->route('partner.storefront.customize');
        }

        $payload = $this->tenantPayload($request, 'partner.settings.' . $section);
        $payload['activeSection'] = 'settings';
        $payload['activePage'] = $section === 'staff' ? 'staff' : $section;
        $payload['settingsSection'] = PartnerSettings::section($payload['partner'], $section);
        $payload['canManageSettings'] = PartnerTenantStore::can($payload['partnerUser'], 'manage-settings');

        if ($section === 'staff') {
            $payload['staffPage'] = PartnerSettingsSuite::staff($payload['partner']);

            return view('partner.settings.staff', $payload);
        }

        if ($section === 'permissions') {
            $payload['rolesPage'] = PartnerSettingsSuite::roles($payload['partner']);

            return view('partner.settings.permissions', $payload);
        }

        if ($section === 'security') {
            $payload['securitySessions'] = PartnerSettingsSuite::sessions($payload['partner']);
            $payload['loginHistory'] = PartnerSettingsSuite::loginHistory($payload['partner']);

            return view('partner.settings.security', $payload);
        }

        return view('partner.settings.section', $payload);
    }

    public function updateSettingsSection(Request $request, string $section): RedirectResponse
    {
        $payload = $this->tenantPayload($request, 'partner.settings.update.' . $section);
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:2000'],
        ]);

        PartnerSettings::update($payload['partner'], $section, $validated['settings']);

        return back()->with('status', 'تم حفظ الإعدادات.');
    }

    public function settingsSectionApi(Request $request, string $section): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.settings.' . $section);

        return response()->json(PartnerSettings::api($payload['partner'], $section));
    }

    public function settingsOverviewApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.settings');

        return response()->json(PartnerSettingsSuite::summary($payload['partner']));
    }

    public function updatePartnerStoreSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.settings.store.update');

        return response()->json(PartnerSettingsSuite::updateSection($payload['partner'], 'store', $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function updatePartnerIdentitySettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.settings.identity.update');

        return response()->json(PartnerSettingsSuite::updateSection($payload['partner'], 'identity', $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function uploadPartnerIdentityApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.settings.identity.upload');
        $validated = $request->validate([
            'type' => ['required', 'in:logo,favicon,social_image'],
            'path' => ['required', 'string', 'max:500'],
        ]);

        return response()->json(PartnerSettingsSuite::uploadIdentity($payload['partner'], $validated, $payload['partnerUser']), 201);
    }

    public function partnerStaffApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.staff');

        return response()->json(PartnerSettingsSuite::staff($payload['partner']));
    }

    public function invitePartnerStaffApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.staff.invite');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email', 'max:180'],
            'username' => ['nullable', 'string', 'max:180', 'unique:partner_users,username'],
            'role' => ['nullable', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:120'],
        ]);

        return response()->json(PartnerSettingsSuite::inviteStaff($payload['partner'], $validated, $payload['partnerUser']), 201);
    }

    public function updatePartnerStaffApi(Request $request, string $staff): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.staff.update');
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'status' => ['nullable', 'in:active,invited,disabled'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:120'],
        ]);

        return response()->json(PartnerSettingsSuite::updateStaff($payload['partner'], $staff, $validated, $payload['partnerUser']));
    }

    public function deletePartnerStaffApi(Request $request, string $staff): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.staff.delete');

        return response()->json(PartnerSettingsSuite::deleteStaff($payload['partner'], $staff, $payload['partnerUser']));
    }

    public function partnerRolesApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.roles');

        return response()->json(PartnerSettingsSuite::roles($payload['partner']));
    }

    public function storePartnerRoleApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.roles.store');
        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
        ]);

        return response()->json(PartnerSettingsSuite::createRole($payload['partner'], $validated, $payload['partnerUser']), 201);
    }

    public function updatePartnerRoleApi(Request $request, string $role): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.roles.update');
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
            'status' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(PartnerSettingsSuite::updateRole($payload['partner'], $role, $validated, $payload['partnerUser']));
    }

    public function deletePartnerRoleApi(Request $request, string $role): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.roles.delete');

        return response()->json(PartnerSettingsSuite::deleteRole($payload['partner'], $role, $payload['partnerUser']));
    }

    public function assignPartnerStaffRoleApi(Request $request, string $staff): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.staff.role');
        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:80'],
            'role_id' => ['nullable', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:120'],
        ]);

        return response()->json(PartnerSettingsSuite::assignRole($payload['partner'], $staff, $validated, $payload['partnerUser']));
    }

    public function deletePartnerDomainApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.domain.delete');

        return response()->json(PartnerSettingsSuite::deleteDomain($payload['partner'], $payload['partnerUser']));
    }

    public function shippingSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.shipping-settings');

        return response()->json(PartnerSettingsSuite::shipping($payload['partner']));
    }

    public function updateShippingSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.shipping-settings.update');

        return response()->json(PartnerSettingsSuite::updateShipping($payload['partner'], $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function paymentSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.payment-settings');

        return response()->json(PartnerSettingsSuite::payments($payload['partner']));
    }

    public function updatePaymentSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.payment-settings.update');

        return response()->json(PartnerSettingsSuite::updatePayments($payload['partner'], $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function taxSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.tax-settings');

        return response()->json(PartnerSettingsSuite::taxes($payload['partner']));
    }

    public function updateTaxSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.tax-settings.update');

        return response()->json(PartnerSettingsSuite::updateTaxes($payload['partner'], $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function notificationSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.notification-settings');

        return response()->json(PartnerSettingsSuite::notifications($payload['partner']));
    }

    public function updateNotificationSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.notification-settings.update');

        return response()->json(PartnerSettingsSuite::updateNotifications($payload['partner'], $this->validateSettingsPayload($request), $payload['partnerUser']));
    }

    public function testNotificationSettingsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.notification-settings.test');
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'max:80'],
            'template' => ['nullable', 'string', 'max:120'],
            'recipient' => ['nullable', 'string', 'max:180'],
        ]);

        return response()->json(PartnerSettingsSuite::testNotification($payload['partner'], $validated, $payload['partnerUser']));
    }

    public function securitySessionsApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.security.sessions');

        return response()->json(PartnerSettingsSuite::sessions($payload['partner']));
    }

    public function deleteSecuritySessionApi(Request $request, string $session): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.security.sessions.delete');

        return response()->json(PartnerSettingsSuite::deleteSession($payload['partner'], $session, $payload['partnerUser']));
    }

    public function enableTwoFactorApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.security.2fa.enable');

        return response()->json(PartnerSettingsSuite::enableTwoFactor($payload['partner'], $payload['partnerUser']));
    }

    public function disableTwoFactorApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.security.2fa.disable');

        return response()->json(PartnerSettingsSuite::disableTwoFactor($payload['partner'], $payload['partnerUser']));
    }

    public function loginHistoryApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'api.partner.security.login-history');

        return response()->json(PartnerSettingsSuite::loginHistory($payload['partner']));
    }

    public function staff(Request $request): View
    {
        return view('partner.staff', $this->tenantPayload($request, 'partner.staff'));
    }

    public function page(Request $request, string $section, string $page): View
    {
        $payload = $this->tenantPayload($request, "partner.pages.$section.$page");
        $definition = PartnerWorkspace::findPage($section, $page);

        abort_unless($definition, 404);
        abort_unless(PartnerWorkspace::isAllowed($definition['item'], $payload['partnerUser'], $payload['partner']), 403);

        $payload['page'] = PartnerWorkspace::pagePayload($payload['partner'], $section, $page);
        $payload['activeSection'] = $section;
        $payload['activePage'] = $page;

        $this->rememberRecent($request, $definition['item']['label'], route('partner.pages.show', ['section' => $section, 'page' => $page]));

        return view('partner.page', $payload);
    }

    public function navigationApi(Request $request): JsonResponse
    {
        $payload = $this->tenantPayload($request, 'partner.api.navigation');

        return response()->json([
            'store_id' => $payload['partner']['store_id'],
            'role' => $payload['partnerUser']['role'],
            'plan' => $payload['partner']['plan'],
            'sections' => PartnerWorkspace::visibleSections($payload['partnerUser'], $payload['partner']),
        ]);
    }

    public function pageApi(Request $request, string $section, string $page): JsonResponse
    {
        $payload = $this->tenantPayload($request, "partner.api.$section.$page");
        $definition = PartnerWorkspace::findPage($section, $page);

        abort_unless($definition, 404);
        abort_unless(PartnerWorkspace::isAllowed($definition['item'], $payload['partnerUser'], $payload['partner']), 403);

        return response()->json(PartnerWorkspace::pagePayload($payload['partner'], $section, $page));
    }

    private function resourceView(Request $request, string $resource, string $title, string $description): View
    {
        $payload = $this->tenantPayload($request, 'partner.' . $resource);
        $payload['resourceKey'] = $resource;
        $payload['resourceTitle'] = $title;
        $payload['resourceDescription'] = $description;
        $payload['rows'] = $payload['partner'][$resource] ?? [];

        return view('partner.resource', $payload);
    }

    private function authorizeAnalyticsReport(string $report, array $user, array $partner): void
    {
        if ($report === 'overview') {
            abort_unless(PartnerTenantStore::can($user, 'view-analytics'), 403);

            return;
        }

        $definition = PartnerWorkspace::findPage('analytics', $report);

        abort_unless($definition, 404);
        abort_unless(PartnerWorkspace::isAllowed($definition['item'], $user, $partner), 403);
    }

    private function authorizeWorkspacePage(array $payload, string $section, string $page): void
    {
        $definition = PartnerWorkspace::findPage($section, $page);

        abort_unless($definition, 404);
        abort_unless(PartnerWorkspace::isAllowed($definition['item'], $payload['partnerUser'], $payload['partner']), 403);
    }

    private function filterAnalyticsTabs(array $analytics, array $user, array $partner): array
    {
        $analytics['tabs'] = collect($analytics['tabs'])
            ->filter(function (array $tab) use ($user, $partner) {
                if (($tab['key'] ?? null) === 'overview') {
                    return PartnerTenantStore::can($user, 'view-analytics');
                }

                $definition = PartnerWorkspace::findPage('analytics', (string) $tab['key']);

                return $definition && PartnerWorkspace::isAllowed($definition['item'], $user, $partner);
            })
            ->values()
            ->all();

        return $analytics;
    }

    private function ordersRelatedView(Request $request, string $activePage, string $section, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.orders.' . $activePage);
        $payload['activeSection'] = 'orders';
        $payload['activePage'] = $activePage;
        $payload['title'] = $title;
        $payload['rows'] = PartnerOrders::relatedRows($payload['partner'], $section);
        $payload['section'] = $section;

        return view('partner.orders.related', $payload);
    }

    private function productsRelatedView(Request $request, string $activePage, string $section, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.products.' . $activePage);
        $payload['activeSection'] = 'products';
        $payload['activePage'] = $activePage;
        $payload['title'] = $title;
        $payload['rows'] = PartnerProducts::relatedRows($payload['partner'], $section);
        $payload['section'] = $section;

        return view('partner.products.related', $payload);
    }

    private function customersRelatedView(Request $request, string $activePage, string $section, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.customers.' . $activePage);
        $payload['activeSection'] = 'customers';
        $payload['activePage'] = $activePage;
        $payload['title'] = $title;
        $payload['rows'] = PartnerCustomers::relatedRows($payload['partner'], $section);
        $payload['section'] = $section;

        return view('partner.customers.related', $payload);
    }

    private function marketingRelatedView(Request $request, string $activePage, string $section, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.marketing.' . $activePage);
        $payload['activeSection'] = 'marketing';
        $payload['activePage'] = $activePage;
        $payload['title'] = $title;
        $payload['section'] = $section;
        $payload['marketingPage'] = PartnerMarketing::list($payload['partner'], $section, $request);

        return view('partner.marketing.related', $payload);
    }

    private function storefrontRelatedView(Request $request, string $activePage, string $section, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.storefront.' . $activePage);
        $payload['activeSection'] = 'storefront';
        $payload['activePage'] = $activePage;
        $payload['title'] = $title;
        $payload['section'] = $section;
        $payload['storefrontPage'] = PartnerStorefront::list($payload['partner'], $section, $request);

        return view('partner.storefront.related', $payload);
    }

    private function serviceTypedView(Request $request, string $type, string $title): View
    {
        $payload = $this->tenantPayload($request, 'partner.services.' . $type);
        $payload['activeSection'] = 'services';
        $payload['activePage'] = $type === 'payment-gateways' ? 'payment-gateways' : $type;
        $payload['title'] = $title;
        $payload['type'] = $type;
        $payload['servicesPage'] = PartnerServices::typed($payload['partner'], $type, $request);

        return view('partner.services.typed', $payload);
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:' . implode(',', array_keys(PartnerProducts::PRODUCT_TYPES))],
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerProducts::PRODUCT_STATUSES))],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:120'],
            'brand' => ['nullable', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'tags' => ['nullable', 'string', 'max:300'],
            'option_name' => ['nullable', 'string', 'max:80'],
            'option_values' => ['nullable', 'string', 'max:300'],
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'visibility' => ['nullable', 'in:visible,hidden'],
            'track_inventory' => ['nullable', 'boolean'],
            'allow_backorders' => ['nullable', 'boolean'],
            'requires_shipping' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerCustomers::CUSTOMER_STATUSES))],
            'tags' => ['nullable', 'string', 'max:300'],
        ]);
    }

    private function validateCustomerGroup(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'condition_type' => ['required', 'in:orders_count,total_spent,city,last_purchase'],
            'condition_value' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'max:80'],
            'customers_count' => ['nullable', 'integer', 'min:0'],
            'campaigns_count' => ['nullable', 'integer', 'min:0'],
            'linked_campaign' => ['nullable', 'string', 'max:160'],
        ]);
    }

    private function marketingSectionName(string $section): string
    {
        return match ($section) {
            'coupons' => 'marketing_coupons',
            'campaigns' => 'marketing_campaigns',
            'bundles' => 'marketing_bundles',
            'affiliate' => 'marketing_affiliate_links',
            'ads' => 'marketing_ads_integrations',
            default => abort(404),
        };
    }

    private function storefrontSectionName(string $section): string
    {
        return match ($section) {
            'pages' => 'storefront_pages',
            'banners' => 'storefront_banners',
            default => abort(404),
        };
    }

    private function validateThemeCustomization(Request $request): array
    {
        return $request->validate([
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'font' => ['nullable', 'string', 'max:80'],
            'header_style' => ['nullable', 'string', 'max:80'],
            'footer_style' => ['nullable', 'string', 'max:80'],
            'card_style' => ['nullable', 'string', 'max:80'],
            'button_style' => ['nullable', 'string', 'max:80'],
            'supports_dark' => ['nullable', 'boolean'],
        ]);
    }

    private function validateStorefrontBuilder(Request $request): array
    {
        return $request->validate([
            'page' => ['nullable', 'string', 'max:80'],
            'device' => ['nullable', 'in:desktop,tablet,mobile'],
            'mode' => ['nullable', 'string', 'max:80'],
            'settings' => ['nullable', 'array'],
            'draft' => ['nullable', 'array'],
        ]);
    }

    private function validateStorefrontSection(Request $request): array
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:180'],
            'placement' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'visible' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:active,draft,hidden,paused'],
            'settings' => ['nullable', 'array'],
            'responsive' => ['nullable', 'array'],
        ]);

        $this->rejectInlineStorefrontMedia($validated['settings'] ?? []);

        return $validated;
    }

    private function validateStorefrontSectionOrder(Request $request): array
    {
        return $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['required', 'string', 'max:120'],
        ]);
    }

    private function validateStorefrontPage(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:180'],
            'content' => ['nullable', 'string', 'max:5000'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'preview_url' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerStorefront::PAGE_STATUSES))],
        ]);
    }

    private function validateStorefrontBanner(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'image_url' => ['nullable', 'string', 'max:300'],
            'link_type' => ['required', 'in:url,product,category,page'],
            'link_target' => ['nullable', 'string', 'max:300'],
            'placement' => ['required', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerStorefront::BANNER_STATUSES))],
        ]);
    }

    private function validateBannerReorder(Request $request): array
    {
        return $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string', 'max:120'],
        ]);
    }

    private function validateNavigation(Request $request): array
    {
        return $request->validate([
            'header_menu' => ['nullable'],
            'footer_menu' => ['nullable'],
        ]);
    }

    private function validateDomain(Request $request): array
    {
        return $request->validate([
            'custom_domain' => ['required', 'string', 'max:180', 'regex:/^[A-Za-z0-9.-]+$/'],
        ]);
    }

    private function validateSeo(Request $request): array
    {
        return $request->validate([
            'meta_title' => ['required', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'social_image' => ['nullable', 'string', 'max:300'],
            'sitemap_enabled' => ['nullable', 'boolean'],
            'robots_txt' => ['nullable', 'string', 'max:2000'],
            'open_graph_enabled' => ['nullable', 'boolean'],
            'speed_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'index_status' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function validateStoreSettings(Request $request): array
    {
        return $request->validate([
            'store_name' => ['required', 'string', 'max:180'],
            'logo' => ['nullable', 'string', 'max:300'],
            'favicon' => ['nullable', 'string', 'max:300'],
            'contact_email' => ['nullable', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'working_hours' => ['nullable', 'string', 'max:300'],
            'social_links' => ['nullable'],
            'language' => ['required', 'string', 'max:10'],
            'currency' => ['required', 'string', 'max:10'],
        ]);
    }

    private function validateTypedService(Request $request, string $type): array
    {
        $base = [
            'provider' => ['required', 'string', 'max:120'],
            'api_key' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:' . implode(',', array_keys(PartnerServices::STATUSES))],
        ];

        $extra = match ($type) {
            'logistics' => [
                'regions' => ['nullable', 'string', 'max:500'],
                'shipping_rates' => ['nullable', 'string', 'max:500'],
            ],
            'payment-gateways' => [
                'mode' => ['required', 'in:test,production'],
            ],
            default => abort(404),
        };

        return $request->validate($base + $extra);
    }

    private function validateWhatsapp(Request $request): array
    {
        return $request->validate([
            'business_number' => ['required', 'string', 'max:50'],
            'access_token' => ['nullable', 'string', 'max:500'],
            'order_confirmation_template' => ['nullable', 'string', 'max:500'],
            'order_status_template' => ['nullable', 'string', 'max:500'],
            'abandoned_cart_template' => ['nullable', 'string', 'max:500'],
            'back_in_stock_template' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function validateFinancing(Request $request): array
    {
        return $request->validate([
            'provider' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
            'min_order_total' => ['required', 'numeric', 'min:0'],
            'max_installments' => ['required', 'integer', 'min:1', 'max:24'],
            'terms' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateMarketplace(Request $request): array
    {
        return $request->validate([
            'seller_id' => ['nullable', 'string', 'max:180'],
            'api_key' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(PartnerChannels::STATUSES))],
            'sync_products' => ['nullable', 'boolean'],
            'sync_orders' => ['nullable', 'boolean'],
        ]);
    }

    private function validateMobileApp(Request $request): array
    {
        return $request->validate([
            'primary_color' => ['nullable', 'string', 'max:30'],
            'logo_url' => ['nullable', 'string', 'max:300'],
            'push_enabled' => ['nullable', 'boolean'],
            'publish_status' => ['nullable', 'string', 'max:80'],
            'app_store_url' => ['nullable', 'string', 'max:300'],
            'google_play_url' => ['nullable', 'string', 'max:300'],
        ]);
    }

    private function validatePos(Request $request): array
    {
        return $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'branch_name' => ['nullable', 'string', 'max:160'],
            'sync_inventory' => ['nullable', 'boolean'],
            'allow_returns' => ['nullable', 'boolean'],
        ]);
    }

    private function validatePosDevice(Request $request, bool $requireName = true): array
    {
        return $request->validate([
            'name' => [$requireName ? 'required' : 'nullable', 'string', 'max:160'],
            'cashier' => ['nullable', 'string', 'max:160'],
            'branch' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(PartnerChannels::STATUSES))],
        ]);
    }

    private function validateAppSettings(Request $request): array
    {
        return $request->validate([
            'api_key' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable'],
            'events' => ['nullable'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ]);
    }

    private function validateSettingsPayload(Request $request): array
    {
        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'settings.*' => ['nullable'],
        ]);

        $payload = $validated['settings'] ?? $request->except(['_token', '_method']);

        return collect($payload)
            ->mapWithKeys(function (mixed $value, string $key) {
                if (is_bool($value) || is_numeric($value) || is_string($value) || is_null($value)) {
                    return [$key => (string) $value];
                }

                return [$key => json_encode($value, JSON_UNESCAPED_UNICODE)];
            })
            ->all();
    }

    private function validateAutomation(Request $request, bool $requireName = true): array
    {
        return $request->validate([
            'name' => [$requireName ? 'required' : 'nullable', 'string', 'max:180'],
            'trigger' => [$requireName ? 'required' : 'nullable', 'in:new_order,payment_paid,low_stock,abandoned_cart,new_customer'],
            'action' => [$requireName ? 'required' : 'nullable', 'in:send_whatsapp,send_email,create_coupon,send_notification,update_status'],
            'conditions' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:installed,disabled'],
        ]);
    }

    private function validateAiGenerate(Request $request): array
    {
        return $request->validate([
            'tool' => ['required', 'in:product-description,product-title,seo-keywords,store-analysis,campaign-ideas,product-improvements'],
            'prompt' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validateSolveAi(Request $request, bool $toolRequired = true): array
    {
        return $request->validate([
            'tool' => [$toolRequired ? 'required' : 'nullable', 'string', 'max:120'],
            'prompt' => ['nullable', 'string', 'max:3000'],
            'context_id' => ['nullable', 'string', 'max:120'],
            'product_id' => ['nullable', 'string', 'max:120'],
            'tone' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function validateSolveAiApply(Request $request): array
    {
        return $request->validate([
            'tool' => ['required', 'string', 'max:120'],
            'output' => ['required', 'string', 'max:10000'],
            'prompt' => ['nullable', 'string', 'max:3000'],
            'target_type' => ['nullable', 'in:product,campaign,policy,support,note'],
            'target_id' => ['nullable', 'string', 'max:160'],
            'product_id' => ['nullable', 'string', 'max:160'],
        ]);
    }

    private function validateMarketing(Request $request, string $section): array
    {
        $base = [
            'name' => ['required', 'string', 'max:180'],
            'status' => ['nullable', 'in:' . implode(',', array_keys(PartnerMarketing::STATUSES))],
        ];

        $extra = match ($section) {
            'coupons' => [
                'code' => ['required', 'string', 'max:60'],
                'discount_type' => ['required', 'in:' . implode(',', array_keys(PartnerMarketing::COUPON_TYPES))],
                'discount_value' => ['nullable', 'numeric', 'min:0'],
                'minimum_order' => ['nullable', 'numeric', 'min:0'],
                'conditions' => ['nullable', 'string', 'max:500'],
                'starts_at' => ['nullable', 'date'],
                'ends_at' => ['nullable', 'date'],
                'usage_limit' => ['nullable', 'integer', 'min:1'],
            ],
            'campaigns' => [
                'type' => ['required', 'in:' . implode(',', array_keys(PartnerMarketing::CAMPAIGN_TYPES))],
                'target_audience' => ['nullable', 'string', 'max:180'],
                'products' => ['nullable', 'string', 'max:300'],
                'coupon_code' => ['nullable', 'string', 'max:80'],
                'scheduled_at' => ['nullable', 'date'],
                'visits' => ['nullable', 'integer', 'min:0'],
                'orders' => ['nullable', 'integer', 'min:0'],
                'sales' => ['nullable', 'numeric', 'min:0'],
            ],
            'bundles' => [
                'products' => ['nullable', 'string', 'max:400'],
                'bundle_price' => ['required', 'numeric', 'min:0'],
                'discount_value' => ['nullable', 'numeric', 'min:0'],
                'orders' => ['nullable', 'integer', 'min:0'],
                'sales' => ['nullable', 'numeric', 'min:0'],
            ],
            'affiliate' => [
                'marketer' => ['required', 'string', 'max:160'],
                'url' => ['nullable', 'url', 'max:300'],
                'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'orders' => ['nullable', 'integer', 'min:0'],
                'earnings' => ['nullable', 'numeric', 'min:0'],
            ],
            'ads' => [
                'provider' => ['required', 'string', 'max:120'],
                'pixel_id' => ['nullable', 'string', 'max:160'],
                'conversions' => ['nullable', 'integer', 'min:0'],
                'spend' => ['nullable', 'numeric', 'min:0'],
                'sales' => ['nullable', 'numeric', 'min:0'],
            ],
            default => abort(404),
        };

        return $request->validate($base + $extra);
    }

    private function productSectionName(string $section): string
    {
        return match ($section) {
            'categories' => 'product_categories',
            'product-filters' => 'product_filters',
            'custom-fields' => 'product_custom_fields',
            'options' => 'product_options',
            default => abort(404),
        };
    }

    private function validateProductRelated(Request $request, string $section): array
    {
        $base = [
            'name' => ['required', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        $extra = match ($section) {
            'categories' => [
                'parent_id' => ['nullable', 'string', 'max:120'],
                'products_count' => ['nullable', 'integer', 'min:0'],
            ],
            'product-filters' => [
                'category' => ['nullable', 'string', 'max:120'],
                'values' => ['nullable', 'string', 'max:1000'],
            ],
            'custom-fields' => [
                'type' => ['required', 'in:نص,رقم,اختيار,تاريخ,ملف,text,number,select,date,file'],
                'category' => ['nullable', 'string', 'max:120'],
                'required' => ['nullable', 'string', 'max:20'],
            ],
            'options' => [
                'values' => ['nullable', 'string', 'max:1000'],
                'products_count' => ['nullable', 'integer', 'min:0'],
            ],
            default => abort(404),
        };

        return $request->validate($base + $extra);
    }

    private function validateManualOrder(Request $request): array
    {
        return $request->validate([
            'customer' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'product_id' => ['required', 'string', 'max:120'],
            'product_sku' => ['nullable', 'string', 'max:120'],
            'item_name' => ['required', 'string', 'max:160'],
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
            'total' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:paid,unpaid,pending'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'shipping_method' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:300'],
            'source_channel' => ['nullable', 'string', 'max:80'],
            'fulfillment_priority' => ['nullable', 'string', 'max:40'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'internal_note' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function storeProductImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $directory = public_path('uploads/products');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = $request->file('image');
        $name = uniqid('product-', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return 'uploads/products/' . $name;
    }

    private function storeStorefrontMediaFile(Request $request, array $partner): ?string
    {
        if (! $request->hasFile('media')) {
            return null;
        }

        $storeId = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($partner['store_id'] ?? 'store'));
        $storeId = trim((string) $storeId, '-') ?: 'store';
        $directory = public_path('uploads/storefront/' . $storeId);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file = $request->file('media');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $name = uniqid('storefront-', true) . '.' . $extension;
        $file->move($directory, $name);

        return 'uploads/storefront/' . $storeId . '/' . $name;
    }

    private function rejectInlineStorefrontMedia(array $settings): void
    {
        $mediaKeys = ['image', 'poster', 'video_url', 'background_image', 'desktop_image', 'mobile_image'];

        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                $this->rejectInlineStorefrontMedia($value);
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $trimmed = ltrim($value);
            $key = strtolower((string) $key);

            if (str_starts_with($trimmed, 'data:') || (in_array($key, $mediaKeys, true) && strlen($trimmed) > 1000)) {
                abort(422, 'ارفع الصور والفيديوهات من أداة الوسائط أولاً. لا يمكن حفظ ملفات Base64 داخل إعدادات الواجهة.');
            }
        }
    }

    private function tenantPayload(Request $request, string $activeRoute): array
    {
        $user = PartnerTenantStore::currentUser($request);
        $partner = PartnerTenantStore::currentPartner($request);

        abort_unless($user && $partner, 403);
        abort_unless(($user['store_id'] ?? null) === ($partner['store_id'] ?? null), 403);

        return [
            'activeRoute' => $activeRoute,
            'partnerUser' => $user,
            'partner' => $partner,
            'roleLabel' => PartnerTenantStore::roleLabel((string) $user['role']),
            'partnerSections' => PartnerWorkspace::visibleSections($user, $partner),
            'recentPages' => $request->session()->get('partner_recent_pages', []),
        ];
    }

    private function rememberRecent(Request $request, string $label, string $url): void
    {
        $recent = collect($request->session()->get('partner_recent_pages', []))
            ->reject(fn (array $page) => ($page['url'] ?? null) === $url)
            ->prepend(['label' => $label, 'url' => $url])
            ->take(5)
            ->values()
            ->all();

        $request->session()->put('partner_recent_pages', $recent);
    }
}
