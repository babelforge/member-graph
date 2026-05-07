<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild;

use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePathNormalizer;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Resolves cache-backed source metadata for future global-index rebuilds.
 */
final readonly class MemberGraphGlobalIndexRebuildInputResolver
{
    /**
     * Constructor.
     *
     * @param MemberGraphCachePathNormalizer $pathNormalizer The cache path normalizer.
     */
    public function __construct(
        private MemberGraphCachePathNormalizer $pathNormalizer = new MemberGraphCachePathNormalizer(),
    ) {
    }

    /**
     * Resolves reusable global-index inputs from a partial rebuild input.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput The partial rebuild input.
     *
     * @return MemberGraphGlobalIndexRebuildInput
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
