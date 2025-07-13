<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Turnstile Configuration
  |--------------------------------------------------------------------------
  |
  | Configure Cloudflare Turnstile settings for form validation
  |
  */
  'turnstile' => [
    'enabled' => env('TURNSTILE_ENABLED', true),
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
    'endpoint' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    'retry_attempts' => 3,
    'retry_delay' => 100, // milliseconds
  ],

  /*
  |--------------------------------------------------------------------------
  | Form Processing Configuration
  |--------------------------------------------------------------------------
  |
  | Configure how forms are processed and validated
  |
  */
  'forms' => [
    'default_error_message' => 'Refresca la página e inténtalo de nuevo',
    'validation_error_message' => 'Por favor, corrige los errores del formulario.',
    'processing_error_message' => 'Ocurrió un error al procesar el formulario.',
    'success_message' => 'Formulario enviado correctamente.',
    'log_errors' => true,
    'log_submissions' => true,
  ],

  /*
  |--------------------------------------------------------------------------
  | WordPress Integration
  |--------------------------------------------------------------------------
  |
  | Configure WordPress HTML Forms plugin integration
  |
  */
  'wordpress' => [
    'required_plugin' => 'HTML_Forms\\Forms',
    'check_plugin_exists' => true,
    'honeypot_field_prefix' => '_hf_h',
    'form_id_field' => '_hf_form_id',
  ],

  /*
  |--------------------------------------------------------------------------
  | Security Settings
  |--------------------------------------------------------------------------
  |
  | Configure security features for form processing
  |
  */
  'security' => [
    'honeypot_enabled' => true,
  ],

  /*
  |--------------------------------------------------------------------------
  | Validation Rules
  |--------------------------------------------------------------------------
  |
  | Default validation rules that can be applied to forms
  |
  */
  'validation' => [
    'messages' => [
      'turnstile_response.required' => 'La validación de seguridad es requerida.',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Blade Components
  |--------------------------------------------------------------------------
  |
  | Configure Blade component settings
  |
  */
  'components' => [
    'turnstile' => [
      'default_id' => 'captcha',
    ],
  ],
];