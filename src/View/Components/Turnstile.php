<?php

namespace LacruzWebDev\LivewireHtmlForms\View\Components;

use Illuminate\View\Component;
use InvalidArgumentException;

class Turnstile extends Component
{
  public string $id;
  public string $key;
  public string $theme;
  public string $size;
  public ?string $model;

  public function __construct(
    string $id = null,
    string $theme = null,
    string $size = null,
    array $attributes = []
  ) {
    $this->id = $id ?? config('livewire-html-forms.components.turnstile.default_id');
    $this->theme = $theme ?? config('livewire-html-forms.components.turnstile.default_theme');
    $this->size = $size ?? config('livewire-html-forms.components.turnstile.default_size');
    $this->key = config('livewire-html-forms.turnstile.key');

    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $this->id)) {
      throw new InvalidArgumentException(
        "The Turnstile ID [{$this->id}] must start with a letter or underscore, and can only contain alphanumeric or underscore characters."
      );
    }

    $this->model = $this->extractWireModel($attributes);
  }

  public function render()
  {
    return view('livewire-html-forms::components.turnstile');
  }

  private function extractWireModel(array $attributes): ?string
  {
    foreach ($attributes as $key => $value) {
      if (str_starts_with($key, 'wire:model')) {
        return $value;
      }
    }

    return null;
  }
}