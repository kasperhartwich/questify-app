<?php

use App\Models\QuestSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('session.{code}', function ($user, $code) {
    $session = QuestSession::where('join_code', $code)->first();

    if (! $session) {
        return false;
    }

    $isHost = $session->host_id === $user->id;
    $isParticipant = $session->participants()->where('user_id', $user->id)->exists();

    if ($isHost || $isParticipant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    return false;
});

Broadcast::channel('session.{code}.host', function ($user, $code) {
    $session = QuestSession::where('join_code', $code)->first();

    return $session && $session->host_id === $user->id;
});
