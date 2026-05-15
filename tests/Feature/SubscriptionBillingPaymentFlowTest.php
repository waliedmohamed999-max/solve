<?php

namespace Tests\Feature;

use App\Models\PartnerStore;
use App\Models\PartnerUser;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SubscriptionBillingPaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_upgrade_creates_invoice_payment_and_trusted_webhook_marks_paid(): void
    {
        $store = $this->createStore(['plan' => 'Starter']);
        $user = $this->createPartnerUser($store);
        $this->loginPartner($user, $store);

        $this->postJson('/api/partner/subscription/upgrade', ['plan' => 'Growth', 'cycle' => 'monthly'])
            ->assertOk()
            ->assertJsonPath('subscription.plan_name', 'Growth');

        $invoice = PlatformRecord::query()->where('section', 'subscription_invoices')->where('store_id', $store->store_id)->firstOrFail();
        $payment = PlatformRecord::query()->where('section', 'subscription_payments')->where('store_id', $store->store_id)->firstOrFail();
        $this->assertSame('pending', $invoice->status);
        $this->assertSame('pending', $payment->status);
        $this->assertStringStartsWith('SUB-', $invoice->payload['invoice_number']);

        $event = ['type' => 'payment_success', 'data' => ['invoice_id' => $invoice->record_id, 'store_id' => $store->store_id, 'payment_reference' => 'pay_123']];
        $this->postSignedWebhook($event)->assertOk()->assertJsonPath('type', 'payment_success');

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $store->fresh()->payment_status);
        $this->assertTrue(PlatformActivityLog::query()->where('action', 'billing.webhook.payment_success')->where('store_id', $store->store_id)->exists());
        $this->assertTrue(PlatformNotification::query()->where('type', 'billing_success')->where('store_id', $store->store_id)->exists());

        $this->get('/api/partner/invoices/' . $invoice->record_id . '/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_failed_payment_retry_refund_and_signature_security(): void
    {
        $store = $this->createStore(['store_id' => 'store-billing', 'partner_id' => 'billing', 'plan' => 'Growth']);
        $user = $this->createPartnerUser($store);
        $this->loginPartner($user, $store);
        $this->postJson('/api/partner/subscription/renew')->assertOk();
        $invoice = PlatformRecord::query()->where('section', 'subscription_invoices')->where('store_id', $store->store_id)->firstOrFail();

        $event = ['type' => 'payment_failed', 'data' => ['invoice_id' => $invoice->record_id, 'store_id' => $store->store_id, 'reason' => 'insufficient_funds']];
        $this->withHeaders(['X-Solve-Signature' => 'bad'])->postJson('/webhooks/billing', $event)->assertUnauthorized();
        $this->postSignedWebhook($event)->assertOk()->assertJsonPath('type', 'payment_failed');

        $this->assertSame('failed', $invoice->fresh()->status);
        $this->assertSame('past_due', $store->fresh()->status);

        $this->postJson('/api/partner/invoices/' . $invoice->record_id . '/retry')
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->loginAsAdmin()->postJson('/api/admin/invoices/' . $invoice->record_id . '/refund')
            ->assertOk()
            ->assertJsonPath('status', 'refunded');
    }

    private function postSignedWebhook(array $event)
    {
        $raw = json_encode($event);

        return $this->withHeaders([
            'X-Solve-Signature' => hash_hmac('sha256', $raw, (string) config('services.billing.webhook_secret')),
        ])->postJson('/webhooks/billing', $event);
    }

    private function createStore(array $overrides = []): PartnerStore
    {
        return PartnerStore::query()->create(array_merge([
            'partner_id' => 'atlas',
            'store_id' => 'store-atlas',
            'name' => 'Atlas Store',
            'brand_name' => 'Atlas Store',
            'owner_name' => 'Sara',
            'owner_email' => 'sara@example.test',
            'owner_phone' => '+966500000000',
            'status' => 'active',
            'plan' => 'Growth',
            'payment_status' => 'paid',
            'subscription_started_at' => now()->subMonth()->toDateString(),
            'subscription_renews_at' => now()->addMonth()->toDateString(),
        ], $overrides));
    }

    private function createPartnerUser(PartnerStore $store): PartnerUser
    {
        return PartnerUser::query()->create([
            'partner_store_id' => $store->id,
            'store_id' => $store->store_id,
            'name' => 'Store Owner',
            'username' => 'owner-' . $store->store_id . '@example.test',
            'email' => 'owner-' . $store->store_id . '@example.test',
            'password_hash' => Hash::make('StrongPass2026'),
            'role' => 'partner_admin',
            'status' => 'active',
            'abilities' => ['*'],
        ]);
    }

    private function loginPartner(PartnerUser $user, PartnerStore $store): void
    {
        $this->withSession(['partner_user' => [
            'id' => $user->id,
            'store_id' => $store->store_id,
            'role' => 'partner_admin',
            'username' => $user->username,
            'name' => $user->name,
        ], 'admin_authenticated' => false]);
    }
}
