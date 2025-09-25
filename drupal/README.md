# DDEV Drupal Add-on: DatabaseNullBackend

This DDEV add-on provides a custom cache backend that stores cache data in the database but always returns cache misses (like NullBackend). This is useful for debugging cache contexts and tags without having to rebuild the cache constantly.

## What it does

The `DatabaseNullBackendFactory` creates cache backends that:
- **Store** cache data in the database (like `DatabaseBackend`)
- **Always return cache misses** on retrieval (like `NullBackend`)

This allows you to:
- Debug cache contexts and tags by seeing what would be cached
- Analyze cache behavior without actually using cached data
- Test cache invalidation logic
- Monitor cache storage patterns

## Files

- `src/Cache/DatabaseNullBackendFactory.php` - Factory class that creates the cache backends
- `src/Cache/DatabaseNullBackend.php` - Backend class that stores but doesn't retrieve
- `autoload.php` - Autoloader for the custom namespace
- `assets/dev.services.yml` - Service definition for the cache backend
- `assets/settings.dev.php` - Development settings that include the autoloader

## Usage

The cache backend is automatically available in development environments as `cache.backend.database.null`.

### Configure specific cache bins

To use this backend for specific cache bins, add to your `settings.php` or `settings.local.php`:

```php
// Use for specific cache bins
$settings['cache']['bins']['render'] = 'cache.backend.database.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.database.null';
$settings['cache']['bins']['page'] = 'cache.backend.database.null';
```

### Configure as default backend

To use as the default cache backend:

```php
$settings['cache']['default'] = 'cache.backend.database.null';
```

## Namespace

The classes use the `Ddev\Drupal\Cache` namespace to avoid conflicts with Drupal core classes while still being able to extend them.

## Testing

Run the test script to verify the installation:

```bash
php .ddev/drupal/test_cache.php
```

## How it works

1. The `DatabaseNullBackendFactory` extends Drupal's `DatabaseBackendFactory`
2. It creates `DatabaseNullBackend` instances instead of regular `DatabaseBackend` instances
3. `DatabaseNullBackend` extends `DatabaseBackend` but overrides `get()` and `getMultiple()` to always return cache misses
4. All other operations (set, delete, invalidate, etc.) work normally, storing data in the database
5. The autoloader ensures the classes can be found by Drupal's service container

This gives you the best of both worlds: you can see what's being cached and analyze cache behavior, but your application always acts as if there's no cache, ensuring you're testing the uncached code paths.
