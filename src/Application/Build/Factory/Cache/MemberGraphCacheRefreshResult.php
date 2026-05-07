<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory\Cache;

use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;

/**
 * Reports the result of refreshing member graph cache metadata after a build.
 */
final readonly class MemberGraphCacheRefreshResult
{
    /**
     * Constructor.
     *
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences The refreshed virtual file references.
     * @param MemberGraphCacheWriteResult $cacheWriteResult The cache write result.
     */
    public function __construct(
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
        public MemberGraphCacheWriteResult $cacheWriteResult,
    ) {
    }
}
