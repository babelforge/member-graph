<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild;

use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCachePathNormalizer;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Resolves cache-backed source metadata for future global-index rebuilds.
 */
final readonly class MemberGraphGlobalIndexRebuildInputResolver
{
    /**
     * Constructor.
     *
     * @param MemberGraphCachePathNormalizer $pathNormalizer the cache path normalizer
     */
    public function __construct(
        private MemberGraphCachePathNormalizer $pathNormalizer = new MemberGraphCachePathNormalizer(),
    ) {
    }

    /**
     * Resolves reusable global-index inputs from a partial rebuild input.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput the partial rebuild input
     */
    public function resolve(MemberDependencyGraphPartialRebuildInput $partialRebuildInput): MemberGraphGlobalIndexRebuildInput
    {
        $filesToBuild = array_fill_keys(array_map(
            fn (string $filePath): string => $this->pathNormalizer->normalize($filePath),
            $partialRebuildInput->filesToBuild->all(),
        ), true);
        $filesToDelete = array_fill_keys(array_map(
            fn (string $filePath): string => $this->pathNormalizer->normalize($filePath),
            $partialRebuildInput->filesToDelete->all(),
        ), true);
        $reusableSources = new MemberGraphVirtualSourceMetadataCollection();

        foreach ($partialRebuildInput->globalIndexInputSnapshot->sources as $metadata) {
            $normalizedFilePath = $this->pathNormalizer->normalize($metadata->fullFilePath);

            if (isset($filesToBuild[$normalizedFilePath]) || isset($filesToDelete[$normalizedFilePath])) {
                continue;
            }

            $reusableSources->add($metadata);
        }

        return new MemberGraphGlobalIndexRebuildInput(
            reusableSources: $reusableSources,
            filesToBuild: $partialRebuildInput->filesToBuild,
            fragmentsToReuse: $partialRebuildInput->fragmentsToReuse,
            knownOwners: $partialRebuildInput->knownOwners,
            virtualFileReferences: $partialRebuildInput->virtualFileReferences,
        );
    }
}
