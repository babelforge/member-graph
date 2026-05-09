<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph;

use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;

/**
 * Accumulates partial member graphs produced from individual virtual files.
 */
final readonly class PartialMemberGraphAccumulator
{
    private MemberDeclarationCollection $declarations;
    private MemberUsageCollection $usages;
    private ParameterUsageCollection $parameterUsages;
    private OwnerDeclarationCollection $ownerDeclarations;
    private OwnerUsageCollection $ownerUsages;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->declarations = new MemberDeclarationCollection();
        $this->usages = new MemberUsageCollection();
        $this->parameterUsages = new ParameterUsageCollection();
        $this->ownerDeclarations = new OwnerDeclarationCollection();
        $this->ownerUsages = new OwnerUsageCollection();
    }

    /**
     * Adds one partial member graph to the accumulated collections.
     *
     * @param MemberDependencyGraph $partialGraph the partial graph to merge
     */
    public function addPartialGraph(MemberDependencyGraph $partialGraph): void
    {
        $this->mergeDeclarations($partialGraph->declarations);
        $this->mergeUsages($partialGraph->usages);
        $this->mergeParameterUsages($partialGraph->parameterUsages);
        $this->mergeOwnerDeclarations($partialGraph->ownerDeclarations);
        $this->mergeOwnerUsages($partialGraph->ownerUsages);
    }

    /**
     * Returns the accumulated member declarations.
     */
    public function declarations(): MemberDeclarationCollection
    {
        return $this->declarations;
    }

    /**
     * Returns the accumulated member usages.
     */
    public function usages(): MemberUsageCollection
    {
        return $this->usages;
    }

    /**
     * Returns the accumulated parameter usages.
     */
    public function parameterUsages(): ParameterUsageCollection
    {
        return $this->parameterUsages;
    }

    /**
     * Returns the accumulated owner declarations.
     */
    public function ownerDeclarations(): OwnerDeclarationCollection
    {
        return $this->ownerDeclarations;
    }

    /**
     * Returns the accumulated owner usages.
     */
    public function ownerUsages(): OwnerUsageCollection
    {
        return $this->ownerUsages;
    }

    /**
     * Merges declarations from one partial collection.
     *
     * @param MemberDeclarationCollection $source the source collection
     */
    private function mergeDeclarations(MemberDeclarationCollection $source): void
    {
        foreach ($source->all() as $declaration) {
            $this->declarations->add($declaration);
        }
    }

    /**
     * Merges usages from one partial collection.
     *
     * @param MemberUsageCollection $source the source collection
     */
    private function mergeUsages(MemberUsageCollection $source): void
    {
        foreach ($source->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $this->usages->add($usage);
            }
        }
    }

    /**
     * Merges parameter usages from one partial collection.
     *
     * @param ParameterUsageCollection $source the source collection
     */
    private function mergeParameterUsages(ParameterUsageCollection $source): void
    {
        foreach ($source->all() as $parameterUsagesByTarget) {
            foreach ($parameterUsagesByTarget as $parameterUsage) {
                $this->parameterUsages->add($parameterUsage);
            }
        }
    }

    /**
     * Merges owner declarations from one partial collection.
     *
     * @param OwnerDeclarationCollection $source the source collection
     */
    private function mergeOwnerDeclarations(OwnerDeclarationCollection $source): void
    {
        foreach ($source->all() as $declaration) {
            $this->ownerDeclarations->add($declaration);
        }
    }

    /**
     * Merges owner usages from one partial collection.
     *
     * @param OwnerUsageCollection $source the source collection
     */
    private function mergeOwnerUsages(OwnerUsageCollection $source): void
    {
        foreach ($source->all() as $ownerUsagesByTarget) {
            foreach ($ownerUsagesByTarget as $ownerUsage) {
                $this->ownerUsages->add($ownerUsage);
            }
        }
    }
}
