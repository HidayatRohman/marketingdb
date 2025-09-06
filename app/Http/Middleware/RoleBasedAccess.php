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
                    abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
                }
                break;
                
            case 'view':
            case 'index':
            case 'show':
                // All roles can view, but with different data scope
                if (!$user->hasFullAccess() && !$user->hasReadOnlyAccess() && !$user->hasLimitedAccess()) {
                    abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
                }
                break;
                
            default:
                abort(403, 'Invalid permission type.');
        }

        return $next($request);
    }
}
