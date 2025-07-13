<?php

namespace LacruzWebDev\LivewireHtmlForms\Services;

use Illuminate\Support\Facades\Http;
use LacruzWebDev\LivewireHtmlForms\Responses\SiteverifyResponse;

class TurnstileClient
{
  const SITEKEY_ALWAYS_PASSES_VISIBLE = '1x00000000000000000000AA';
  const SITEKEY_ALWAYS_BLOCKS_VISIBLE = '2x00000000000000000000AB';
  const SITEKEY_FORCE_INTERACTIVE_VISIBLE = '3x00000000000000000000FF';
  const SITEKEY_ALWAYS_PASSES_INVISIBLE = '1x00000000000000000000BB';
  const SITEKEY_ALWAYS_BLOCKS_INVISIBLE = '2x00000000000000000000BB';
  const SECRET_KEY_ALWAYS_PASSES = '1x0000000000000000000000000000000AA';
  const SECRET_KEY_ALWAYS_FAILS = '2x0000000000000000000000000000000AA';
  const SECRET_KEY_TOKEN_SPENT = '3x0000000000000000000000000000000AA';
  const RESPONSE_DUMMY_TOKEN = 'XXXX.DUMMY.TOKEN.XXXX';

  public function __construct(
    protected string $secret,
    protected string $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    protected int $retryAttempts = 3,
    protected int $retryDelay = 100
  ) {
  }

  public function siteverify(string $response): SiteverifyResponse
  {
    if ($response === self::RESPONSE_DUMMY_TOKEN && !app()->isProduction()) {
      return new SiteverifyResponse(
        success: true,
        errorCodes: [],
      );
    }
    $httpResponse = Http::retry($this->retryAttempts, $this->retryDelay)
      ->asForm()
      ->acceptJson()
      ->post($this->endpoint, [
        'secret' => $this->secret,
        'response' => $response,
      ]);

    logger()->info('Response: ' . $response);

    if (!$httpResponse->ok()) {
      return new SiteverifyResponse(
        success: false,
        errorCodes: ['network-error']
      );
    }

    return new SiteverifyResponse(
      success: $httpResponse->json('success', false),
      errorCodes: $httpResponse->json('error-codes', [])
    );
  }
}