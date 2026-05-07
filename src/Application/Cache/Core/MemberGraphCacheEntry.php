<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

/**
 * Stores cache metadata for one physical PHP file.
 */
final readonly class MemberGraphCacheEntry
{
    /**
     * Constructor.
     *
     * @param string $filePath The physical file path.
     * @param string $fingerprint The file fingerprint.
     * @param string $fingerprintStrategyVersion The file fingerprint strategy version.
     * @param int $lastModifiedTime The file last modification timestamp.
     */
    public function __construct(
        public string $filePath,
        public string $fingerprint,
        public string $fingerprintStrategyVersion,
        public int $lastModifiedTime,
    ) {
    }
}
