<?php

namespace App\Policies;

use App\Models\CampusEvent;
use App\Models\User;

class CampusEventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, CampusEvent $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    public function update(User $user, CampusEvent $event): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }

    public function delete(User $user, CampusEvent $event): bool
    {
        return $user->isTeacher() || $user->isAdmin();
    }
}
