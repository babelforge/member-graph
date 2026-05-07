<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Core;

use PhpNoobs\MemberGraph\Application\Cache\Fingerprint\MemberGraphFileFingerprintResolver;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFilePayload;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePathNormalizer;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlanner;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Holds cache configuration for member dependency graph factory builds.
 */
final class MemberGraphCache
{
    private MemberGraphCacheState $state;

    private MemberGraphCacheLoadResult $loadResult;

    /**
     * Constructor.
     *
     * @param string $cacheFilePath The cache file path.
     * @param list<string> $directories The managed directories.
     * @param bool $clearCache Whether the cache must be cleared first.
     * @param MemberGraphIssueCollection|null $dependencyGraphIssues The dependency graph issue collection.
     * @param MemberGraphFileFingerprintResolver $fingerprintResolver The file fingerprint resolver.
     * @param MemberGraphCacheStorage|null $storage The cache storage.
     * @param MemberGraphCachePlanner|null $planner The cache planner.
     * @param MemberGraphCachePathNormalizer $pathNormalizer The cache path normalizer.
     */
    public function __construct(
        public readonly string                              $cacheFilePath,
        public readonly array                               $directories,
        public readonly bool                                $clearCache = false,
        public readonly ?MemberGraphIssueCollection         $dependencyGraphIssues = null,
        private readonly MemberGraphFileFingerprintResolver $fingerprintResolver = new MemberGraphFileFingerprintResolver(),
        private readonly ?MemberGraphCacheStorage           $storage = null,
        private readonly ?MemberGraphCachePlanner           $planner = null,
        private readonly MemberGraphCachePathNormalizer     $pathNormalizer = new MemberGraphCachePathNormalizer(),
    ) {
        $this->state = new MemberGraphCacheState();
        $this->loadResult = MemberGraphCacheLoadResult::notLoaded(MemberGraphCacheLoadStatus::CACHE_FILE_MISSING);
        $this->load();
    }

    /**
     * Returns the last cache payload load result.
     *
     * @return MemberGraphCacheLoadResult
     */
    public function loadResult(): MemberGraphCacheLoadResult
    {
        return $this->loadResult;
    }

    /**
     * Indicates whether the cache entry for one file can be reused.
     *
     * @param string $filePath The file path to inspect.
     *
     * @return bool
     */
    public function isFresh(string $filePath): bool
    {
        return $this->planner()->isFresh($filePath, $this->state);
    }

    /**
     * Marks one file as built and cacheable.
     *
     * @param string $filePath The file path to mark.
     * @param MemberDependencyGraph|null $graphFragment The optional graph fragment for this file.
     *
     * @return void
     */
    public function markBuilt(string $filePath, ?MemberDependencyGraph $graphFragment = null): void
    {
        $filePath = $this->pathNormalizer->normalize($filePath);

        if (!is_file($filePath)) {
            return;
        }

        $this->state->setFilePayload(
            $filePath,
            new MemberGraphCacheFilePayload(
                entry: new MemberGraphCacheEntry(
                    filePath: $filePath,
                    fingerprint: $this->fingerprintResolver->resolve($filePath),
                    fingerprintStrategyVersion: $this->fingerprintResolver->strategyVersion(),
                    lastModifiedTime: filemtime($filePath) ?: 0,
                ),
                graphFragment: $graphFragment,
            ),
        );
    }

    /**
     * Returns the cached graph fragment for one file when available.
     *
     * @param string $filePath The file path to inspect.
     *
     * @return MemberDependencyGraph|null
     */
    public function graphFragment(string $filePath): ?MemberDependencyGraph
    {
        $filePath = $this->pathNormalizer->normalize($filePath);

        return $this->state->graphFragment($filePath);
    }

    /**
     * Stores virtual file references in the cache payload.
     *
     * @param MemberGraphVirtualFileReferenceCollection $virtualFileReferences The references to store.
     *
     * @return void
     */
    public function setVirtualFileReferences(MemberGraphVirtualFileReferenceCollection $virtualFileReferences): void
    {
        $this->state->setVirtualFileReferences($virtualFileReferences);
    }

    /**
     * Returns cached virtual file references.
     *
     * @return MemberGraphVirtualFileReferenceCollection
     */
    public function virtualFileReferences(): MemberGraphVirtualFileReferenceCollection
    {
        return $this->state->virtualFileReferences();
    }

    /**
     * Stores known owners in the cache payload.
     *
     * @param KnownOwnerCollection $knownOwners The known owners to store.
     *
     * @return void
     */
    public function setKnownOwners(KnownOwnerCollection $knownOwners): void
    {
        $this->state->setKnownOwners($knownOwners);
    }

    /**
     * Returns cached known owners.
     *
     * @return KnownOwnerCollection|null
     */
    public function knownOwners(): ?KnownOwnerCollection
    {
        return $this->state->knownOwners();
    }

