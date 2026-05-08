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
     * @param string $filePath the file path to normalize
     */
    public function normalize(string $filePath): string
    {
        return realpath($filePath) ?: $filePath;
    }
}
