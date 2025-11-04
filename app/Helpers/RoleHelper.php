<?php

namespace App\Helpers;

class RoleHelper
{
    /**
     * Get role-based permissions for frontend
     */
    public static function getPermissions($user): array
    {
        return [
            'canCrud' => $user->canCrud(),
            'canOnlyView' => $user->canOnlyView(),
            'canOnlyViewOwn' => $user->canOnlyViewOwn(),
            'hasFullAccess' => $user->hasFullAccess(),
            'hasReadOnlyAccess' => $user->hasReadOnlyAccess(),
            'hasLimitedAccess' => $user->hasLimitedAccess(),
            'role' => $user->role,
            'roleLabel' => $user->role_label,
        ];
    }

    /**
     * Get navigation items based on user role
     */
    public static function getNavigationItems($user): array
    {
        $baseItems = [];
        // Only non-CS roles see Dashboard in navigation
        if (!$user->isCs()) {
            $baseItems[] = [
                'name' => 'Dashboard',
                'href' => route('dashboard'),
                'icon' => 'home',
                'visible' => true,
            ];
        }

        if ($user->hasFullAccess() || $user->hasReadOnlyAccess() || $user->hasLimitedAccess()) {
            $baseItems[] = [
                'name' => 'Mitra',
                'href' => route('mitras.index'),
                'icon' => 'users',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];
        }

        if ($user->hasFullAccess() || $user->hasReadOnlyAccess()) {
            $baseItems[] = [
                'name' => 'Users',
                'href' => route('users.index'),
                'icon' => 'user',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];

            $baseItems[] = [
                'name' => 'Brands',
                'href' => route('brands.index'),
                'icon' => 'tag',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];

            $baseItems[] = [
                'name' => 'Labels',
                'href' => route('labels.index'),
                'icon' => 'bookmark',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];
        }

        // Navigation for CS role: only show CS menus and Products
        if ($user->isCs()) {
            $baseItems[] = [
                'name' => 'CS Repeat',
                'href' => route('cs-repeats.index'),
                'icon' => 'repeat',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];
            $baseItems[] = [
                'name' => 'CS Maintenance',
                'href' => route('cs-maintenances.index'),
                'icon' => 'wrench',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];
            $baseItems[] = [
                'name' => 'Products',
                'href' => route('products.index'),
                'icon' => 'box',
                'visible' => true,
                'actions' => [
                    'create' => $user->canCrud(),
                    'edit' => $user->canCrud(),
                    'delete' => $user->canCrud(),
                ],
            ];
        }

        return array_filter($baseItems, function ($item) {
            return $item['visible'];
        });
    }

    /**
     * Check if user can access specific resource
     */
    public static function canAccessResource($user, string $resource, string $action = 'view'): bool
    {
        switch ($action) {
            case 'create':
            case 'store':
            case 'edit':
            case 'update':
            case 'destroy':
                return $user->canCrud();
                
            case 'view':
            case 'index':
            case 'show':
                return $user->hasFullAccess() || $user->hasReadOnlyAccess() || $user->hasLimitedAccess();
                
            default:
                return false;
        }
    }

    /**
     * Get accessible data scope for user
     */
    public static function getDataScope($user): string
    {
        if ($user->hasFullAccess()) {
            return 'all';
        }
        
        if ($user->hasReadOnlyAccess()) {
            return 'readonly_all';
        }
        
        if ($user->hasLimitedAccess()) {
            return 'own_only';
        }
        
        return 'none';
    }
}
