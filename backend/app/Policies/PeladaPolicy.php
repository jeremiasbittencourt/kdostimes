<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pelada;
use App\Models\User;

class PeladaPolicy
{
    public function view(User $user, Pelada $pelada): bool
    {
        return $pelada->hasMember($user);
    }

    public function update(User $user, Pelada $pelada): bool
    {
        return $pelada->isAdmin($user);
    }

    public function manageMembers(User $user, Pelada $pelada): bool
    {
        return $pelada->isAdmin($user);
    }

    public function removeMember(User $user, Pelada $pelada, User $target): bool
    {
        if (! $pelada->isAdmin($user)) {
            return false;
        }

        return $target->id !== $pelada->admin_id;
    }

    public function configureVoting(User $user, Pelada $pelada): bool
    {
        return $pelada->isAdmin($user);
    }

    public function drawTeams(User $user, Pelada $pelada): bool
    {
        return $pelada->isAdmin($user);
    }
}
