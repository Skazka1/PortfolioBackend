<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateAsAdmin(User $auth, User $model): bool
    {
        return $auth->isAdmin();
    }

    public function viewInDirectory(?User $user, User $model): bool
    {
        return $model->isStudent() && $model->is_active;
    }

    public function viewPortfolio(?User $user, User $student): bool
    {
        return $student->isStudent() && $student->is_active;
    }

    public function updateProfile(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id
            && ($user->isStudent() || $user->isTeacher());
    }

    public function importProjectFromPdf(User $user, User $model): bool
    {
        return (int) $user->id === (int) $model->id && $user->isStudent();
    }

    public function downloadPortfolioPdf(User $viewer, User $student): bool
    {
        if (! $student->isStudent() || ! $student->is_active) {
            return false;
        }
        if ($viewer->isTeacher() || $viewer->isAdmin()) {
            return true;
        }

        return $viewer->isStudent() && (int) $viewer->id === (int) $student->id;
    }
}
