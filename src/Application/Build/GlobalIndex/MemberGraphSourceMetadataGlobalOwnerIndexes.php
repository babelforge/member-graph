<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Carries global owner indexes rebuilt from cacheable source metadata.
 */
final readonly class MemberGraphSourceMetadataGlobalOwnerIndexes
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection $knownOwners The known owners rebuilt from source metadata.
     * @param PolymorphicImplementationsIndex $polymorphicImplementationsIndex The polymorphic implementations index.
     */
    public function __construct(
        public KnownOwnerCollection $knownOwners,
        public PolymorphicImplementationsIndex $polymorphicImplementationsIndex,
    ) {
    }
}
