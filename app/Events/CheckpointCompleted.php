<?php

namespace App\Events;

use App\Models\Checkpoint;
use App\Models\SessionParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckpointCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionCode,
        public SessionParticipant $participant,
        public Checkpoint $checkpoint,
    ) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("session.{$this->sessionCode}"),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'participant_id' => $this->participant->id,
            'display_name' => $this->participant->display_name,
            'checkpoint_id' => $this->checkpoint->id,
            'checkpoint_title' => $this->checkpoint->title,
            'score' => $this->participant->score,
        ];
    }
}
