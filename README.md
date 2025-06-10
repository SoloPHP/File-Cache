# Solo FileCache

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/solophp/file-cache)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](https://opensource.org/licenses/MIT)

A simple, lightweight, file-based cache implementation for PHP that adheres to the PSR-16 Simple Cache standard. `Solo\FileCache` provides a straightforward way to cache data using the filesystem with TTL (time-to-live) support, making it ideal for small to medium-sized projects where external dependencies like Redis or Memcached are not desired.

## Features

- Fully compliant with [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/).
- File-based storage with no external dependencies.
- Supports TTL for cache entries (via seconds or `DateInterval`).
- Thread-safe operations using file locking (`flock`).
- Validates cache keys per PSR-16 requirements.
- Handles filesystem errors gracefully with meaningful exceptions.

## Installation

Install the package via Composer:

```bash
composer require solophp/file-cache
```

## Requirements

- PHP >= 8.1
- Write access to the specified cache directory

## Usage

### Basic Example

```php
<?php

use Solo\FileCache;

$cache = new FileCache('/path/to/cache');

// Set a cache item with a 1-hour TTL
$cache->set('user_123', ['name' => 'John Doe'], 3600);

// Retrieve the cache item
$user = $cache->get('user_123', null);
if ($user !== null) {
    echo $user['name']; // Outputs: John Doe
}

// Check if a cache item exists
if ($cache->has('user_123')) {
    echo 'Cache exists!';
}

// Delete a cache item
$cache->delete('user_123');

// Clear all cache items
$cache->clear();
```

### Working with Multiple Items

```php
// Set multiple cache items
$cache->setMultiple([
    'key1' => 'value1',
    'key2' => 'value2',
], 600); // 10-minute TTL

// Retrieve multiple cache items
$items = $cache->getMultiple(['key1', 'key2'], 'default');
foreach ($items as $key => $value) {
    echo "$key: $value\n";
}

// Delete multiple cache items
$cache->deleteMultiple(['key1', 'key2']);
```

### Using DateInterval for TTL

```php
$ttl = new DateInterval('PT1H'); // 1 hour
$cache->set('session_data', ['token' => 'abc123'], $ttl);
```

## Configuration

The `FileCache` constructor accepts a single parameter for the cache directory:

```php
$cache = new FileCache('/tmp/my_cache');
```

If the directory does not exist, it will be created automatically with `0755` permissions. Ensure the PHP process has write access to this directory, or a `RuntimeException` will be thrown.

## Key Validation

Cache keys must conform to PSR-16 requirements (alphanumeric characters, underscores, and dots only). Invalid keys will throw a `Psr\SimpleCache\InvalidArgumentException`.

## Error Handling

The class throws exceptions for critical errors:
- `RuntimeException` if the cache directory cannot be created or is not writable.
- `Psr\SimpleCache\InvalidArgumentException` for invalid cache keys.

## Limitations

- Suitable for low to medium load applications due to filesystem-based storage.
- Not optimized for high-concurrency scenarios or large-scale caching.
- Uses PHP's `serialize` for data storage, so avoid caching untrusted input to prevent potential security issues.

For high-performance or distributed systems, consider using Redis, Memcached, or other PSR-16-compatible caching solutions.

## License

This package is licensed under the [MIT License](LICENSE).

## Contributing

Contributions are welcome! Please submit issues or pull requests on [GitHub](https://github.com/yourusername/solo-filecache).

## Acknowledgments

Built with simplicity and reliability in mind, inspired by the PSR-16 standard and the needs of lightweight PHP applications.