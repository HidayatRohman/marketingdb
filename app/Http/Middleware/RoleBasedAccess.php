<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission = 'view'): Response
    {
        if (!auth()->check()) {
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        switch ($permission) {
            case 'create':
            case 'store':
            case 'edit':
            case 'update':
            case 'destroy':
                // Only Super Admin can perform CRUD operations
                if (!$user->canCrud()) {
                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'], 403);
                    }
                    abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
                }
                break;
                
            case 'view':
            case 'index':
            case 'show':
                // All roles can view, but with different data scope
                if (!$user->hasFullAccess() && !$user->hasReadOnlyAccess() && !$user->hasLimitedAccess()) {
                    if ($request->wantsJson() || $request->expectsJson()) {
                        return response()->json(['error' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'], 403);
                    }
                    abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
                }
                break;
                
            default:
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json(['error' => 'Invalid permission type.'], 403);
                }
                abort(403, 'Invalid permission type.');
        }

        return $next($request);
    }
}
