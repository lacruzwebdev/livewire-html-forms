# Livewire HTML Forms


Integration between Livewire and WordPress HTML Forms plugin with full Cloudflare Turnstile support.

## Features

- ✅ **Real-time validation** with Livewire
- ✅ **Cloudflare Turnstile** integration
- ✅ **Anti-spam protection** with Cloudflare Turnstile

## Installation

Install the package via Composer:

```bash
composer require lacruzwebdev/livewire-html-forms
```

Publish configuration files:

```bash
php artisan vendor:publish --tag=livewire-html-forms
```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
TURNSTILE_SITE_KEY=your-site-key
TURNSTILE_SECRET_KEY=your-secret-key
```

### Basic Configuration

The configuration file is located at `config/livewire-html-forms.php`:

```php
return [
    'turnstile' => [
        'enabled' => env('TURNSTILE_ENABLED', true),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
    // ... more configurations
];
```

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Livewire 3.0+
- WordPress HTML Forms plugin

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).

Find this package useful? Consider giving it a ⭐ on GitHub! 