<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffApproved
{
    /**
     * Blokir akses panel untuk staf admin/platform yang belum disetujui admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isStaff() && ! $user->isApproved()) {
            abort(403, 'Akun staf Anda belum disetujui oleh admin platform.');
        }

        return $next($request);
    }
}
