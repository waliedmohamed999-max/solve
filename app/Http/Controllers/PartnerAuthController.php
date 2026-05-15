<?php

namespace App\Http\Controllers;

use App\Support\PartnerTenantStore;
use App\Support\PlatformAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (PartnerTenantStore::currentUser($request)) {
            return redirect()->route('partner.dashboard');
        }

        $request->session()->regenerateToken();

        return view('partner.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = PartnerTenantStore::authenticate($credentials['username'], $credentials['password']);

        if (! $user) {
            return back()
                ->withErrors(['username' => 'بيانات الدخول غير صحيحة.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('partner_user', $user);
        PlatformAudit::activity('login', 'partner_user', $user['username'], [
            'role' => $user['role'],
            'store_id' => $user['store_id'],
            'partner_id' => $user['partner_id'],
        ], $request);

        $intended = $request->session()->pull('partner.intended', route('partner.dashboard'));

        return redirect()->to($this->safeRedirectTarget($intended, route('partner.dashboard')));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = PartnerTenantStore::currentUser($request);
        PlatformAudit::activity('logout', 'partner_user', $user['username'] ?? null, [
            'role' => $user['role'] ?? null,
            'store_id' => $user['store_id'] ?? null,
            'partner_id' => $user['partner_id'] ?? null,
        ], $request);
        $request->session()->forget('partner_user');
        $request->session()->regenerateToken();

        return redirect()->route('partner.login');
    }

    private function safeRedirectTarget(mixed $target, string $fallback): string
    {
        $target = is_string($target) ? $target : '';

        return str_starts_with($target, '/') && ! str_starts_with($target, '//') ? $target : $fallback;
    }
}
