# oc-sentry-plugin

> Sentry integration for October CMS

You can manage your Sentry configuration via the backend settings.

This plugin is also available on October's marketplace: https://octobercms.com/plugin/offline-sentry

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
