<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Loading;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshotBuilder;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Loads only files scheduled for a future partial member graph rebuild.
 */
final readonly class MemberDependencyGraphPartialRebuildLoader
{
    public function __construct(private MemberGraphPhpSourceRegistryInstance $fileRegistry)
    {
    }

    /**
     * Loads source data for files scheduled for rebuild.
     *
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput The partial rebuild input.
     *
     * @return MemberDependencyGraphPartialRebuildLoadedInput
     */
    public function load(MemberDependencyGraphPartialRebuildInput $partialRebuildInput): MemberDependencyGraphPartialRebuildLoadedInput
    {
        $loadedVirtualFiles = new VirtualPhpSourceFileCollection();

        foreach ($partialRebuildInput->filesToBuild as $filePath) {
            $loadedVirtualFiles->merge($this->fileRegistry->getVirtualFiles($filePath));
        }

        $loadedDeclarationSnapshot = new MemberGraphDeclarationSnapshotBuilder()->build($loadedVirtualFiles);
        $loadedSourceMetadata = new MemberGraphLoadedSourceMetadata(
            new MemberGraphGlobalIndexInputSnapshotBuilder()->build(
                virtualFiles: $loadedVirtualFiles,
                knownOwners: $this->fileRegistry->getKnownOwners(),
            )->sources,
        );

        return new MemberDependencyGraphPartialRebuildLoadedInput(
            loadedVirtualFiles: $loadedVirtualFiles,
            loadedDeclarationSnapshot: $loadedDeclarationSnapshot,
            loadedSourceMetadata: $loadedSourceMetadata,
        );
    }
}
