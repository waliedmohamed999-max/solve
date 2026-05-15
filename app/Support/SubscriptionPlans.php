<?php

namespace App\Support;

class SubscriptionPlans
{
    public static function all(): array
    {
        return self::defaults();
    }

    public static function defaults(): array
    {
        return [
            'Free' => [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0,
                'yearly_price' => 0,
                'cycle' => 'monthly',
                'trial_days' => 0,
                'status' => 'active',
                'recommended' => false,
                'enterprise' => false,
                'free' => true,
                'features' => [
                    'لوحة تحكم أساسية',
                    'إضافة منتجات وتجربة الطلبات',
                    'كوبونات وتقييمات العملاء',
                    'طرق دفع أساسية',
                    'دومين فرعي مجاني',
                ],
                'feature_flags' => [
                    'pos' => false,
                    'apps' => false,
                    'apps_marketplace' => false,
                    'ai' => false,
                    'advanced_reports' => false,
                    'custom_domain' => false,
                    'real_payment_gateways' => false,
                    'shipping_integrations' => false,
                    'staff' => false,
                    'api_access' => false,
                    'automation' => false,
                ],
                'limits' => [
                    'products' => 10,
                    'orders' => 50,
                    'staff' => 1,
                    'branches' => 1,
                    'apps' => 0,
                    'channels' => 1,
                    'ai_requests' => 0,
                    'automations' => 0,
                ],
            ],
            'Starter' => [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 99,
                'yearly_price' => 990,
                'cycle' => 'monthly',
                'trial_days' => 14,
                'status' => 'active',
                'recommended' => false,
                'enterprise' => false,
                'features' => [
                    'كل خصائص باقة البداية',
                    'بوابات دفع حقيقية',
                    'ربط دومين خاص',
                    'ثيمات متجر احترافية',
                    'شحن يبدأ من 11 ريال للشحنة',
                    'POS مجاني على الباقة الأساسية',
                ],
                'feature_flags' => [
                    'pos' => false,
                    'apps' => true,
                    'apps_marketplace' => true,
                    'ai' => false,
                    'advanced_reports' => false,
                    'custom_domain' => false,
                    'real_payment_gateways' => true,
                    'shipping_integrations' => true,
                    'staff' => true,
                    'api_access' => false,
                    'automation' => false,
                ],
                'limits' => [
                    'products' => 100,
                    'orders' => 500,
                    'staff' => 3,
                    'branches' => 1,
                    'apps' => 4,
                    'channels' => 1,
                    'ai_requests' => 0,
                    'automations' => 0,
                ],
            ],
            'Growth' => [
                'name' => 'Growth',
                'slug' => 'growth',
                'price' => 299,
                'yearly_price' => 2990,
                'cycle' => 'monthly',
                'trial_days' => 14,
                'status' => 'active',
                'recommended' => true,
                'enterprise' => false,
                'features' => [
                    'كل خصائص باقة الانطلاقة',
                    'إدارة 5 مستخدمين إضافيين',
                    'إدارة المخزون عبر فرعين أو مستودعين',
                    'تقسيم العملاء لعروض حصرية',
                    'تقارير وتحليلات متقدمة',
                    'تخصيص الواجهة CSS',
                    'شحن يبدأ من 10.5 ريال للشحنة',
                ],
                'feature_flags' => [
                    'pos' => false,
                    'apps' => true,
                    'apps_marketplace' => true,
                    'ai' => false,
                    'advanced_reports' => true,
                    'custom_domain' => true,
                    'real_payment_gateways' => true,
                    'shipping_integrations' => true,
                    'staff' => true,
                    'api_access' => false,
                    'automation' => true,
                ],
                'limits' => [
                    'products' => 1000,
                    'orders' => 5000,
                    'staff' => 12,
                    'branches' => 3,
                    'apps' => 12,
                    'channels' => 3,
                    'ai_requests' => 0,
                    'automations' => 20,
                ],
            ],
            'Enterprise' => [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price' => null,
                'yearly_price' => null,
                'cycle' => 'annual',
                'trial_days' => 14,
                'status' => 'active',
                'recommended' => false,
                'enterprise' => true,
                'features' => [
                    'كل خصائص باقة النمو',
                    'أولوية الدعم 24/7',
                    'مدير علاقات تجار مخصص',
                    'POS مجاني بالكامل',
                    'صلاحية إدارة المتجر لـ 20 شخص إضافي',
                    'تقارير الربحية والتكاليف',
                    'ربط خارجي عبر API',
                ],
                'feature_flags' => [
                    'pos' => true,
                    'apps' => true,
                    'apps_marketplace' => true,
                    'ai' => true,
                    'advanced_reports' => true,
                    'custom_domain' => true,
                    'real_payment_gateways' => true,
                    'shipping_integrations' => true,
                    'staff' => true,
                    'api_access' => true,
                    'automation' => true,
                ],
                'limits' => [
                    'products' => 'unlimited',
                    'orders' => 'unlimited',
                    'staff' => 'unlimited',
                    'branches' => 'unlimited',
                    'apps' => 'unlimited',
                    'channels' => 'unlimited',
                    'ai_requests' => 'unlimited',
                    'automations' => 'unlimited',
                ],
            ],
        ];
    }

    public static function find(string $plan): array
    {
        return self::all()[$plan] ?? self::all()['Starter'];
    }

    public static function names(): array
    {
        return array_keys(self::all());
    }
}
