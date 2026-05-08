<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Warning;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteStatus;

/**
 * Describes a non-blocking member dependency graph factory warning.
 */
final readonly class MemberDependencyGraphFactoryWarning
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphFactoryWarningCode $code             the warning code
     * @param string                                  $message          the human-readable warning message
     * @param string|null                             $cacheFilePath    the related cache file path, when available
     * @param string|null                             $tempFilePath     the related temporary file path, when available
     * @param MemberGraphCacheWriteStatus|null        $cacheWriteStatus the related cache write status, when available
     */
    public function __construct(
        public MemberDependencyGraphFactoryWarningCode $code,
        public string $message,
        public ?string $cacheFilePath = null,
        public ?string $tempFilePath = null,
        public ?MemberGraphCacheWriteStatus $cacheWriteStatus = null,
    ) {
    }
}
