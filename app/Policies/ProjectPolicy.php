<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent() || $user->isAdmin();
    }

    public function view(?User $user, Project $project): bool
    {
        if (! $user) {
            return $project->is_published;
        }
        if ($user->isTeacher() || $user->isAdmin()) {
            return true;
        }
        if ($user->isStudent()) {
            return $project->is_published || $project->isParticipantOf($user);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStudent() || $user->isAdmin();
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (! $user->isStudent()) {
            return false;
        }

        return $project->isParticipantOf($user);
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (! $user->isStudent()) {
            return false;
        }

        return $project->isParticipantOf($user);
    }

    public function toggleLike(User $user, Project $project): bool
    {
        if ($project->isParticipantOf($user)) {
            return false;
        }
        if (! $user->is_active) {
            return false;
        }

        return $user->isStudent() || $user->isTeacher() || $user->isAdmin();
    }

    public function moderate(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /** Экспорт одного проекта в PDF (участник или сотрудник кафедры). */
    public function downloadPdf(User $user, Project $project): bool
    {
        if ($user->isTeacher() || $user->isAdmin()) {
            return true;
        }

        return $user->isStudent() && $project->isParticipantOf($user);
    }
}
