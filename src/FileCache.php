<?php declare(strict_types=1);

namespace Solo\FileCache;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache implements CacheInterface
{
    private readonly string $cacheDir;

    public function __construct(string $cacheDir = '/tmp/cache')
    {
        $this->cacheDir = rtrim($cacheDir, '/\\');
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
            throw new \RuntimeException("Cannot create cache directory: {$this->cacheDir}");
        }
        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);
        $file = $this->getFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            return $default;
        }

        try {
            if (flock($handle, LOCK_SH)) {
                $data = unserialize(file_get_contents($file), ['allowed_classes' => true]);
                flock($handle, LOCK_UN);
                if ($data === false || !is_array($data) || !isset($data['value'], $data['expire'])) {
                    unlink($file);
                    return $default;
                }
                if ($data['expire'] !== null && $data['expire'] < time()) {
                    unlink($file);
                    return $default;
                }
                return $data['value'];
            }
        } finally {
            fclose($handle);
        }

        return $default;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateKey($key);
        $file = $this->getFile($key);

        $expire = null;
        if ($ttl instanceof \DateInterval) {
            $expire = (new \DateTime())->add($ttl)->getTimestamp();
        } elseif (is_int($ttl)) {
            $expire = time() + $ttl;
        }

        $data = ['value' => $value, 'expire' => $expire];

        $handle = fopen($file, 'c');
        if ($handle === false) {
            return false;
        }

        try {
            if (flock($handle, LOCK_EX)) {
                ftruncate($handle, 0);
                $result = fwrite($handle, serialize($data)) !== false;
                flock($handle, LOCK_UN);
                return $result;
            }
        } finally {
            fclose($handle);
        }

        return false;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);
        return $this->get($key) !== null;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);
        $file = $this->getFile($key);
        return !file_exists($file) || unlink($file);
    }

    public function clear(): bool
    {
        $success = true;
        foreach (glob($this->cacheDir . '/*.cache') ?: [] as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        return $success;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $this->validateKey($key);
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    private function getFile(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    private function validateKey(string $key): void
    {
        if ($key === '' || !preg_match('/^[a-zA-Z0-9_.]+$/', $key)) {
            throw new class("Invalid cache key: {$key}") extends \InvalidArgumentException implements InvalidArgumentException {
            };
        }
    }
}