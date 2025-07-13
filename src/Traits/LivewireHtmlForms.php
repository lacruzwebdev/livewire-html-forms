<?php

namespace LacruzWebDev\LivewireHtmlForms\Traits;

use LacruzWebDev\LivewireHtmlForms\Rules\Turnstile;
use LacruzWebDev\LivewireHtmlForms\Services\TurnstileClient;
use Illuminate\Validation\ValidationException;

/**
 * Trait LivewireHtmlForms
 *
 * Integration between Livewire and WordPress HTML Forms plugin
 *
 * USAGE:
 *
 * Basic usage:
 * $this->processWithHtmlForms('form-slug');
 *
 * With callback:
 * $this->processWithHtmlForms('form-slug', function($validated) {
 *     // Your logic here
 * });
 */
trait LivewireHtmlForms
{
  public $successMessage = '';
  public $errorMessage = '';
  public $hf = '';
  public $turnstileResponse = '';

  /**
   * Process form with Livewire validation + HTML Forms submission
   *
   * @param string $formSlug The HTML Forms form slug
   * @param callable|null $beforeSubmit Callback before submission
   */
  public function submitForm(string $formSlug, callable $beforeSubmit = null)
  {
    try {
      if (!$this->checkHtmlFormsPlugin()) {
        throw new \Exception('HTML Forms plugin not installed');
      }

      $this->clearMessages();
      $this->resetErrorBag();

      // Livewire Validation
      $validated = $this->validate();

      // Turnstile Validation
      if (config('livewire-html-forms.turnstile.enabled')) {
        $this->validateTurnstile();
      }

      // Optional callback before submission
      if ($beforeSubmit && is_callable($beforeSubmit)) {
        $beforeSubmit($validated);
      }

      // Process with HTML Forms Plugin
      $response = $this->submitToHtmlForms($formSlug, $validated);

      if ($response['success']) {
        $this->successMessage = $response['message'];
        $this->resetFormFields();
      } else {
        $this->errorMessage = $response['message'];
      }

    } catch (ValidationException $e) {
      $this->errorMessage = config('livewire-html-forms.forms.validation_error_message');
      throw $e;
    } catch (\Exception $e) {
      $this->errorMessage = config('livewire-html-forms.forms.processing_error_message') . ': ' . $e->getMessage();

      if (config('livewire-html-forms.forms.log_errors')) {
        logger()->error('HTML Forms integration error: ' . $e->getMessage());
      }
    }
  }

  /**
   * Validate Turnstile response
   */
  protected function validateTurnstile(): void
  {
    try {
      $validated = $this->validate([
        'turnstileResponse' => ['required', new Turnstile(app(TurnstileClient::class))],
      ]);
    } catch (ValidationException $e) {
      $this->errorMessage = config('livewire-html-forms.forms.default_error_message');
      throw $e;
    }
  }

  /**
   * Check if HTML Forms plugin is available
   */
  protected function checkHtmlFormsPlugin(): bool
  {
    if (!config('livewire-html-forms.wordpress.check_plugin_exists')) {
      return true;
    }

    return class_exists(config('livewire-html-forms.wordpress.required_plugin'));
  }

