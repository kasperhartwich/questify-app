<?php

namespace App\Livewire\Concerns;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiValidationException;
use App\Services\TokenStorage;
use Closure;
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
            foreach ($e->errors as $field => $messages) {
                $this->reportUnmappedValidationError($field, $messages[0] ?? '');
                $this->addError($field, $messages[0]);
            }

            return null;
        } catch (ApiNotFoundException) {
            abort(404);
        } catch (ApiException $e) {
            $this->dispatch('api-error', message: $e->getMessage());

            return null;
        }
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
