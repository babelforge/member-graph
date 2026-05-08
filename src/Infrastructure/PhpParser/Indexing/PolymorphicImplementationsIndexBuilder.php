<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing;

use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;

/**
 * Builds the reverse polymorphic index from known owners.
 *
 * Indexed contracts:
 * - interfaces
 * - abstract classes
 *
 * Indexed implementations:
 * - concrete classes only
 */
final readonly class PolymorphicImplementationsIndexBuilder
{
    /**
     * Builds the reverse index from the given known owners collection.
     *
     * @param KnownOwnerCollection $knownOwners the known owners collection
     */
    public function build(KnownOwnerCollection $knownOwners): PolymorphicImplementationsIndex
    {
        $index = new PolymorphicImplementationsIndex();

        foreach ($knownOwners->all() as $knownOwner) {
            if ($this->isInterface($knownOwner)) {
                continue;
            }

            if ($this->isAbstract($knownOwner)) {
                continue;
            }

            foreach ($knownOwner->interfaces as $interfaceFqcn) {
                $index->addImplementation($interfaceFqcn, $knownOwner->fqcn);
            }

            foreach ($this->collectAbstractAncestors($knownOwners, $knownOwner) as $abstractAncestorFqcn) {
                $index->addImplementation($abstractAncestorFqcn, $knownOwner->fqcn);
            }

            foreach ($this->collectAllImplementedInterfaces($knownOwners, $knownOwner) as $interfaceFqcn) {
                $index->addImplementation($interfaceFqcn, $knownOwner->fqcn);
            }
        }

        return $index;
    }

    /**
     * Collects all abstract ancestors for one concrete class.
     *
     * @param KnownOwnerCollection $knownOwners the known owners collection
     * @param KnownOwner           $knownOwner  the owner to inspect
     *
     * @return list<string>
     */
    private function collectAbstractAncestors(KnownOwnerCollection $knownOwners, KnownOwner $knownOwner): array
    {
        $resolved = [];
        $current = $knownOwner->parentFqcn;
        $visited = [];

        while (null !== $current && '' !== $current && !isset($visited[$current])) {
            $visited[$current] = true;

            $parentOwner = $knownOwners->get($current);

            if (null === $parentOwner) {
                break;
            }

            if ($this->isAbstract($parentOwner)) {
                $resolved[$current] = $current;
            }

            $current = $parentOwner->parentFqcn;
        }

        return array_values($resolved);
    }

    /**
     * @return list<string>
     */
    private function collectAllImplementedInterfaces(
        KnownOwnerCollection $knownOwners,
        KnownOwner $knownOwner,
    ): array {
        $resolved = [];
        $current = $knownOwner;
        $visited = [];

        while (!isset($visited[$current->fqcn])) {
            $visited[$current->fqcn] = true;

            foreach ($current->interfaces as $interfaceFqcn) {
                $resolved[$interfaceFqcn] = $interfaceFqcn;

                foreach ($this->collectExtendedInterfaces($knownOwners, $interfaceFqcn) as $extendedInterfaceFqcn) {
                    $resolved[$extendedInterfaceFqcn] = $extendedInterfaceFqcn;
                }
            }

            if (null === $current->parentFqcn || '' === $current->parentFqcn) {
                break;
            }

            $parent = $knownOwners->get($current->parentFqcn);

            if (null === $parent) {
                break;
            }

            $current = $parent;
        }

        return array_values($resolved);
    }

    /**
     * Collects all interfaces extended by one interface.
     *
     * @param KnownOwnerCollection $knownOwners   the known owners collection
     * @param string               $interfaceFqcn the interface FQCN
     * @param array<string, true>  $visited       the already visited interfaces
     *
     * @return list<string>
     */
    private function collectExtendedInterfaces(
        KnownOwnerCollection $knownOwners,
        string $interfaceFqcn,
        array $visited = [],
    ): array {
        if (isset($visited[$interfaceFqcn])) {
            return [];
        }

        $visited[$interfaceFqcn] = true;
        $interfaceOwner = $knownOwners->get($interfaceFqcn);

        if (null === $interfaceOwner) {
            return [];
        }

        $resolved = [];

        foreach (array_merge($interfaceOwner->interfaces, $interfaceOwner->extendsInterfaces) as $extendedInterfaceFqcn) {
            $resolved[$extendedInterfaceFqcn] = $extendedInterfaceFqcn;

            foreach ($this->collectExtendedInterfaces($knownOwners, $extendedInterfaceFqcn, $visited) as $nestedInterfaceFqcn) {
                $resolved[$nestedInterfaceFqcn] = $nestedInterfaceFqcn;
            }
        }

        return array_values($resolved);
    }

    /**
     * Indicates whether the given owner represents an interface.
     *
     * @param KnownOwner $knownOwner the owner to inspect
     */
    private function isInterface(KnownOwner $knownOwner): bool
    {
        return OwnerKind::INTERFACE === $knownOwner->kind;
    }

    /**
     * Indicates whether the given owner represents an abstract class.
     *
     * @param KnownOwner $knownOwner the owner to inspect
     */
    private function isAbstract(KnownOwner $knownOwner): bool
    {
        return true === $knownOwner->isAbstract;
    }
}
