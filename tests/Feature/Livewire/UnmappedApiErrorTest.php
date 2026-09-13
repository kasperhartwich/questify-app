<?php

use App\Exceptions\Api\ApiAuthenticationException;
use App\Exceptions\Api\ApiException;
use App\Exceptions\Api\ApiNotFoundException;
use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
use App\Services\TokenStorage;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Livewire;

/**
 * A backend validation error on a field the screen cannot render leaks a raw,
 * untranslated path to the user — e.g.
 * "checkpoints.0.questions.0.answers.0.answer_text field is required".
 * That is a missing client-side rule, so it must be reported, not swallowed.
 */
class ErrorProbeComponent extends Component
{
    use HandlesApiErrors;

    public string $title = '';

    public function boom(array $errors): void
    {
        $this->tryApiCall(function () use ($errors) {
            throw new ApiValidationException('failed', $errors);
        });
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

/** Probes for each branch of tryApiCall(). */
class AuthFailureProbeComponent extends Component
{
    use HandlesApiErrors;

    public function boom(): void
    {
        TokenStorage::set('a-token');
        $this->tryApiCall(fn () => throw new ApiAuthenticationException('Unauthenticated.'));
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

class GenericFailureProbeComponent extends Component
{
    use HandlesApiErrors;

    public function boom(): void
    {
        $this->tryApiCall(fn () => throw new ApiException(500, 'Something went wrong'));
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

class NotFoundProbeComponent extends Component
{
    use HandlesApiErrors;

    public function boom(): void
    {
        $this->tryApiCall(fn () => throw new ApiNotFoundException('No such quest.'));
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

class PassthroughProbeComponent extends Component
{
    use HandlesApiErrors;

    public ?string $result = null;

    public function run(): void
    {
        $this->result = $this->tryApiCall(fn () => 'the value');
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

it('reports a backend validation error the form cannot show', function () {
    Log::spy();

    Livewire::test(ErrorProbeComponent::class)
        ->call('boom', ['checkpoints.0.questions.0.answers.0.answer_text' => ['The field is required.']]);

    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context = []) => $message === 'Unmapped API validation error surfaced to the user'
            && $context['field'] === 'checkpoints.0.questions.0.answers.0.answer_text')
        ->once();
});

it('stays quiet for an error the form can render inline', function () {
    Log::spy();

    Livewire::test(ErrorProbeComponent::class)
        ->call('boom', ['title' => ['The title field is required.']]);

    Log::shouldNotHaveReceived('warning');
});

it('shows the message against the field the form does have', function () {
    Livewire::test(ErrorProbeComponent::class)
        ->call('boom', ['title' => ['The title field is required.']])
        ->assertHasErrors(['title' => 'The title field is required.']);
});

it('reports a nested path but still shows it, so nothing is silently lost', function () {
    Log::spy();

    Livewire::test(ErrorProbeComponent::class)
        ->call('boom', ['checkpoints.0.title' => ['Required.']])
        ->assertHasErrors('checkpoints.0.title');
});

it('recognises a field by its root, not the whole path', function () {
    // "title.en" belongs to the $title the form renders; reporting it would be
    // noise, not a missing rule.
    Log::spy();

    Livewire::test(ErrorProbeComponent::class)->call('boom', ['title.en' => ['Required.']]);

    Log::shouldNotHaveReceived('warning');
});

it('signs the player out when the backend rejects the token', function () {
    Livewire::test(AuthFailureProbeComponent::class)
        ->call('boom')
        ->assertRedirect(route('login'));

    expect(TokenStorage::has())->toBeFalse();
});

it('shows an ordinary failure as a dialog rather than an error screen', function () {
    Livewire::test(GenericFailureProbeComponent::class)
        ->call('boom')
        ->assertDispatched('api-error');
});

it('turns a missing record into the app\'s own not-found screen', function () {
    // abort(404) is what routes the player to our branded screen instead of
    // letting the raw exception through.
    Livewire::test(NotFoundProbeComponent::class)->call('boom')->assertStatus(404);
});

it('passes a successful call straight through', function () {
    expect(Livewire::test(PassthroughProbeComponent::class)->call('run')->get('result'))
        ->toBe('the value');
});
