<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\GlobalIndex;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Builds known owners from cacheable virtual source metadata.
 */
final readonly class MemberGraphKnownOwnersFromSourceMetadataBuilder
{
    /**
     * Builds the known owner collection.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata The source metadata collection.
     *
     * @return KnownOwnerCollection
     */
    public function build(MemberGraphVirtualSourceMetadataCollection $sourceMetadata): KnownOwnerCollection
    {
        $knownOwners = new KnownOwnerCollection();

        foreach ($sourceMetadata as $metadata) {
            if (!$metadata->hasOwner()) {
                continue;
            }

            $knownOwners->add(new KnownOwner(
                fqcn: $metadata->ownerName,
                parentFqcn: $metadata->parentFqcn,
                kind: $metadata->ownerKind,
                isAbstract: $metadata->isAbstract,
                traits: $metadata->traits,
                interfaces: $metadata->interfaces,
                extendsInterfaces: $metadata->extendsInterfaces,
            ));
        }

        return $knownOwners;
    }
}
