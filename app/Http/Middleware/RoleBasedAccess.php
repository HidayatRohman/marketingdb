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
        $routeName = optional($request->route())->getName();
        
        // Special handling for CS role: restrict access to CS menus and Products only
        if ($user->isCs()) {
            $allowedPrefixes = ['cs-repeats.', 'cs-maintenances.', 'products.'];
            $isAllowed = false;

            switch ($permission) {
                case 'create':
                case 'store':
                case 'edit':
                case 'update':
                case 'destroy':
                    foreach ($allowedPrefixes as $prefix) {
                        if (is_string($routeName) && str_starts_with($routeName, $prefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }
                    break;
                case 'view':
                case 'index':
                case 'show':
                    // CS can view Dashboard and CS/Products pages
                    if ($routeName === 'dashboard') {
                        $isAllowed = true;
                        break;
                    }
                    foreach ($allowedPrefixes as $prefix) {
                        if (is_string($routeName) && str_starts_with($routeName, $prefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }
                    break;
                default:
                    $isAllowed = false;
            }

            if (!$isAllowed) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json(['error' => 'Anda tidak memiliki izin untuk mengakses halaman ini.'], 403);
                }
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }

            // Allowed for CS: proceed
            return $next($request);
        }

        // Restrict marketing role from accessing CS data (CS Repeat, CS Maintenance, Products)
        if ($user->isMarketing()) {
            $restrictedPrefixes = ['cs-repeats.', 'cs-maintenances.', 'products.'];
            $isRestricted = false;

            foreach ($restrictedPrefixes as $prefix) {
                if (is_string($routeName) && str_starts_with($routeName, $prefix)) {
                    $isRestricted = true;
                    break;
                }
            }

            if ($isRestricted) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json(['error' => 'Anda tidak memiliki izin untuk mengakses data CS.'], 403);
                }
                abort(403, 'Anda tidak memiliki izin untuk mengakses data CS.');
            }
        }

        switch ($permission) {
            case 'create':
            case 'store':
            case 'edit':
            case 'update':
            case 'destroy':
                // Only users with CRUD permission can perform these operations
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
