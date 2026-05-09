<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Fragment;

use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Project\AvailableMemberProjector;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\PolymorphicImplementationsIndexBuilder;

/**
 * Merges member graph fragments into one member dependency graph.
 *
 * Fragments currently carry shared global indexes. The merger preserves those
 * global structures from the first fragment while merging file-scoped facts.
 */
final readonly class MemberGraphFragmentMerger
{
    /**
     * Merges graph fragments into one graph.
     *
     * @param MemberGraphFragmentCollection $fragments the graph fragments to merge
     */
    public function merge(MemberGraphFragmentCollection $fragments): MemberDependencyGraph
    {
        return $this->mergeFileScopedFacts($fragments);
    }

    /**
     * Merges graph fragments while rebuilding global facts from explicit known owners.
     *
     * @param MemberGraphFragmentCollection $fragments   the graph fragments to merge
     * @param KnownOwnerCollection          $knownOwners the authoritative known owners
     */
    public function mergeWithKnownOwners(
        MemberGraphFragmentCollection $fragments,
        KnownOwnerCollection $knownOwners,
    ): MemberDependencyGraph {
        $mergedGraph = $this->mergeFileScopedFacts($fragments);

        return new MemberDependencyGraph(
            declarations: $mergedGraph->declarations,
            usages: $mergedGraph->usages,
            parameterUsages: $mergedGraph->parameterUsages,
            availableMembers: new AvailableMemberProjector()->project($mergedGraph->declarations, $knownOwners),
            knownOwners: $knownOwners,
            interfaceImplementationsIndex: new PolymorphicImplementationsIndexBuilder()->build($knownOwners),
            dependencyGraphIssues: $mergedGraph->dependencyGraphIssues,
            ownerDeclarations: $mergedGraph->ownerDeclarations,
            ownerUsages: $mergedGraph->ownerUsages,
        );
    }

    /**
     * Merges file-scoped graph facts from fragments.
     *
     * @param MemberGraphFragmentCollection $fragments the graph fragments to merge
     */
    private function mergeFileScopedFacts(MemberGraphFragmentCollection $fragments): MemberDependencyGraph
    {
        $declarations = new MemberDeclarationCollection();
        $usages = new MemberUsageCollection();
        $parameterUsages = new ParameterUsageCollection();
        $ownerDeclarations = new OwnerDeclarationCollection();
        $ownerUsages = new OwnerUsageCollection();
        $firstFragment = null;

        foreach ($fragments as $fragment) {
            $firstFragment ??= $fragment;

            foreach ($fragment->declarations->all() as $declaration) {
                $declarations->add($declaration);
            }

            foreach ($fragment->usages->all() as $usagesByTarget) {
                foreach ($usagesByTarget as $usage) {
                    $usages->add($usage);
                }
            }

            foreach ($fragment->parameterUsages->all() as $usagesByTarget) {
                foreach ($usagesByTarget as $usage) {
                    $parameterUsages->add($usage);
                }
            }

            foreach ($fragment->ownerDeclarations->all() as $declaration) {
                $ownerDeclarations->add($declaration);
            }

            foreach ($fragment->ownerUsages->all() as $usagesByTarget) {
                foreach ($usagesByTarget as $usage) {
                    $ownerUsages->add($usage);
                }
            }
        }

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: $usages,
            parameterUsages: $parameterUsages,
            availableMembers: $firstFragment->availableMembers ?? new AvailableMemberCollection(),
            knownOwners: $firstFragment->knownOwners ?? new KnownOwnerCollection(),
            interfaceImplementationsIndex: $firstFragment->interfaceImplementationsIndex ?? new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: $firstFragment->dependencyGraphIssues ?? new MemberGraphIssueCollection(),
            ownerDeclarations: $ownerDeclarations,
            ownerUsages: $ownerUsages,
        );
    }
}
