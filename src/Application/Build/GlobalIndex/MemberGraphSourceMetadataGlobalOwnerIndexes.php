<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries global owner indexes rebuilt from cacheable source metadata.
 */
final readonly class MemberGraphSourceMetadataGlobalOwnerIndexes
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection            $knownOwners                     the known owners rebuilt from source metadata
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex the polymorphic implementations index
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners,
        public PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
    ) {
    }
}
