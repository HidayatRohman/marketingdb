<?php

namespace App\Policies;

use App\Models\Mitra;
use App\Models\User;

class MitraPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasFullAccess() || $user->hasReadOnlyAccess() || $user->hasLimitedAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Mitra $mitra): bool
    {
        if ($user->hasFullAccess() || $user->hasReadOnlyAccess()) {
            return true;
        }

        if ($user->hasLimitedAccess()) {
            return $mitra->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canCrud();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Mitra $mitra): bool
    {
        if ($user->canCrud()) {
            if ($user->hasFullAccess()) {
                return true;
            }
            
            if ($user->hasLimitedAccess()) {
                return $mitra->user_id === $user->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Mitra $mitra): bool
    {
        if ($user->canCrud()) {
            if ($user->hasFullAccess()) {
                return true;
            }
            
            if ($user->hasLimitedAccess()) {
                return $mitra->user_id === $user->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Mitra $mitra): bool
    {
        return $user->canCrud() && $user->hasFullAccess();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Mitra $mitra): bool
    {
        return $user->canCrud() && $user->hasFullAccess();
    }
}
