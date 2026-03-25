<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\GlobalRateBucket;
use App\Models\User;

class GlobalRateBucketPolicy
{
    public function viewAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function view(Admin|User $actor, GlobalRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function create(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }

    public function update(Admin|User $actor, GlobalRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function delete(Admin|User $actor, GlobalRateBucket $model): bool
    {
        return $actor instanceof Admin;
    }

    public function deleteAny(Admin|User $actor): bool
    {
        return $actor instanceof Admin;
    }
}
