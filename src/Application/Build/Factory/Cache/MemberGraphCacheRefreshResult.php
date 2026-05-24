<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Cache;

use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;

/**
 * Reports the result of refreshing member graph cache metadata after a build.
 */
final readonly class MemberGraphCacheRefreshResult
{
    /**
     * Constructor.
     *
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences the refreshed virtual file references
     * @param MemberGraphCacheWriteResult               $cacheWriteResult      the cache write result
     */
    public function __construct(
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences,
        public MemberGraphCacheWriteResult $cacheWriteResult,
    ) {
    }
}
