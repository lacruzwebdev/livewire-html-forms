<?php

namespace LacruzWebDev\LivewireHtmlForms\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use LacruzWebDev\LivewireHtmlForms\Services\TurnstileClient;

class Turnstile implements ValidationRule
{
  protected array $messages = [];

  public function __construct(
    protected TurnstileClient $turnstileClient,
  ) {
  }

  public function validate(string $attribute, mixed $value, \Closure $fail): void
  {
    if (!config('livewire-html-forms.turnstile.enabled')) {
      return;
    }

    $response = $this->turnstileClient->siteverify($value);

    foreach ($response->errorCodes as $errorCode) {
      $this->messages[] = match ($errorCode) {
        'missing-input-secret' => __('livewire-html-forms::turnstile.missing-input-secret'),
        'invalid-input-secret' => __('livewire-html-forms::turnstile.invalid-input-secret'),
        'missing-input-response' => __('livewire-html-forms::turnstile.missing-input-response'),
        'invalid-input-response' => __('livewire-html-forms::turnstile.invalid-input-response'),
        'bad-request' => __('livewire-html-forms::turnstile.bad-request'),
        'timeout-or-duplicate' => __('livewire-html-forms::turnstile.timeout-or-duplicate'),
        'internal-error' => __('livewire-html-forms::turnstile.internal-error'),
        'network-error' => __('livewire-html-forms::turnstile.network-error'),
        default => __('livewire-html-forms::turnstile.unexpected'),
      };
    }

    if (!$response->success) {
      if (config('livewire-html-forms.forms.log_errors')) {
        logger()->error('Turnstile validation failed', [
          'error_codes' => $response->errorCodes,
          'attribute' => $attribute,
        ]);
      }

      $fail(__('livewire-html-forms::turnstile.validation-failed'));
    }
  }

  public function message(): array
  {
    return $this->messages;
  }
}