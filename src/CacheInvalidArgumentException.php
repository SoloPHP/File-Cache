<?php

namespace Solo\FileCache;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Exception thrown when cache key is invalid
 */
class CacheInvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentException
{
}