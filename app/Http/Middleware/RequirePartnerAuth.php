<?php

namespace App\Http\Middleware;

use App\Support\PartnerTenantStore;
use App\Support\SubscriptionManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequirePartnerAuth
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $user = PartnerTenantStore::currentUser($request);
        $partner = PartnerTenantStore::currentPartner($request);

        if (! $user || ! $partner) {
            $request->session()->put('partner.intended', $request->getRequestUri());

            return redirect()->route('partner.login');
        }

        $status = Str::lower((string) ($partner['status'] ?? ''));

        if (str_contains($status, 'موقوف')) {
            $request->session()->forget('partner_user');

            return redirect()
                ->route('partner.login')
                ->withErrors(['username' => 'هذا المتجر موقوف حالياً. تواصل مع الإدارة.']);
        }

        if ($ability && ! PartnerTenantStore::can($user, $ability)) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذا القسم.');
        }

        $decision = SubscriptionManager::accessDecision($partner, $request, $ability);

        if (! $decision['allowed']) {
            SubscriptionManager::recordDeniedAccess($partner, $user, $request, $decision);

            if ($request->expectsJson() || Str::startsWith($request->path(), 'api/partner')) {
                return response()->json([
                    'message' => $decision['message'],
                    'reason' => $decision['reason'],
                    'feature' => $decision['feature'],
                    'upgrade_prompt' => $decision['upgrade_prompt'],
                ], $decision['code']);
            }

            return redirect()
                ->route('partner.subscription')
                ->with('subscription_warning', $decision['message'])
                ->with('upgrade_prompt', $decision['upgrade_prompt']);
        }

        return $next($request);
    }
}
