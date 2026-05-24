<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\GlobalIndex;

use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Builds known owners from cacheable virtual source metadata.
 */
final readonly class MemberGraphKnownOwnersFromSourceMetadataBuilder
{
    /**
     * Builds the known owner collection.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata the source metadata collection
     */
    public function build(MemberGraphVirtualSourceMetadataCollection $sourceMetadata): KnownOwnerCollection
    {
        $knownOwners = new KnownOwnerCollection();

        foreach ($sourceMetadata as $metadata) {
            if (null === $metadata->ownerName || null === $metadata->ownerKind) {
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
