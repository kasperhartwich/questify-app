<?php

use App\Exceptions\Api\ApiValidationException;
use App\Livewire\Concerns\HandlesApiErrors;
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
