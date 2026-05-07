<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotMerger;

/**
 * Builds the partial-compatible global indexes currently available without PHPParser nodes.
 */
final readonly class MemberGraphPartialGlobalIndexesBuilder
{
    /**
     * Constructor.
     *
     * @param MemberGraphSourceMetadataGlobalOwnerIndexesBuilder $ownerIndexesBuilder The owner index builder.
     * @param MemberGraphDeclarationSnapshotMerger $declarationSnapshotMerger The declaration snapshot merger.
     * @param MemberGraphDeclarationFlatMemberIndexesBuilder $flatMemberIndexesBuilder The flat member index builder.
     * @param MemberGraphDeclarationCallableFlatIndexesBuilder $callableFlatIndexesBuilder The callable flat index builder.
     */
    public function __construct(
        private MemberGraphSourceMetadataGlobalOwnerIndexesBuilder $ownerIndexesBuilder = new MemberGraphSourceMetadataGlobalOwnerIndexesBuilder(),
        private MemberGraphDeclarationSnapshotMerger $declarationSnapshotMerger = new MemberGraphDeclarationSnapshotMerger(),
        private MemberGraphDeclarationFlatMemberIndexesBuilder $flatMemberIndexesBuilder = new MemberGraphDeclarationFlatMemberIndexesBuilder(),
        private MemberGraphDeclarationCallableFlatIndexesBuilder $callableFlatIndexesBuilder = new MemberGraphDeclarationCallableFlatIndexesBuilder(),
    ) {
    }

    /**
     * Builds partial-compatible global indexes.
     *
     * @param MemberDependencyGraphPartialRebuildSourceView $sourceView The partial rebuild source view.
     * @param MemberGraphDeclarationSnapshot $cachedDeclarationSnapshot The cached declaration snapshot.
     *
     * @return MemberGraphPartialGlobalIndexes
     */
    public function build(
        MemberDependencyGraphPartialRebuildSourceView $sourceView,
        MemberGraphDeclarationSnapshot $cachedDeclarationSnapshot,
    ): MemberGraphPartialGlobalIndexes {
        $ownerIndexes = $this->ownerIndexesBuilder->build($sourceView->allSourceMetadata);
        $mergedDeclarationSnapshot = $this->declarationSnapshotMerger->merge(
            cachedSnapshot: $cachedDeclarationSnapshot,
            loadedSnapshot: $sourceView->loadedInput->loadedDeclarationSnapshot,
            filesToBuild: $sourceView->globalIndexRebuildInput->filesToBuild,
        );
        $flatMemberIndexes = $this->flatMemberIndexesBuilder->build($mergedDeclarationSnapshot);
        $callableFlatIndexes = $this->callableFlatIndexesBuilder->build($mergedDeclarationSnapshot);

        return new MemberGraphPartialGlobalIndexes(
            knownOwners: $ownerIndexes->knownOwners,
            polymorphicImplementationsIndex: $ownerIndexes->polymorphicImplementationsIndex,
            propertyTypeIndex: $flatMemberIndexes->propertyTypeIndex,
            classConstantTypeIndex: $flatMemberIndexes->classConstantTypeIndex,
            classConstantValueIndex: $flatMemberIndexes->classConstantValueIndex,
            methodReturnTypeIndex: $callableFlatIndexes->methodReturnTypeIndex,
            methodParameterTypeIndex: $callableFlatIndexes->methodParameterTypeIndex,
            functionReturnTypeIndex: $callableFlatIndexes->functionReturnTypeIndex,
            functionParameterTypeIndex: $callableFlatIndexes->functionParameterTypeIndex,
            mergedDeclarationSnapshot: $mergedDeclarationSnapshot,
        );
    }
}
