<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance;

use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Resolves inherited method nodes from structural method indexes.
 */
final readonly class ParentMethodNodeResolver
{
    /**
     * @param KnownOwnerCollection $knownOwners The known owners collection.
     * @param MethodNodeIndex $methodNodeIndex The method node index.
     */
    public function __construct(
        private KnownOwnerCollection $knownOwners,
        private MethodNodeIndex $methodNodeIndex,
    ) {
    }

    /**
     * Resolves the first inherited parent method node, if any.
     *
     * Candidate owners are ordered from nearest parent to farthest parent.
     *
     * @param string $owner The current owner FQCN.
     * @param string $methodName The current method name.
     *
     * @return ClassMethod|null
     */
    public function resolveFirstParent(string $owner, string $methodName): ?ClassMethod
    {
        $parents = $this->resolveAll($owner, $methodName);

        return $parents[0] ?? null;
    }

    /**
     * Resolves all inherited parent method nodes, if any.
     *
     * @param string $owner The current owner FQCN.
     * @param string $methodName The current method name.
     *
     * @return array<int, ClassMethod>
     */
    public function resolveAllParents(string $owner, string $methodName): array
    {
        return $this->resolveAll($owner, $methodName);
    }

    /**
     * Resolves all inherited methods for one owner and one method name.
     *
     * The returned array is ordered from nearest parent to farthest parent.
     *
     * @param string $owner The current owner FQCN.
     * @param string $methodName The method name.
     *
     * @return array<int, ClassMethod>
     */
    public function resolveAll(string $owner, string $methodName): array
    {
        $resolved = [];
        $visitedOwners = [];

        foreach ($this->resolveCandidateOwners($owner, $visitedOwners) as $candidateOwner) {
            $parentMethod = $this->methodNodeIndex->get($candidateOwner, $methodName);

            if ($parentMethod instanceof ClassMethod) {
                $resolved[] = $parentMethod;
            }
        }

        return $resolved;
    }

    /**
     * Resolves candidate owners that may provide an inherited PHPDoc method.
     *
     * The order keeps class inheritance first, then interface contracts, then
     * trait methods. This preserves parent-class priority while allowing
     * interface and trait docs to fill gaps.
     *
     * @param string $owner The current owner FQCN.
     * @param array<string, true> $visitedOwners The owners already visited.
     *
     * @return list<string>
     */
    private function resolveCandidateOwners(string $owner, array &$visitedOwners): array
    {
        if (isset($visitedOwners[$owner])) {
            return [];
        }

        $visitedOwners[$owner] = true;
        $knownOwner = $this->knownOwners->get($owner);

        if (null === $knownOwner) {
            return [];
        }

        $candidates = [];
        $parentOwner = $this->resolveParentOwner($owner);

        if (null !== $parentOwner && '' !== $parentOwner) {
            $candidates[] = $parentOwner;
            $candidates = array_merge(
                $candidates,
                $this->resolveCandidateOwners($parentOwner, $visitedOwners),
            );
        }

        foreach ($knownOwner->interfaces as $interfaceOwner) {
            $candidates[] = $interfaceOwner;
            $newOwners = $this->resolveInterfaceCandidateOwners($interfaceOwner, $visitedOwners);
            foreach ($newOwners as $newOwner) {
                $candidates[] = $newOwner;
            }
        }

        foreach ($knownOwner->traits as $traitOwner) {
            $candidates[] = $traitOwner;
            $newOwners = $this->resolveTraitCandidateOwners($traitOwner, $visitedOwners);
            foreach ($newOwners as $newOwner) {
                $candidates[] = $newOwner;
            }
        }

        return $this->uniqueOwners($candidates);
    }

    /**
     * Resolves candidate owners from one interface and its extended interfaces.
     *
     * @param string $interfaceOwner The interface FQCN.
     * @param array<string, true> $visitedOwners The owners already visited.
     *
     * @return list<string>
     */
    private function resolveInterfaceCandidateOwners(string $interfaceOwner, array &$visitedOwners): array
    {
        if (isset($visitedOwners[$interfaceOwner])) {
            return [];
        }

        $visitedOwners[$interfaceOwner] = true;
        $knownOwner = $this->knownOwners->get($interfaceOwner);

        if (null === $knownOwner) {
            return [];
        }

        $candidates = [];

        foreach ($this->resolveExtendedInterfaceOwners($knownOwner->interfaces, $knownOwner->extendsInterfaces) as $extendedInterfaceOwner) {
            $candidates[] = $extendedInterfaceOwner;
            $newOwners = $this->resolveInterfaceCandidateOwners($extendedInterfaceOwner, $visitedOwners);
            foreach ($newOwners as $newOwner) {
                $candidates[] = $newOwner;
            }
        }

        return $this->uniqueOwners($candidates);
    }

    /**
     * Resolves candidate owners from one trait and its used traits.
     *
     * @param string $traitOwner The trait FQCN.
     * @param array<string, true> $visitedOwners The owners already visited.
     *
     * @return list<string>
     */
    private function resolveTraitCandidateOwners(string $traitOwner, array &$visitedOwners): array
    {
        if (isset($visitedOwners[$traitOwner])) {
            return [];
        }

        $visitedOwners[$traitOwner] = true;
        $knownOwner = $this->knownOwners->get($traitOwner);

        if (null === $knownOwner) {
            return [];
        }

        $candidates = [];

        foreach ($knownOwner->traits as $nestedTraitOwner) {
            $candidates[] = $nestedTraitOwner;
            $newOwners = $this->resolveTraitCandidateOwners($nestedTraitOwner, $visitedOwners);
            foreach ($newOwners as $newOwner) {
                $candidates[] = $newOwner;
            }
        }

        return $this->uniqueOwners($candidates);
    }

    /**
     * Resolves extended interface owners from both known owner fields.
     *
     * Older collection code stores interface extends in `interfaces`; newer
     * callers may use `extendsInterfaces`. Supporting both keeps this resolver
     * compatible with the current graph data.
     *
     * @param list<string> $interfaces The interface owners.
     * @param list<string> $extendsInterfaces The extended interface owners.
     *
     * @return list<string>
     */
    private function resolveExtendedInterfaceOwners(array $interfaces, array $extendsInterfaces): array
    {
        return $this->uniqueOwners(array_merge($interfaces, $extendsInterfaces));
    }

    /**
     * Returns owners without duplicates while preserving their original order.
     *
     * @param list<string> $owners The owners to filter.
     *
     * @return list<string>
     */
    private function uniqueOwners(array $owners): array
    {
        $uniqueOwners = [];
        $seenOwners = [];

        foreach ($owners as $owner) {
            if ('' === $owner || isset($seenOwners[$owner])) {
                continue;
            }

            $seenOwners[$owner] = true;
            $uniqueOwners[] = $owner;
        }

        return $uniqueOwners;
    }

    /**
     * Resolves the direct parent owner for one owner.
     *
     * @param string $owner The current owner FQCN.
     *
     * @return string|null
     */
    private function resolveParentOwner(string $owner): ?string
    {
        $knownOwner = $this->knownOwners->get($owner);

        if (null === $knownOwner) {
            return null;
        }

        return '' !== $knownOwner->parentFqcn ? $knownOwner->parentFqcn : null;
    }
}
