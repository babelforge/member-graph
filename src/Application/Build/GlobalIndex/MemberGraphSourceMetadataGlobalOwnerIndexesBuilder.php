<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\PolymorphicImplementationsIndexBuilder;

/**
 * Builds global owner indexes from cacheable source metadata.
 */
final readonly class MemberGraphSourceMetadataGlobalOwnerIndexesBuilder
{
    /**
     * Constructor.
     *
     * @param MemberGraphKnownOwnersFromSourceMetadataBuilder $knownOwnersBuilder                     the known owners builder
     * @param PolymorphicImplementationsIndexBuilder          $polymorphicImplementationsIndexBuilder the polymorphic index builder
     */
    public function __construct(
        private MemberGraphKnownOwnersFromSourceMetadataBuilder $knownOwnersBuilder = new MemberGraphKnownOwnersFromSourceMetadataBuilder(),
        private PolymorphicImplementationsIndexBuilder $polymorphicImplementationsIndexBuilder = new PolymorphicImplementationsIndexBuilder(),
    ) {
    }

    /**
     * Builds global owner indexes.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata the complete source metadata view
     */
    public function build(
        MemberGraphVirtualSourceMetadataCollection $sourceMetadata,
    ): MemberGraphSourceMetadataGlobalOwnerIndexes {
        $knownOwners = $this->knownOwnersBuilder->build($sourceMetadata);

        return new MemberGraphSourceMetadataGlobalOwnerIndexes(
            knownOwners: $knownOwners,
            polymorphicImplementationsIndex: $this->polymorphicImplementationsIndexBuilder->build($knownOwners),
        );
    }
}
