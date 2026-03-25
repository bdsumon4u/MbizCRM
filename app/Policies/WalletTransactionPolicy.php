<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;
use App\Models\WalletTransaction;

class WalletTransactionPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return true;
    }

    public function view(Admin|User $actor, WalletTransaction $walletTransaction): bool
    {
        if ($actor instanceof Admin) {
            return true;
        }

        return $walletTransaction->user_id === $actor->id;
    }

    public function create(Admin|User $actor): bool
    {
        return false;
    }

    public function update(Admin|User $actor, WalletTransaction $walletTransaction): bool
    {
        return false;
    }

    public function delete(Admin|User $actor, WalletTransaction $walletTransaction): bool
    {
        return false;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return false;
    }
}
