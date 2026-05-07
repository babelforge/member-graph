<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Project;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsage;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Projects trait self-usages onto consuming classes.
 *
 * This projector handles usages collected in traits such as:
 * - $this->method()
 * - $this->property
 *
 * Because the per-file member builder resolves "$this" to the current trait FQCN,
 * those raw usages are initially indexed on the trait owner. This projector
 * duplicates them onto each known consuming class so downstream collectors can
 * resolve them through member lineage.
 */
final readonly class TraitSelfUsageProjector
{
    /**
     * Projects raw trait self-usages into consuming-class usages.
     *
     * @param MemberUsageCollection $usages The merged member usages.
     * @param ParameterUsageCollection $parameterUsages The merged parameter usages.
     * @param KnownOwnerCollection $knownOwners The merged known owners.
     *
     * @return void
     */
    public function project(
        MemberUsageCollection $usages,
        ParameterUsageCollection $parameterUsages,
        KnownOwnerCollection $knownOwners,
    ): void {
        foreach ($knownOwners->all() as $knownOwner) {
            foreach ($knownOwner->traits as $traitFqcn) {
                $this->projectTraitMemberUsagesForOwner(
                    usages: $usages,
                    knownOwner: $knownOwner,
                    traitFqcn: $traitFqcn,
                );

                $this->projectTraitParameterUsagesForOwner(
                    parameterUsages: $parameterUsages,
                    knownOwner: $knownOwner,
                    traitFqcn: $traitFqcn,
                );
            }
        }
    }

    /**
     * Projects trait member usages for one consuming owner.
     *
     * @param MemberUsageCollection $usages The merged member usages.
     * @param KnownOwner $knownOwner The consuming owner.
     * @param string $traitFqcn The trait FQCN.
     *
     * @return void
     */
    private function projectTraitMemberUsagesForOwner(
        MemberUsageCollection $usages,
        KnownOwner $knownOwner,
        string $traitFqcn,
    ): void {
        $snapshots = $usages->all();

        foreach ($snapshots as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if (!$usage instanceof MemberUsage) {
                    continue;
                }

                if (!$this->isTraitSelfUsage($usage, $traitFqcn)) {
                    continue;
                }

                $projectedSourceSymbol = $this->projectedSourceSymbol(
                    sourceSymbol: $usage->sourceSymbol,
                    traitFqcn: $traitFqcn,
                    ownerFqcn: $knownOwner->fqcn,
                );

                if (null === $projectedSourceSymbol) {
                    continue;
                }

                $usages->add(new MemberUsage(
                    sourceSymbol: $projectedSourceSymbol,
                    target: new MemberId(
                        owner: $knownOwner->fqcn,
                        name: $usage->target->name,
                        type: $usage->target->type,
                    ),
                    type: $usage->type,
                    file: $usage->file,
                    sourceNodeId: $usage->sourceNodeId,
                ));
            }
        }
    }

    /**
     * Projects trait parameter usages for one consuming owner.
     *
     * @param ParameterUsageCollection $parameterUsages The merged parameter usages.
     * @param KnownOwner $knownOwner The consuming owner.
     * @param string $traitFqcn The trait FQCN.
     *
     * @return void
     */
    private function projectTraitParameterUsagesForOwner(
        ParameterUsageCollection $parameterUsages,
        KnownOwner $knownOwner,
        string $traitFqcn,
    ): void {
        $snapshots = $parameterUsages->all();

        foreach ($snapshots as $parameterUsagesByTarget) {
            foreach ($parameterUsagesByTarget as $parameterUsage) {
                if (!$parameterUsage instanceof ParameterUsage) {
                    continue;
                }

                if (!$this->isTraitSelfParameterUsage($parameterUsage, $traitFqcn)) {
                    continue;
                }

                $projectedSourceSymbol = $this->projectedSourceSymbol(
                    sourceSymbol: $parameterUsage->sourceSymbol,
                    traitFqcn: $traitFqcn,
                    ownerFqcn: $knownOwner->fqcn,
                );

                if (null === $projectedSourceSymbol) {
                    continue;
                }

                $parameterUsages->add(new ParameterUsage(
                    sourceSymbol: $projectedSourceSymbol,
                    target: new ParameterId(
                        owner: $knownOwner->fqcn,
                        functionLikeName: $parameterUsage->target->functionLikeName,
                        parameterName: $parameterUsage->target->parameterName,
                    ),
                    type: $parameterUsage->type,
                    file: $parameterUsage->file,
                    sourceNodeId: $parameterUsage->sourceNodeId,
                ));
            }
        }
    }

    /**
     * Indicates whether one source symbol belongs to the given trait.
     *
     * @param string $sourceSymbol The source symbol to inspect.
     * @param string $traitFqcn The trait FQCN.
     *
     * @return bool
     */
    private function isTraitSourceSymbol(string $sourceSymbol, string $traitFqcn): bool
    {
        return str_starts_with($sourceSymbol, $traitFqcn . '::');
    }

    /**
     * Indicates whether the given member usage is a trait self-usage.
     *
     * @param MemberUsage $usage The usage to inspect.
     * @param string $traitFqcn The trait FQCN.
     *
     * @return bool
     */
    private function isTraitSelfUsage(MemberUsage $usage, string $traitFqcn): bool
    {
        if (!$this->isTraitSourceSymbol($usage->sourceSymbol, $traitFqcn)) {
            return false;
        }

        return $usage->target->owner === $traitFqcn;
    }

    /**
     * Indicates whether the given parameter usage is a trait self-parameter usage.
     *
     * @param ParameterUsage $parameterUsage The parameter usage to inspect.
     * @param string $traitFqcn The trait FQCN.
     *
     * @return bool
     */
    private function isTraitSelfParameterUsage(ParameterUsage $parameterUsage, string $traitFqcn): bool
    {
        if (!$this->isTraitSourceSymbol($parameterUsage->sourceSymbol, $traitFqcn)) {
            return false;
        }

        return $parameterUsage->target->owner === $traitFqcn;
    }

    /**
     * Projects a trait source symbol onto one consuming owner.
     *
     * @param string $sourceSymbol The source symbol.
     * @param string $traitFqcn The trait FQCN.
     * @param string $ownerFqcn The consuming owner FQCN.
     *
     * @return string|null
     */
    private function projectedSourceSymbol(string $sourceSymbol, string $traitFqcn, string $ownerFqcn): ?string
    {
        if (!$this->isTraitSourceSymbol($sourceSymbol, $traitFqcn)) {
            return null;
        }

        $sourceMethod = $this->extractSourceMethodName($sourceSymbol);

        if (null === $sourceMethod) {
            return null;
        }

        return $ownerFqcn . '::' . $sourceMethod;
    }

    /**
     * Extracts the source method name from a source symbol.
     *
     * @param string $sourceSymbol The source symbol.
     *
     * @return string|null
     */
    private function extractSourceMethodName(string $sourceSymbol): ?string
    {
        $parts = explode('::', $sourceSymbol, 2);

        if (count($parts) !== 2 || '' === $parts[1]) {
            return null;
        }

        return $parts[1];
    }
}
