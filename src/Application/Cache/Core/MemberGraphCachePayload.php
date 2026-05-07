<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFilePayload;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Serializable payload for member graph cache metadata.
 */
final readonly class MemberGraphCachePayload
{
    public const int SCHEMA_VERSION = 8;

    /**
     * Constructor.
     *
     * @param int $schemaVersion The cache schema version.
     * @param array<string, MemberGraphCacheFilePayload> $filesByPath Cache file payloads indexed by physical file path.
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences Cached virtual file references.
     * @param KnownOwnerCollection|null $knownOwners Cached known owners.
     * @param MemberGraphGlobalIndexInputSnapshot|null $globalIndexInputSnapshot Cached global-index input snapshot.
     * @param MemberGraphDeclarationSnapshot|null $declarationSnapshot Cached declaration snapshot.
     */
    public function __construct(
        public int $schemaVersion = self::SCHEMA_VERSION,
        public array $filesByPath = [],
        public MemberGraphVirtualFileReferenceCollection $virtualFileReferences = new MemberGraphVirtualFileReferenceCollection(),
        public ?KnownOwnerCollection $knownOwners = null,
        public ?MemberGraphGlobalIndexInputSnapshot $globalIndexInputSnapshot = null,
        public ?MemberGraphDeclarationSnapshot $declarationSnapshot = null,
    ) {
    }
}
