<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Cek izin Casbin sebelum request masuk ke route.
     *
     * Pemakaian di route: ->middleware('permission:depo,read')
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $key     key menu (sama dengan 'key' di config/navigation.php)
     * @param  string  $action  aksi yang dicek, default 'read'
     */
    public function handle(Request $request, Closure $next, string $key, string $action = 'read'): Response
    {
        $user = $request->user();
        $diizinkan = $user?->izin($key, $action) ?? false;
        abort_unless($diizinkan, 403, 'Anda tidak memiliki akses ke halaman ini. dd');

        return $next($request);
    }
}
