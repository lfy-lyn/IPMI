<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->member) {
            abort(403, 'Akses ditolak. Anda harus menjadi anggota.');
        }

        $member = $user->member;

        foreach ($roles as $role) {
            if ($member->hasPosition($role)) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak. Anda tidak memiliki jabatan yang diperlukan.');
    }
}