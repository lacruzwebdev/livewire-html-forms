<?php

namespace LacruzWebDev\LivewireHtmlForms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use LacruzWebDev\LivewireHtmlForms\Services\TurnstileClient;
use LacruzWebDev\LivewireHtmlForms\View\Components\Turnstile;

class LivewireHtmlFormsServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    // Merge configuration
    $this->mergeConfigFrom(
      __DIR__ . '/../../config/livewire-html-forms.php',
      'livewire-html-forms'
    );

    // Register TurnstileClient singleton
    $this->app->singleton(TurnstileClient::class, function ($app) {
      return new TurnstileClient(
        config('livewire-html-forms.turnstile.secret_key'),
        config('livewire-html-forms.turnstile.endpoint'),
        config('livewire-html-forms.turnstile.retry_attempts'),
        config('livewire-html-forms.turnstile.retry_delay')
      );
    });

    // Register Blade components
    $this->loadViewComponentsAs('livewire-html-forms', [
      'turnstile' => Turnstile::class,
    ]);
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Publish configuration
    $this->publishes([
      __DIR__ . '/../../config/livewire-html-forms.php' => config_path('livewire-html-forms.php'),
    ], 'livewire-html-forms-config');

    // Publish views
    $this->publishes([
      __DIR__ . '/../../resources/views' => resource_path('views/vendor/livewire-html-forms'),
    ], 'livewire-html-forms-views');

    // Publish translations
    $this->publishes([
      __DIR__ . '/../../resources/lang' => resource_path('lang/vendor/livewire-html-forms'),
    ], 'livewire-html-forms-lang');

    // Load views
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'livewire-html-forms');

    // Load translations
    $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'livewire-html-forms');

    // Register Blade directives
    $this->registerBladeDirectives();
  }

  /**
   * Register custom Blade directives.
   */
  protected function registerBladeDirectives(): void
  {
    Blade::directive('turnstileScripts', function () {
      return '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    });
  }

  /**
   * Get the services provided by the provider.
   */
  public function provides(): array
  {
    return [
      TurnstileClient::class,
    ];
  }
}