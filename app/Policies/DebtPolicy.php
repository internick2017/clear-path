<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DebtPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own debts
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create debts
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can record payments on the debt.
     */
    public function recordPayment(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can mark the debt as paid.
     */
    public function markAsPaid(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }

    /**
     * Determine whether the user can mark the debt as active.
     */
    public function markAsActive(User $user, Debt $debt): bool
    {
        return $user->id === $debt->user_id;
    }
}