    /**
     * Stores the global-index input snapshot.
     *
     * @param MemberGraphGlobalIndexInputSnapshot $snapshot The snapshot to store.
     *
     * @return void
     */
    public function setGlobalIndexInputSnapshot(MemberGraphGlobalIndexInputSnapshot $snapshot): void
    {
        $this->state->setGlobalIndexInputSnapshot($snapshot);
    }

    /**
     * Returns the cached global-index input snapshot.
     *
     * @return MemberGraphGlobalIndexInputSnapshot|null
     */
    public function globalIndexInputSnapshot(): ?MemberGraphGlobalIndexInputSnapshot
    {
        return $this->state->globalIndexInputSnapshot();
    }

    /**
     * Indicates whether a compatible global-index input snapshot is available.
     *
     * @return bool
     */
    public function hasCompatibleGlobalIndexInputSnapshot(): bool
    {
        return $this->state->hasCompatibleGlobalIndexInputSnapshot();
    }

    /**
     * Stores the declaration snapshot.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot The declaration snapshot to store.
     *
     * @return void
     */
    public function setDeclarationSnapshot(MemberGraphDeclarationSnapshot $snapshot): void
    {
        $this->state->setDeclarationSnapshot($snapshot);
    }

    /**
     * Returns the cached declaration snapshot.
     *
     * @return MemberGraphDeclarationSnapshot|null
     */
    public function declarationSnapshot(): ?MemberGraphDeclarationSnapshot
    {
        return $this->state->declarationSnapshot();
    }

    /**
     * Indicates whether a declaration snapshot is available.
     *
     * @return bool
     */
    public function hasDeclarationSnapshot(): bool
    {
        return $this->state->hasDeclarationSnapshot();
    }

    /**
     * Indicates whether cached fragments can rebuild the graph without parsing files.
     *
     * @param list<string> $filePaths The currently scanned physical file paths.
     *
     * @return bool
     */
    public function canUseFastPath(array $filePaths): bool
    {
        return $this->planForFiles($filePaths)->canUseFastPath;
    }

    /**
     * Builds a cache plan for the given scanned file paths.
     *
     * @param list<string> $filePaths The currently scanned physical file paths.
     *
     * @return MemberGraphCachePlan
     */
    public function planForFiles(array $filePaths): MemberGraphCachePlan
    {
        return $this->planner()->planForFiles($filePaths, $this->state);
    }

    /**
     * Returns cached graph fragments for the given physical file paths.
     *
     * @param list<string> $filePaths The physical file paths.
     *
     * @return MemberGraphFragmentCollection
     */
    public function graphFragments(array $filePaths): MemberGraphFragmentCollection
    {
        $fragments = new MemberGraphFragmentCollection();

        foreach ($filePaths as $filePath) {
            $normalizedFilePath = $this->pathNormalizer->normalize($filePath);
            $fragment = $this->graphFragment($normalizedFilePath);

            if (null !== $fragment) {
                $fragments->add($normalizedFilePath, $fragment);
            }
        }

        return $fragments;
    }

    /**
     * Removes cache entries that are no longer part of the scanned file set.
     *
     * @param list<string> $currentFilePaths The currently managed files.
     *
     * @return void
     */
    public function removeMissingFiles(array $currentFilePaths): void
    {
        $currentFilePathMap = array_fill_keys(array_map(
            fn (string $filePath): string => $this->pathNormalizer->normalize($filePath),
            $currentFilePaths,
        ), true);

        foreach ($this->state->filePaths() as $filePath) {
            if (!isset($currentFilePathMap[$filePath])) {
                $this->state->removeFilePayload($filePath);
            }
        }
    }

    /**
     * Saves cache metadata to disk.
     *
     * @return void
     */
    public function save(): void
    {
        $this->saveResult();
    }

    /**
     * Saves cache metadata to disk and reports write failures.
     *
     * @return MemberGraphCacheWriteResult
     */
    public function saveResult(): MemberGraphCacheWriteResult
    {
        return $this->storage()->saveResult($this->state->toPayload());
    }

    /**
     * Loads cache metadata from disk when possible.
     *
     * @return void
     */
    private function load(): void
    {
        $this->loadResult = $this->storage()->loadResult($this->clearCache);
        $payload = $this->loadResult->payload;

        if (null === $payload) {
            return;
        }

        $this->state = MemberGraphCacheState::fromPayload($payload);
    }

    /**
     * Returns the cache storage.
     *
     * @return MemberGraphCacheStorage
     */
    private function storage(): MemberGraphCacheStorage
    {
        return $this->storage ?? new MemberGraphCacheStorage($this->cacheFilePath);
    }

    /**
     * Returns the cache planner.
     *
     * @return MemberGraphCachePlanner
     */
    private function planner(): MemberGraphCachePlanner
    {
        return $this->planner ?? new MemberGraphCachePlanner(
            fingerprintResolver: $this->fingerprintResolver,
            pathNormalizer: $this->pathNormalizer,
        );
    }
}
