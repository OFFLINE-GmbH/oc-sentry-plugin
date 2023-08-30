# oc-sentry-plugin

> Sentry integration for October CMS

## Setup

Simply add your Sentry DSN to your `.env` file:

```dotenv
SENTRY_LARAVEL_DSN=https://your@dsn.ingest.sentry.io/12345678
```

## Configuration

To configure this plugin, create a `config/sentry.php` file and add whatever configuration you need
according to the [Sentry documentation](https://docs.sentry.io/platforms/php/guides/laravel/configuration/).


## Capture custom messages

Use the following syntax to send custom messages to your Sentry logs.

```php
\Sentry\withScope(function (\Sentry\State\Scope $scope): void {
    $scope->setExtras([
        'custom' => 'value'
    ]);

    \Sentry\captureMessage('Something happened!');
});
```

## Upgrade from 2.x to 3.x

In version 3.0, this plugin was simplified considerably. You can now specify your settings
via the `config/sentry.php` file. The old backend settings page is no longer available.

To upgrade to this new version, you must add your Sentry DSN to your `.env` file:

```dotenv
SENTRY_LARAVEL_DSN=https://your@dsn.ingest.sentry.io/12345678
```
