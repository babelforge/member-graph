<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Resolves the declaring owner of class-like constants through inheritance, interfaces, and traits.
 */
final readonly class ClassConstantOwnerResolver
{
    /**
     * Constructor.
     *
     * @param ClassConstantTypeIndex $classConstantTypeIndex the class constant type index
     * @param KnownOwnerCollection   $knownOwners            the known owners collection
     */
    public function __construct(
        private ClassConstantTypeIndex $classConstantTypeIndex,
        private KnownOwnerCollection $knownOwners,
    ) {
    }

    /**
     * Resolves the class constant declared type through inheritance.
     *
     * @param string $owner        the starting owner
     * @param string $constantName the constant name
     */
    public function resolve(string $owner, string $constantName): SymbolCollection
    {
        return $this->resolveRecursive($owner, $constantName, []);
    }

    /**
     * Resolves one class constant owner through classes, interfaces, and traits.
     *
     * @param string              $owner        the current owner to inspect
     * @param string              $constantName the constant name
     * @param array<string, bool> $visited      the visited owners
     */
    private function resolveRecursive(
        string $owner,
        string $constantName,
        array $visited,
    ): SymbolCollection {
        $owners = new SymbolCollection();

        if ('' === $owner || isset($visited[$owner])) {
            return $owners;
        }

        $visited[$owner] = true;

        $resolved = $this->classConstantTypeIndex->get($owner, $constantName);

        if (null !== $resolved && '' !== $resolved) {
            return $owners->add($resolved);
        }

        $knownOwner = $this->knownOwners->get($owner);

        if (!$knownOwner instanceof KnownOwner) {
            return $owners;
        }

        $parentOwners = $this->resolveParentOwners($knownOwner, $constantName, $visited);

        if (!$parentOwners->isEmpty()) {
            return $parentOwners;
        }

        $interfaceOwners = $this->resolveInterfaceOwners($knownOwner, $constantName, $visited);

        if (!$interfaceOwners->isEmpty()) {
            return $interfaceOwners;
        }

        return $this->resolveTraitOwners($knownOwner, $constantName, $visited);
    }

    /**
     * Resolves parent class owners.
     *
     * @param KnownOwner          $knownOwner   the owner metadata
     * @param string              $constantName the constant name
     * @param array<string, bool> $visited      the visited owners
     */
    private function resolveParentOwners(
        KnownOwner $knownOwner,
        string $constantName,
        array $visited,
    ): SymbolCollection {
        if (null === $knownOwner->parentFqcn || '' === $knownOwner->parentFqcn) {
            return new SymbolCollection();
        }

        return $this->resolveRecursive($knownOwner->parentFqcn, $constantName, $visited);
    }

    /**
     * Resolves interface owners.
     *
     * @param KnownOwner          $knownOwner   the owner metadata
     * @param string              $constantName the constant name
     * @param array<string, bool> $visited      the visited owners
     */
    private function resolveInterfaceOwners(
        KnownOwner $knownOwner,
        string $constantName,
        array $visited,
    ): SymbolCollection {
        foreach ($knownOwner->interfaces as $interfaceOwner) {
            $interfaceOwners = $this->resolveRecursive($interfaceOwner, $constantName, $visited);

            if (!$interfaceOwners->isEmpty()) {
                return $interfaceOwners;
            }
        }

        foreach ($knownOwner->extendsInterfaces as $interfaceOwner) {
            $interfaceOwners = $this->resolveRecursive($interfaceOwner, $constantName, $visited);

            if (!$interfaceOwners->isEmpty()) {
                return $interfaceOwners;
            }
        }

        return new SymbolCollection();
    }

    /**
     * Resolves trait owners.
     *
     * @param KnownOwner          $knownOwner   the owner metadata
     * @param string              $constantName the constant name
     * @param array<string, bool> $visited      the visited owners
     */
    private function resolveTraitOwners(
        KnownOwner $knownOwner,
        string $constantName,
        array $visited,
    ): SymbolCollection {
        foreach ($knownOwner->traits as $traitOwner) {
            $traitOwners = $this->resolveRecursive($traitOwner, $constantName, $visited);

            if (!$traitOwners->isEmpty()) {
                return $traitOwners;
            }
        }

        return new SymbolCollection();
    }
}
