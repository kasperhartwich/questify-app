<?php

namespace App\Services;

use App\Models\QuestSession;
use App\Models\SessionParticipant;
use Illuminate\Http\Request;

/**
 * Who may act as a session participant, and who may see a session's players.
 *
 * A participant proves itself with the token join handed out (header
 * X-Participant-Token), or by being the signed-in account it joined with.
 * Join hands a token only to builds that send it back (X-App-Build at least
 * questify.participant_token_min_build). Older builds join without one and
 * play on the participant id alone until questify.require_participant_token
 * is switched on. A participant that has a token always needs it, so its id
 * never falls back to that, and a token that does not match is always refused.
 */
class ParticipantAccess
{
    public const HEADER = 'X-Participant-Token';

    public function required(): bool
    {
        return (bool) config('questify.require_participant_token');
    }

    public function mayActAs(Request $request, SessionParticipant $participant): bool
    {
        $token = $this->token($request);

        if ($token !== null) {
            return $participant->matchesToken($token);
        }

        if ($participant->user_id !== null && $participant->user_id === $request->user('sanctum')?->id) {
            return true;
        }

        return ! $participant->hasToken() && ! $this->required();
    }

    /**
     * Whether join should hand this client a token: only a build that sends it
     * back on the gameplay calls. An older build, or a client without a build
     * header, would never send it and lock itself out.
     */
    public function issuesTokenTo(Request $request): bool
    {
        $build = $request->header('X-App-Build');

        return is_numeric($build) && (int) $build >= (int) config('questify.participant_token_min_build');
    }

    /**
     * The caller's own participant in the session: by token, by signed-in
     * account, or (older builds) by the participant_id query parameter.
     */
    public function viewer(Request $request, QuestSession $session): ?SessionParticipant
    {
        $token = $this->token($request);

        if ($token !== null) {
            return $session->participants->first(fn (SessionParticipant $p): bool => $p->matchesToken($token));
        }

        $userId = $request->user('sanctum')?->id;
        $own = $userId ? $session->participants->firstWhere('user_id', $userId) : null;

        if ($own) {
            return $own;
        }

        $claimed = $request->integer('participant_id')
            ? $session->participants->firstWhere('id', $request->integer('participant_id'))
            : null;

        return $claimed && $this->mayActAs($request, $claimed) ? $claimed : null;
    }

    public function isHost(Request $request, QuestSession $session): bool
    {
        $userId = $request->user('sanctum')?->id;

        return $userId !== null && $session->host_id === $userId;
    }

    /**
     * The player list and scores are for the host and the session's own
     * players. While tokens are not required they stay public, because the
     * builds without tokens read them to find their own participant.
     */
    public function maySeePlayers(Request $request, QuestSession $session): bool
    {
        return $this->isHost($request, $session)
            || $this->viewer($request, $session) !== null
            || ! $this->required();
    }

    private function token(Request $request): ?string
    {
        $token = $request->header(self::HEADER);

        return is_string($token) && $token !== '' ? $token : null;
    }
}
