<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\BusinessManager;
use App\Models\User;

class BusinessManagerPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function view(Admin|User $actor, BusinessManager $businessManager): bool
    {
        return $actor instanceof Admin;
    }

    public function create(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function update(Admin|User $actor, BusinessManager $businessManager): bool
    {
        return $actor instanceof Admin;
    }

    public function delete(Admin|User $actor, BusinessManager $businessManager): bool
    {
        return $actor instanceof Admin;
    }

    public function restore(Admin|User $actor, BusinessManager $businessManager): bool
    {
        return $actor instanceof Admin;
    }

    public function forceDelete(Admin|User $actor, BusinessManager $businessManager): bool
    {
        return $actor instanceof Admin;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }
}
