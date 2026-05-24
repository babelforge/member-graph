<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView;

use BabelForge\MemberGraph\Application\Build\PartialGraph\Execution\MemberDependencyGraphPartialRebuildExecutionResult;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshotBuilder;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;

/**
 * Merges reusable and rebuilt source metadata after a partial rebuild execution.
 */
final readonly class MemberDependencyGraphPartialRebuildSourceMetadataMerger
{
    /**
     * Builds the source metadata view that should be persisted after a partial rebuild.
     *
     * @param MemberDependencyGraphPartialRebuildPreparedInput   $preparedInput   the prepared partial rebuild input
     * @param MemberDependencyGraphPartialRebuildExecutionResult $executionResult the partial rebuild execution result
     */
    public function merge(
        MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
        MemberDependencyGraphPartialRebuildExecutionResult $executionResult,
    ): MemberGraphVirtualSourceMetadataCollection {
        $sourceMetadata = new MemberGraphVirtualSourceMetadataCollection();

        foreach ($preparedInput->sourceView->allSourceMetadata as $metadata) {
            $sourceMetadata->add($metadata);
        }

        $rebuiltSourceMetadata = new MemberGraphGlobalIndexInputSnapshotBuilder()->build(
            virtualFiles: $executionResult->rebuiltVirtualFiles,
            knownOwners: $executionResult->memberDependencyGraph->knownOwners,
        )->sources;

        foreach ($rebuiltSourceMetadata as $metadata) {
            $sourceMetadata->add($metadata);
        }

        return $sourceMetadata;
    }
}
