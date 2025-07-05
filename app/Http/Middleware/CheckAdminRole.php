<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         // 1. Cek apakah user sudah login DAN rolenya adalah 'admin'.
        //    auth()->user() akan mengambil data user yang sedang login.
        if ($request->user() && $request->user()->role === 'admin') {
            
            // 2. Jika user adalah admin, izinkan untuk melanjutkan ke halaman berikutnya.
            return $next($request);
        }

        // 3. Jika user tidak login atau rolenya bukan 'admin', tolak akses.
        //    abort(403) akan menampilkan halaman error "403 Forbidden".
        abort(403, 'ANDA TIDAK MEMILIKI HAK AKSES.');
    }
}
