<?php

namespace App\Http\Controllers;

use App\Support\PlatformAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (session('admin_authenticated')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! $this->credentialsAreValid($credentials['username'], $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'بيانات الدخول غير صحيحة.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put('admin_authenticated', true);
        PlatformAudit::activity('login', 'admin', $credentials['username'], ['role' => 'super_admin'], $request);

        $intended = $request->session()->pull('url.intended', route('admin.dashboard'));

        return redirect()->to($this->safeRedirectTarget($intended, route('admin.dashboard')));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');
        PlatformAudit::activity('logout', 'admin', null, ['role' => 'super_admin'], $request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function credentialsAreValid(string $username, string $password): bool
    {
        $expectedUsername = (string) config('admin.username');
        $expectedPassword = (string) config('admin.password');
        $expectedPasswordHash = (string) config('admin.password_hash');

        if ($expectedUsername === '' || ($expectedPassword === '' && $expectedPasswordHash === '')) {
            return false;
        }

        if (! hash_equals($expectedUsername, $username)) {
            return false;
        }

        if ($expectedPasswordHash !== '') {
            return Hash::check($password, $expectedPasswordHash);
        }

        if (app()->environment('production')) {
            return false;
        }

        return hash_equals($expectedPassword, $password);
    }

    private function safeRedirectTarget(mixed $target, string $fallback): string
    {
        $target = is_string($target) ? $target : '';

        return str_starts_with($target, '/') && ! str_starts_with($target, '//') ? $target : $fallback;
    }
}
