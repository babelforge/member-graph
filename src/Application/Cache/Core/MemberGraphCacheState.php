<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFilePayload;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Stores the mutable in-memory state of a member graph cache.
 */
final class MemberGraphCacheState
{
    /**
     * @param array<string, MemberGraphCacheFilePayload> $filesByPath              cache file payloads indexed by physical file path
     * @param MemberGraphVirtualFileReferenceCollection  $virtualFileReferences    cached virtual file references
     * @param KnownOwnerCollection|null                  $knownOwners              cached known owners
     * @param MemberGraphGlobalIndexInputSnapshot|null   $globalIndexInputSnapshot cached global-index input snapshot
     * @param MemberGraphDeclarationSnapshot|null        $declarationSnapshot      cached declaration snapshot
     */
    public function __construct(
        private array $filesByPath = [],
        private MemberGraphVirtualFileReferenceCollection $virtualFileReferences = new MemberGraphVirtualFileReferenceCollection(),
        private ?KnownOwnerCollection $knownOwners = null,
        private ?MemberGraphGlobalIndexInputSnapshot $globalIndexInputSnapshot = null,
        private ?MemberGraphDeclarationSnapshot $declarationSnapshot = null,
    ) {
    }

    /**
     * Creates cache state from a serialized payload.
     *
     * @param MemberGraphCachePayload $payload the cache payload
     */
    public static function fromPayload(MemberGraphCachePayload $payload): self
    {
        return new self(
            filesByPath: $payload->filesByPath,
            virtualFileReferences: $payload->virtualFileReferences,
            knownOwners: $payload->knownOwners,
            globalIndexInputSnapshot: $payload->globalIndexInputSnapshot,
            declarationSnapshot: $payload->declarationSnapshot,
        );
    }

    /**
     * Converts state to a serializable payload.
     */
    public function toPayload(): MemberGraphCachePayload
    {
        return new MemberGraphCachePayload(
            filesByPath: $this->filesByPath,
            virtualFileReferences: $this->virtualFileReferences,
            knownOwners: $this->knownOwners,
            globalIndexInputSnapshot: $this->globalIndexInputSnapshot,
            declarationSnapshot: $this->declarationSnapshot,
        );
    }

    /**
     * Indicates whether a file payload exists.
     *
     * @param string $filePath the normalized physical file path
     */
    public function hasFilePayload(string $filePath): bool
    {
        return isset($this->filesByPath[$filePath]);
    }

    /**
     * Returns a file payload when available.
     *
     * @param string $filePath the normalized physical file path
     */
    public function filePayload(string $filePath): ?MemberGraphCacheFilePayload
    {
        return $this->filesByPath[$filePath] ?? null;
    }

    /**
     * Stores a file payload.
     *
     * @param string                      $filePath the normalized physical file path
     * @param MemberGraphCacheFilePayload $payload  the file payload
     */
    public function setFilePayload(string $filePath, MemberGraphCacheFilePayload $payload): void
    {
        $this->filesByPath[$filePath] = $payload;
    }

    /**
     * Removes one file payload.
     *
     * @param string $filePath the normalized physical file path
     */
    public function removeFilePayload(string $filePath): void
    {
        unset($this->filesByPath[$filePath]);
    }

    /**
     * Returns cached file paths.
     *
     * @return list<string>
     */
    public function filePaths(): array
    {
        return array_keys($this->filesByPath);
    }

    /**
     * Returns the cached graph fragment for one file when available.
     *
     * @param string $filePath the normalized physical file path
     */
    public function graphFragment(string $filePath): ?MemberDependencyGraph
    {
        return $this->filesByPath[$filePath]->graphFragment ?? null;
    }

    /**
     * Stores virtual file references.
     *
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences the references to store
     */
    public function setVirtualFileReferences(MemberGraphVirtualFileReferenceCollection $virtualFileReferences): void
    {
        $this->virtualFileReferences = $virtualFileReferences;
    }

    /**
     * Returns cached virtual file references.
     */
    public function virtualFileReferences(): MemberGraphVirtualFileReferenceCollection
    {
        return $this->virtualFileReferences;
    }

    /**
     * Indicates whether cached virtual file references are available.
     */
    public function hasVirtualFileReferences(): bool
    {
        return 0 < count($this->virtualFileReferences);
    }

    /**
     * Stores known owners.
     *
     * @param KnownOwnerCollection $knownOwners the known owners to store
     */
    public function setKnownOwners(KnownOwnerCollection $knownOwners): void
    {
        $this->knownOwners = $knownOwners;
    }

    /**
     * Returns cached known owners.
     */
    public function knownOwners(): ?KnownOwnerCollection
    {
        return $this->knownOwners;
    }

    /**
     * Indicates whether known owners are available.
     */
    public function hasKnownOwners(): bool
    {
        return null !== $this->knownOwners;
    }

    /**
     * Stores the global-index input snapshot.
     *
     * @param MemberGraphGlobalIndexInputSnapshot $snapshot the snapshot to store
     */
    public function setGlobalIndexInputSnapshot(MemberGraphGlobalIndexInputSnapshot $snapshot): void
    {
        $this->globalIndexInputSnapshot = $snapshot;
    }

    /**
     * Returns the cached global-index input snapshot.
     */
    public function globalIndexInputSnapshot(): ?MemberGraphGlobalIndexInputSnapshot
    {
        return $this->globalIndexInputSnapshot;
    }

    /**
     * Indicates whether a global-index input snapshot is available.
     */
    public function hasGlobalIndexInputSnapshot(): bool
    {
        return null !== $this->globalIndexInputSnapshot;
    }

    /**
     * Indicates whether a compatible global-index input snapshot is available.
     */
    public function hasCompatibleGlobalIndexInputSnapshot(): bool
    {
        return null !== $this->globalIndexInputSnapshot
            && $this->globalIndexInputSnapshot->isCompatible();
    }

    /**
     * Stores the declaration snapshot.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot the declaration snapshot to store
     */
    public function setDeclarationSnapshot(MemberGraphDeclarationSnapshot $snapshot): void
    {
        $this->declarationSnapshot = $snapshot;
    }

    /**
     * Returns the cached declaration snapshot.
     */
    public function declarationSnapshot(): ?MemberGraphDeclarationSnapshot
    {
        return $this->declarationSnapshot;
    }

    /**
     * Indicates whether a declaration snapshot is available.
     */
    public function hasDeclarationSnapshot(): bool
    {
        return null !== $this->declarationSnapshot;
    }
}
