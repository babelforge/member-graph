<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Polymorphism;

/**
 * Stores concrete implementations for interface contracts.
 */
final class PolymorphicImplementationsIndex
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $items = [];

    /**
     * Registers one implementation for one contract.
     *
     * @param string $contract       the contract FQCN
     * @param string $implementation the implementation FQCN
     */
    public function addImplementation(string $contract, string $implementation): void
    {
        $this->items[$contract][$implementation] = true;
    }

    /**
     * Indicates whether the given contract has known implementations.
     *
     * @param string $contract the contract FQCN
     */
    public function hasImplementations(string $contract): bool
    {
        return isset($this->items[$contract]) && [] !== $this->items[$contract];
    }

    /**
     * Returns all known implementations for the given contract.
     *
     * @param string $contract the contract FQCN
     *
     * @return list<string>
     */
    public function getImplementations(string $contract): array
    {
        return array_values(array_unique(array_keys($this->items[$contract] ?? [])));
    }

    /**
     * Returns all indexed implementations.
     *
     * @return array<string, array<string, true>>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return string[]
     */
    public function getAllTargets(string $owner): array
    {
        $targets = [$owner];

        if ($this->hasImplementations($owner)) {
            $targets = array_merge($targets, $this->getImplementations($owner));
        }

        return array_values(array_unique($targets));
    }
}
