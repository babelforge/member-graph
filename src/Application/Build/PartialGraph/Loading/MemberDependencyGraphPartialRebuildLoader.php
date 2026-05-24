<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Loading;

use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotBuilder;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshotBuilder;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;

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
     * @param MemberDependencyGraphPartialRebuildInput $partialRebuildInput the partial rebuild input
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
