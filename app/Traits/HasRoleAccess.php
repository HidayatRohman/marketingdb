<?php

namespace App\Traits;

trait HasRoleAccess
{
    /**
     * Check if user can perform CRUD operations
     * All roles except admin can perform CRUD operations
     */
    public function canCrud(): bool
    {
        return !$this->isAdmin();
    }

    /**
     * Check if user can only view data
     */
    public function canOnlyView(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can only view own data
     */
    public function canOnlyViewOwn(): bool
    {
        return $this->isMarketing();
    }

    /**
     * Check if user has full access to the system
     */
    public function hasFullAccess(): bool
    {
        return $this->isSuperAdmin() || $this->isAdvertiser();
    }

    /**
     * Check if user has read-only access
     */
    public function hasReadOnlyAccess(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user has limited access (own data only)
     */
    public function hasLimitedAccess(): bool
    {
        return $this->isMarketing();
    }

    /**
     * Get accessible user IDs for marketing users
     */
    public function getAccessibleUserIds(): array
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return [];
        }

        if ($this->isMarketing()) {
            return [$this->id];
        }

        return [];
    }

    /**
     * Apply role-based data filtering to a query
     */
    public function applyRoleFilter($query, string $userIdColumn = 'user_id')
    {
        if ($this->isMarketing()) {
            return $query->where($userIdColumn, $this->id);
        }

        return $query;
    }
}
