<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            ['label' => 'إجمالي المتاجر', 'value' => '12,480', 'change' => '+18.4%', 'tone' => 'primary'],
            ['label' => 'المتاجر النشطة', 'value' => '10,932', 'change' => '+12.1%', 'tone' => 'success'],
            ['label' => 'المتاجر المتوقفة', 'value' => '274', 'change' => '-4.3%', 'tone' => 'danger'],
            ['label' => 'التجار الجدد', 'value' => '486', 'change' => '+21.8%', 'tone' => 'info'],
            ['label' => 'إجمالي الطلبات', 'value' => '248K', 'change' => '+16.7%', 'tone' => 'primary'],
            ['label' => 'الإيرادات الشهرية', 'value' => '3.84M ر.س', 'change' => '+9.2%', 'tone' => 'success'],
            ['label' => 'معدل النمو', 'value' => '24.6%', 'change' => '+3.1%', 'tone' => 'info'],
            ['label' => 'تذاكر الدعم المفتوحة', 'value' => '92', 'change' => '-11.5%', 'tone' => 'warning'],
        ];

        $stores = [
            ['name' => 'متجر أطلس', 'owner' => 'سارة الحربي', 'status' => 'نشط', 'plan' => 'Enterprise', 'sales' => '418,200 ر.س', 'orders' => '2,418', 'created_at' => '15 يناير 2026'],
            ['name' => 'أبعاد بيوتي', 'owner' => 'هدى القحطاني', 'status' => 'معلق', 'plan' => 'Growth', 'sales' => '126,540 ر.س', 'orders' => '1,109', 'created_at' => '09 فبراير 2026'],
            ['name' => 'رواء هوم', 'owner' => 'محمد الشهري', 'status' => 'نشط', 'plan' => 'Starter', 'sales' => '88,200 ر.س', 'orders' => '704', 'created_at' => '21 ديسمبر 2025'],
            ['name' => 'شهد بوتيك', 'owner' => 'نوف العتيبي', 'status' => 'مراجعة', 'plan' => 'Growth', 'sales' => '213,900 ر.س', 'orders' => '1,680', 'created_at' => '03 مارس 2026'],
        ];

        $plans = [
            ['name' => 'Starter', 'price' => '79 ر.س', 'subs' => '2,140', 'revenue' => '169K ر.س'],
            ['name' => 'Growth', 'price' => '299 ر.س', 'subs' => '5,320', 'revenue' => '1.59M ر.س'],
            ['name' => 'Enterprise', 'price' => '899 ر.س', 'subs' => '1,280', 'revenue' => '1.15M ر.س'],
        ];

        $payments = [
            ['name' => 'مدى', 'success' => '98.2%', 'failed' => '1.8%', 'refunds' => '48 طلب'],
            ['name' => 'Apple Pay', 'success' => '97.4%', 'failed' => '2.6%', 'refunds' => '27 طلب'],
            ['name' => 'Visa', 'success' => '96.9%', 'failed' => '3.1%', 'refunds' => '34 طلب'],
            ['name' => 'Mastercard', 'success' => '96.3%', 'failed' => '3.7%', 'refunds' => '22 طلب'],
        ];

        $shippingPartners = [
            ['name' => 'أرامكس', 'deliveries' => '12,840', 'delay' => '2.4%', 'score' => '4.8/5'],
            ['name' => 'سمسا', 'deliveries' => '9,412', 'delay' => '3.1%', 'score' => '4.5/5'],
            ['name' => 'البريد السعودي', 'deliveries' => '7,105', 'delay' => '4.2%', 'score' => '4.2/5'],
        ];

        $tickets = [
            ['title' => 'تأخير مزامنة المخزون', 'type' => 'تقني', 'priority' => 'عالية', 'assignee' => 'فريق المنصة', 'status' => 'قيد المعالجة'],
            ['title' => 'فشل استرجاع مبلغ', 'type' => 'دفع', 'priority' => 'حرجة', 'assignee' => 'فريق المدفوعات', 'status' => 'تصعيد'],
            ['title' => 'خطأ في صفحة المنتج', 'type' => 'متجر', 'priority' => 'متوسطة', 'assignee' => 'فريق الدعم', 'status' => 'بانتظار التاجر'],
            ['title' => 'خلل في تكامل ERP', 'type' => 'تكاملات', 'priority' => 'عالية', 'assignee' => 'فريق الشركاء', 'status' => 'قيد التنفيذ'],
        ];

        $services = [
            ['name' => 'التصميم', 'requests' => '86 طلب', 'sla' => '2.1 يوم'],
            ['name' => 'التسويق', 'requests' => '51 طلب', 'sla' => '1.7 يوم'],
            ['name' => 'التغليف', 'requests' => '34 طلب', 'sla' => '3.4 يوم'],
            ['name' => 'التصوير', 'requests' => '19 طلب', 'sla' => '4.0 يوم'],
        ];

        return view('admin.dashboard', compact(
            'stats',
            'stores',
            'plans',
            'payments',
            'shippingPartners',
            'tickets',
            'services'
        ));
    }
}