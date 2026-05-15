<?php

namespace App\Http\Controllers;

use App\Support\AdvancedCommerceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAdvancedCommerceController extends Controller
{
    public function orderShow(string $order): View
    {
        $record = AdvancedCommerceData::findOrder($order);

        abort_if($record === null, 404);

        return view('admin.advanced.order-show', [
            'activeRoute' => 'admin.orders',
            'order' => $record,
        ]);
    }

    public function customerShow(string $customer): View
    {
        $record = AdvancedCommerceData::findCustomer($customer);

        abort_if($record === null, 404);

        return view('admin.advanced.customer-show', [
            'activeRoute' => 'admin.customers',
            'customer' => $record,
        ]);
    }

    public function inventory(): View
    {
        return view('admin.advanced.inventory', [
            'activeRoute' => 'admin.inventory',
            'inventory' => AdvancedCommerceData::inventory(),
        ]);
    }

    public function invoices(): View
    {
        return view('admin.advanced.invoices', [
            'activeRoute' => 'admin.invoices',
            'invoices' => AdvancedCommerceData::invoices(),
        ]);
    }

    public function plans(): View
    {
        return view('admin.advanced.plans', [
            'activeRoute' => 'admin.plans',
            'plans' => AdvancedCommerceData::plans(),
        ]);
    }

    public function roles(): View
    {
        return view('admin.advanced.roles', [
            'activeRoute' => 'admin.roles',
            'matrix' => AdvancedCommerceData::roles(),
        ]);
    }

    public function merchantModule(string $module): View
    {
        if ($module === 'orders') {
            return view('admin.merchant.orders', [
                'activeRoute' => 'admin.orders',
                'ordersDashboard' => AdvancedCommerceData::adminOrdersDashboard(request()),
            ]);
        }

        if ($module === 'products') {
            return view('admin.merchant.products', [
                'activeRoute' => 'admin.products',
                'productsDashboard' => AdvancedCommerceData::adminProductsDashboard(request()),
            ]);
        }

        if ($module === 'customers') {
            AdvancedCommerceData::customers();
        }

        $data = AdvancedCommerceData::merchantModule($module);

        return view('admin.merchant.module', [
            'activeRoute' => $data['activeRoute'],
            'module' => $data,
        ]);
    }

    public function globalSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json(AdvancedCommerceData::search((string) $request->query('q', '')));
    }

    public function enterpriseModule(string $module): View
    {
        $data = AdvancedCommerceData::enterpriseModule($module);

        return view('admin.advanced.enterprise-module', [
            'activeRoute' => $data['activeRoute'],
            'module' => $data,
        ]);
    }
}
