<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('admin_authenticated', false)) {
            $request->session()->put('url.intended', $request->getRequestUri());

            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
