<?php

namespace App\Livewire\Concerns;

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiValidationException;
use App\Services\TokenStorage;
use Closure;

trait HandlesApiErrors
{
    protected function tryApiCall(Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (ApiAuthenticationException) {
            TokenStorage::forget();
            session()->flush();

            return $this->redirect(route('login'));
        } catch (ApiValidationException $e) {
            foreach ($e->errors as $field => $messages) {
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
}