  /**
   * Send data to HTML Forms plugin
   *
   * @param string $formSlug
   * @param array $data
   * @return array
   */
  protected function submitToHtmlForms(string $formSlug, array $data): array
  {
    try {
      $form = hf_get_form($formSlug);

      if (!$form) {
        throw new \Exception("Form not found with slug: {$formSlug}");
      }

      $postData = $this->prepareFormData($form, $data);

      return $this->processWithHtmlFormsPlugin($form, $postData);

    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => $e->getMessage()
      ];
    }
  }

  /**
   * Process with HTML Forms plugin
   *
   * @param object $form
   * @param array $postData
   * @return array
   */
  protected function processWithHtmlFormsPlugin($form, array $postData): array
  {
    try {
      // Instantiate the plugin handler to make it compatible with Livewire
      $formsHandler = new \HTML_Forms\Forms(
        WP_PLUGIN_DIR . '/html-forms/html-forms.php',
        hf_get_settings()
      );

      $this->validateFormData($postData);
      $submission = $this->createSubmission($form, $postData, $formsHandler);
      $this->executeHooks($form, $submission);

      return [
        'success' => true,
        'message' => $form->get_message('success'),
        'hide_form' => (bool) $form->settings['hide_after_success'],
        'redirect_url' => !empty($form->settings['redirect_url']) ?
          hf_replace_data_variables($form->settings['redirect_url'], $submission, 'urlencode') : null
      ];

    } catch (\Exception $e) {
      if (config('livewire-html-forms.forms.log_errors')) {
        logger()->error('Critical error in HTML Forms processing: ' . $e->getMessage());
      }

      return [
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'internal_error'
      ];
    }
  }

  /**
   * Create submission in HTML Forms
   *
   * @param object $form
   * @param array $postData
   * @param object $formsHandler
   * @return object
   */
  protected function createSubmission($form, array $postData, $formsHandler)
  {
    $submission = new \HTML_Forms\Submission();
    $submission->form_id = $form->ID;

    // Filter data (remove internal fields)
    $filteredData = [];
    foreach ($postData as $key => $value) {
      if (!str_starts_with($key, '_hf_')) {
        $filteredData[$key] = $value;
      }
    }

    $sanitizedData = $formsHandler->sanitize($filteredData);

    $submission->data = $sanitizedData;
    $submission->ip_address = request()->ip();
    $submission->user_agent = request()->userAgent();
    $submission->referer_url = request()->header('referer', '');
    $submission->submitted_at = now()->format('Y-m-d H:i:s');

    // Save submission if enabled
    if ($form->settings['save_submissions'] && config('livewire-html-forms.forms.log_submissions')) {
      $submission->save();
    }

    return $submission;
  }

  /**
   * Execute WordPress hooks and actions
   *
   * @param object $form
   * @param object $submission
   */
  protected function executeHooks($form, $submission): void
  {
    // Main processing hook
    try {
      do_action('hf_process_form', $form, $submission);
    } catch (\Exception $e) {
      if (config('livewire-html-forms.forms.log_errors')) {
        logger()->error('Error in hf_process_form hook: ' . $e->getMessage());
      }
    }

    // Re-save submission object for convenience in form processors hooked into hf_process_form
    if ($form->settings['save_submissions'] && config('livewire-html-forms.forms.log_submissions')) {
      $submission->save();
    }

    // Process form actions
    if (isset($form->settings['actions'])) {
      foreach ($form->settings['actions'] as $action_settings) {
        try {
          do_action('hf_process_form_action_' . $action_settings['type'], $action_settings, $submission, $form);
        } catch (\Exception $e) {
          if (config('livewire-html-forms.forms.log_errors')) {
            logger()->error('Error in action ' . $action_settings['type'] . ': ' . $e->getMessage());
          }
        }
      }
    }

    // Success hooks
    try {
      do_action("hf_form_{$form->slug}_success", $submission, $form);
      do_action('hf_form_success', $submission, $form);
    } catch (\Exception $e) {
      if (config('livewire-html-forms.forms.log_errors')) {
        logger()->error('Error in success hooks: ' . $e->getMessage());
      }
    }
  }

  /**
   * Custom validation for safe mode
   *
   * @param array $postData
   * @throws \Exception
   */
  protected function validateFormData(array $postData): void
  {
    if (!config('livewire-html-forms.security.honeypot_enabled')) {
      return;
    }

    // Validate honeypot (anti-spam)
    $formId = $postData[config('livewire-html-forms.wordpress.form_id_field')] ?? null;
    $honeypotPrefix = config('livewire-html-forms.wordpress.honeypot_field_prefix');

    if ($formId && !empty($postData["{$honeypotPrefix}{$formId}"])) {
      throw new \Exception('Spam detected');
    }

    // Validate that data exists
    $dataFields = array_filter($postData, function ($key) {
      return !str_starts_with($key, '_hf_');
    }, ARRAY_FILTER_USE_KEY);

    if (empty($dataFields)) {
      throw new \Exception('No form data received');
    }
  }

  /**
   * Prepare data for HTML Forms
   *
   * @param object $form
   * @param array $data
   * @return array
   */
  protected function prepareFormData($form, array $data): array
  {
    $honeypotKey = config('livewire-html-forms.wordpress.honeypot_field_prefix') . $form->ID;
    $formIdField = config('livewire-html-forms.wordpress.form_id_field');

    $postData = [
      $formIdField => $form->ID,
      // Use honeypot value from Livewire component
      $honeypotKey => $this->hf,
    ];

    foreach ($data as $key => $value) {
      $postData[$key] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }

    return $postData;
  }

  /**
   * Generate hidden inputs needed for HTML Forms
   *
   * @param string $formSlug
   * @return string
   */
  public function getHtmlFormsHiddenInputs(string $formSlug): string
  {
    try {
      $form = hf_get_form($formSlug);
      if (!$form) {
        return '<!-- HTML Forms: form not found -->';
      }

      $formIdField = config('livewire-html-forms.wordpress.form_id_field');
      $honeypotPrefix = config('livewire-html-forms.wordpress.honeypot_field_prefix');

      return sprintf(
        '<input type="hidden" name="%s" value="%d" />
                <div style="display:none;">
                <input type="text" name="%s%d" value="" wire:model.live="hf"/>
                </div>',
        $formIdField,
        $form->ID,
        $honeypotPrefix,
        $form->ID
      );
    } catch (\Exception $e) {
      return '<!-- HTML Forms: error generating hidden inputs -->';
    }
  }

  /**
   * Clear messages
   */
  public function clearMessages(): void
  {
    $this->successMessage = '';
    $this->errorMessage = '';
  }

  /**
   * Get messages to display in view
   *
   * @return array
   */
  public function getMessages(): array
  {
    return [
      'success' => $this->successMessage,
      'error' => $this->errorMessage
    ];
  }

  /**
   * Reset form fields (must be implemented by each component)
   */
  protected function resetFormFields(): void
  {
    // Must be implemented by each Livewire component
  }

}