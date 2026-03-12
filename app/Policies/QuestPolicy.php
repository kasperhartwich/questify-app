<?php

namespace App\Policies;

use App\Models\Quest;
use App\Models\User;

class QuestPolicy
{
    public function update(User $user, Quest $quest): bool
    {
        return $user->id === $quest->creator_id;
    }

    public function delete(User $user, Quest $quest): bool
    {
        return $user->id === $quest->creator_id;
    }

    public function publish(User $user, Quest $quest): bool
    {
        return $user->id === $quest->creator_id;
    }

    public function rate(User $user, Quest $quest): bool
    {
        return $user->id !== $quest->creator_id;
    }

    public function flag(User $user, Quest $quest): bool
    {
        return $user->id !== $quest->creator_id;
    }
}
