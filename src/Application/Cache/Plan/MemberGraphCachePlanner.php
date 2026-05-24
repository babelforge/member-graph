<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Plan;

use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheState;
use BabelForge\MemberGraph\Application\Cache\Fingerprint\MemberGraphFileFingerprintResolver;

/**
 * Builds cache reuse plans for scanned physical files.
 */
final readonly class MemberGraphCachePlanner
{
    /**
     * Constructor.
     *
     * @param MemberGraphFileFingerprintResolver $fingerprintResolver the file fingerprint resolver
     * @param MemberGraphCachePathNormalizer     $pathNormalizer      the cache path normalizer
     */
    public function __construct(
        private MemberGraphFileFingerprintResolver $fingerprintResolver = new MemberGraphFileFingerprintResolver(),
        private MemberGraphCachePathNormalizer $pathNormalizer = new MemberGraphCachePathNormalizer(),
    ) {
    }

    /**
     * Indicates whether the cache entry for one file can be reused.
     *
     * @param string                $filePath the file path to inspect
     * @param MemberGraphCacheState $state    the current cache state
     */
    public function isFresh(string $filePath, MemberGraphCacheState $state): bool
    {
        $filePath = $this->pathNormalizer->normalize($filePath);
        $payload = $state->filePayload($filePath);

        if (null === $payload || !is_file($filePath)) {
            return false;
        }

        return $this->fingerprintResolver->strategyVersion() === $payload->entry->fingerprintStrategyVersion
            && $this->fingerprintResolver->resolve($filePath) === $payload->entry->fingerprint;
    }

    /**
     * Builds a cache plan for the given scanned file paths.
     *
     * @param list<string>          $filePaths the currently scanned physical file paths
     * @param MemberGraphCacheState $state     the current cache state
     */
    public function planForFiles(array $filePaths, MemberGraphCacheState $state): MemberGraphCachePlan
    {
        $freshFiles = new MemberGraphCacheFileCollection();
        $staleFiles = new MemberGraphCacheFileCollection();
        $deletedFiles = new MemberGraphCacheFileCollection();
        $missingFiles = new MemberGraphCacheFileCollection();
        $missingFilePayloads = new MemberGraphCacheFileCollection();
        $missingGraphFragments = new MemberGraphCacheFileCollection();
        $fastPathBlockers = new MemberGraphCacheFastPathBlockerCollection();
        $scannedFilePathMap = [];

        foreach ($filePaths as $filePath) {
            $normalizedFilePath = $this->pathNormalizer->normalize($filePath);
            $scannedFilePathMap[$normalizedFilePath] = true;

            if (!$state->hasFilePayload($normalizedFilePath)) {
                $missingFilePayloads->add($normalizedFilePath);
                $missingFiles->add($normalizedFilePath);
                continue;
            }

            if (!$this->isFresh($normalizedFilePath, $state)) {
                $staleFiles->add($normalizedFilePath);
                continue;
            }

            if (null === $state->graphFragment($normalizedFilePath)) {
                $missingGraphFragments->add($normalizedFilePath);
                $missingFiles->add($normalizedFilePath);
                continue;
            }

            $freshFiles->add($normalizedFilePath);
        }

        foreach ($state->filePaths() as $cachedFilePath) {
            if (!isset($scannedFilePathMap[$cachedFilePath])) {
                $deletedFiles->add($cachedFilePath);
            }
        }

        if ([] === $filePaths) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::NO_SCANNED_FILES);
        }

        if (0 < count($staleFiles)) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::STALE_FILES);
        }

        if (0 < count($deletedFiles)) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::DELETED_FILES);
        }

        if (0 < count($missingFilePayloads)) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_FILE_PAYLOADS);
        }

        if (0 < count($missingGraphFragments)) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_GRAPH_FRAGMENTS);
        }

        if (!$state->hasKnownOwners()) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_KNOWN_OWNERS);
        }

        if (!$state->hasVirtualFileReferences()) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_VIRTUAL_FILE_REFERENCES);
        }

        if (!$state->hasGlobalIndexInputSnapshot()) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_GLOBAL_INDEX_INPUT_SNAPSHOT);
        } elseif (!$state->hasCompatibleGlobalIndexInputSnapshot()) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::INCOMPATIBLE_GLOBAL_INDEX_INPUT_SNAPSHOT);
        }

        if (!$state->hasDeclarationSnapshot()) {
            $fastPathBlockers->add(MemberGraphCacheFastPathBlocker::MISSING_DECLARATION_SNAPSHOT);
        }

        return new MemberGraphCachePlan(
            freshFiles: $freshFiles,
            staleFiles: $staleFiles,
            deletedFiles: $deletedFiles,
            missingFiles: $missingFiles,
            canUseFastPath: [] !== $filePaths
                && 0 === count($staleFiles)
                && 0 === count($deletedFiles)
                && 0 === count($missingFiles)
                && $state->hasKnownOwners()
                && $state->hasVirtualFileReferences(),
            missingFilePayloads: $missingFilePayloads,
            missingGraphFragments: $missingGraphFragments,
            hasKnownOwners: $state->hasKnownOwners(),
            hasVirtualFileReferences: $state->hasVirtualFileReferences(),
            hasGlobalIndexInputSnapshot: $state->hasGlobalIndexInputSnapshot(),
            hasCompatibleGlobalIndexInputSnapshot: $state->hasCompatibleGlobalIndexInputSnapshot(),
            hasDeclarationSnapshot: $state->hasDeclarationSnapshot(),
            fastPathBlockers: $fastPathBlockers,
        );
    }
}
