<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

/**
 * Provides direct and transitive owner dependency graph queries.
 */
final class OwnerDependencyGraph
{
    /**
     * @var array<string, OwnerDependencyCollection>
     */
    private array $outgoingByOwner = [];

    /**
     * @var array<string, OwnerDependencyCollection>
     */
    private array $incomingByOwner = [];

    private OwnerDependencyNodeCollection $nodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->nodes = new OwnerDependencyNodeCollection();
    }

    /**
     * Creates an owner dependency graph from an owner dependency collection.
     *
     * @param OwnerDependencyCollection $dependencies The dependencies to index.
     *
     * @return self
     */
    public static function fromDependencies(OwnerDependencyCollection $dependencies): self
    {
        $graph = new self();

        foreach ($dependencies as $dependency) {
            $graph->add($dependency);
        }

        return $graph;
    }

    /**
     * Adds one dependency to the graph.
     *
     * @param OwnerDependency $dependency The dependency to add.
     *
     * @return void
     */
    public function add(OwnerDependency $dependency): void
    {
        $sourceOwner = $dependency->sourceOwner;
        $targetOwner = $dependency->target->owner;

        if ('' === $sourceOwner || '' === $targetOwner) {
            return;
        }

        $this->nodes->add($sourceOwner);
        $this->nodes->add($targetOwner);
        $this->outgoingCollectionFor($sourceOwner)->add($dependency);
        $this->incomingCollectionFor($targetOwner)->add($dependency);
    }

    /**
     * Returns all owner nodes found in the graph.
     *
     * @return OwnerDependencyNodeCollection
     */
    public function nodes(): OwnerDependencyNodeCollection
    {
        $nodes = new OwnerDependencyNodeCollection();

        foreach ($this->nodes as $node) {
            $nodes->add($node);
        }

        return $nodes;
    }

    /**
     * Returns direct outgoing dependencies for one owner.
     *
     * @param string $owner The source owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function outgoing(string $owner): OwnerDependencyCollection
    {
        return $this->copyDependencies($this->outgoingByOwner[$owner] ?? new OwnerDependencyCollection());
    }

    /**
     * Returns direct incoming dependencies for one owner.
     *
     * @param string $owner The target owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function incoming(string $owner): OwnerDependencyCollection
    {
        return $this->copyDependencies($this->incomingByOwner[$owner] ?? new OwnerDependencyCollection());
    }

    /**
     * Returns transitive outgoing dependencies reachable from one owner.
     *
     * @param string $owner The source owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function transitiveOutgoing(string $owner): OwnerDependencyCollection
    {
        $dependencies = new OwnerDependencyCollection();
        $visitedOwners = [];

        $this->collectTransitiveOutgoing($owner, $visitedOwners, $dependencies);

        return $dependencies;
    }

    /**
     * Returns transitive incoming dependencies reaching one owner.
     *
     * @param string $owner The target owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    public function transitiveIncoming(string $owner): OwnerDependencyCollection
    {
        $dependencies = new OwnerDependencyCollection();
        $visitedOwners = [];

        $this->collectTransitiveIncoming($owner, $visitedOwners, $dependencies);

        return $dependencies;
    }

    /**
     * Recursively collects outgoing dependencies while preventing owner cycles.
     *
     * @param string $owner The owner currently being explored.
     * @param array<string, true> $visitedOwners The already visited owners.
     * @param OwnerDependencyCollection $dependencies The dependency accumulator.
     *
     * @return void
     */
    private function collectTransitiveOutgoing(
        string $owner,
        array &$visitedOwners,
        OwnerDependencyCollection $dependencies,
    ): void {
        if (isset($visitedOwners[$owner])) {
            return;
        }

        $visitedOwners[$owner] = true;

        foreach ($this->outgoingByOwner[$owner] ?? [] as $dependency) {
            $dependencies->add($dependency);
            $this->collectTransitiveOutgoing($dependency->target->owner, $visitedOwners, $dependencies);
        }
    }

    /**
     * Recursively collects incoming dependencies while preventing owner cycles.
     *
     * @param string $owner The owner currently being explored.
     * @param array<string, true> $visitedOwners The already visited owners.
     * @param OwnerDependencyCollection $dependencies The dependency accumulator.
     *
     * @return void
     */
    private function collectTransitiveIncoming(
        string $owner,
        array &$visitedOwners,
        OwnerDependencyCollection $dependencies,
    ): void {
        if (isset($visitedOwners[$owner])) {
            return;
        }

        $visitedOwners[$owner] = true;

        foreach ($this->incomingByOwner[$owner] ?? [] as $dependency) {
            $dependencies->add($dependency);
            $this->collectTransitiveIncoming($dependency->sourceOwner, $visitedOwners, $dependencies);
        }
    }

    /**
     * Returns the outgoing collection for one owner, creating it when needed.
     *
     * @param string $owner The owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    private function outgoingCollectionFor(string $owner): OwnerDependencyCollection
    {
        return $this->outgoingByOwner[$owner] ??= new OwnerDependencyCollection();
    }

    /**
     * Returns the incoming collection for one owner, creating it when needed.
     *
     * @param string $owner The owner FQCN.
     *
     * @return OwnerDependencyCollection
     */
    private function incomingCollectionFor(string $owner): OwnerDependencyCollection
    {
        return $this->incomingByOwner[$owner] ??= new OwnerDependencyCollection();
    }

    /**
     * Copies dependencies into a detached collection.
     *
     * @param OwnerDependencyCollection $dependencies The dependencies to copy.
     *
     * @return OwnerDependencyCollection
     */
    private function copyDependencies(OwnerDependencyCollection $dependencies): OwnerDependencyCollection
    {
        $copy = new OwnerDependencyCollection();

        foreach ($dependencies as $dependency) {
            $copy->add($dependency);
        }

        return $copy;
    }
}
