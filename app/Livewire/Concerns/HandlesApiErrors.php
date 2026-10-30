<?php

namespace App\Livewire\Concerns;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiValidationException;
use App\Services\Api\ParticipantToken;
use App\Services\TokenStorage;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Sentry\Severity;
use Sentry\State\Scope;

trait HandlesApiErrors
{
    protected function tryApiCall(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ApiAuthenticationException) {
            TokenStorage::signOut();

            return $this->redirect(route('login'));
        } catch (ApiValidationException $e) {
            if ($e->errors === [] && $this->endsWithSession() && $this->sessionIsOver()) {
                return $this->handleEndedSession();
            }

            if ($e->errors === []) {
                $this->dispatch('api-error', message: $e->getMessage());

                return null;
            }

            if (array_key_exists('participant_id', $e->errors)) {
                return $this->handleUnknownParticipant();
            }

            foreach ($e->errors as $field => $messages) {
                $this->reportUnmappedValidationError($field, $messages[0] ?? '');
                $this->addError($field, $messages[0]);
            }

            return null;
        } catch (ApiNotFoundException) {
            if ($this->endsWithSession() && $this->sessionIsOver()) {
                return $this->handleEndedSession();
            }

            abort(404);
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());

            return null;
        }
    }

    /**
     * Whether this screen belongs to a session that can end under the player.
     * There a 422 without field errors ("session is not active") or a 404 means
     * the session is over, not that something broke.
     */
    protected function endsWithSession(): bool
    {
        return false;
    }

    /**
     * A 422 without field errors or a 404 has other causes too (a player too far
     * from the checkpoint, a missing checkpoint), so ask the session itself.
     */
    private function sessionIsOver(): bool
    {
        try {
            $status = data_get($this->api->sessions()->show($this->code), 'data.status');
        } catch (ApiNotFoundException) {
            return true;
        } catch (ApiException) {
            return false;
        }

        return ! in_array($status, ['waiting', 'active'], true);
    }

    /**
     * The session ended under the player (host ended it, everyone left, or it
     * sat idle past the timeout): forget it, go home and say so in plain words.
     */
    protected function handleEndedSession(): null
    {
        session()->forget('questify_participant_id');
        ParticipantToken::forgetAll();
        session()->flash('session_ended', __('sessions.ended_under_you'));
        // The welcome screen would bounce a signed-in player on and use up the flash.
        $this->redirect(Auth::check() ? '/discover/list' : '/');

        return null;
    }

    /**
     * The API no longer knows this player's participant id (the session was
     * cleared, or it belongs to a session that is gone). Drop the stale id so
     * the next screen resolves it again, and tell the player in plain words.
     */
    private function handleUnknownParticipant(): null
    {
        session()->forget('questify_participant_id');
        ParticipantToken::forgetAll();
        $this->dispatch('api-error', message: __('errors.participant_unknown'));

        return null;
    }

    /**
     * A backend validation error on a field this screen cannot render is a gap
     * in our own form: the user sees a raw, untranslated API path like
     * "checkpoints.0.questions.0.answers.0.answer_text". Report it so the
     * missing client-side rule gets fixed instead of shipping the leak.
     */
    private function reportUnmappedValidationError(string $field, string $message): void
    {
        $root = strtok($field, '.');

        if ($root !== false && property_exists($this, $root)) {
            return;
        }

        $context = [
            'component' => static::class,
            'field' => $field,
            'message' => $message,
        ];

        Log::warning('Unmapped API validation error surfaced to the user', $context);

        if (app()->bound('sentry')) {
            \Sentry\withScope(function (Scope $scope) use ($context, $field): void {
                $scope->setContext('validation', $context);
                \Sentry\captureMessage('Unmapped API validation error: '.$field, Severity::warning());
            });
        }
    }
}
