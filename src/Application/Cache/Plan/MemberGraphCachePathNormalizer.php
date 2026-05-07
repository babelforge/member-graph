<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Plan;

/**
 * Normalizes physical file paths used as member graph cache keys.
 */
final readonly class MemberGraphCachePathNormalizer
{
    /**
     * Normalizes a file path for cache lookup.
     *
     * @param string $filePath The file path to normalize.
     *
     * @return string
     */
    public function normalize(string $filePath): string
    {
        return realpath($filePath) ?: $filePath;
    }
}
