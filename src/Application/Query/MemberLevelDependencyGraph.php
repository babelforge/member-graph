<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Provides direct and transitive member-level dependency graph queries.
 */
final class MemberLevelDependencyGraph
{
    /**
     * @var array<string, MemberDependencyCollection>
     */
    private array $outgoingByMember = [];

    /**
     * @var array<string, MemberDependencyCollection>
     */
    private array $incomingByMember = [];

    private MemberIdCollection $nodes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->nodes = new MemberIdCollection();
    }

    /**
     * Creates a member dependency graph from a member dependency collection.
     *
     * @param MemberDependencyCollection $dependencies The dependencies to index.
     *
     * @return self
     */
    public static function fromDependencies(MemberDependencyCollection $dependencies): self
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
     * @param MemberDependency $dependency The dependency to add.
     *
     * @return void
     */
    public function add(MemberDependency $dependency): void
    {
        $this->nodes->add($dependency->source);
        $this->nodes->add($dependency->target);
        $this->outgoingCollectionFor($dependency->source->hash())->add($dependency);
        $this->incomingCollectionFor($dependency->target->hash())->add($dependency);
    }

    /**
     * Returns all member nodes found in the graph.
     *
     * @return MemberIdCollection
     */
    public function nodes(): MemberIdCollection
    {
        $nodes = new MemberIdCollection();

        foreach ($this->nodes as $node) {
            $nodes->add($node);
        }

        return $nodes;
    }

    /**
     * Returns direct outgoing dependencies for one member.
     *
     * @param MemberId $memberId The source member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function outgoing(MemberId $memberId): MemberDependencyCollection
    {
        return $this->outgoingByHash($memberId->hash());
    }

    /**
     * Returns direct outgoing dependencies for one member.
     *
     * @param string $memberHash The source member hash.
     *
     * @return MemberDependencyCollection
     */
    public function outgoingByHash(string $memberHash): MemberDependencyCollection
    {
        return $this->copyDependencies($this->outgoingByMember[$memberHash] ?? new MemberDependencyCollection());
    }

    /**
     * Returns direct incoming dependencies for one member.
     *
     * @param MemberId $memberId The target member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function incoming(MemberId $memberId): MemberDependencyCollection
    {
        return $this->incomingByHash($memberId->hash());
    }

    /**
     * Returns direct incoming dependencies for one member.
     *
     * @param string $memberHash The target member hash.
     *
     * @return MemberDependencyCollection
     */
    public function incomingByHash(string $memberHash): MemberDependencyCollection
    {
        return $this->copyDependencies($this->incomingByMember[$memberHash] ?? new MemberDependencyCollection());
    }

    /**
     * Returns transitive outgoing dependencies reachable from one member.
     *
     * @param MemberId $memberId The source member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function transitiveOutgoing(MemberId $memberId): MemberDependencyCollection
    {
        return $this->transitiveOutgoingByHash($memberId->hash());
    }

    /**
     * Returns transitive outgoing dependencies reachable from one member.
     *
     * @param string $memberHash The source member hash.
     *
     * @return MemberDependencyCollection
     */
    public function transitiveOutgoingByHash(string $memberHash): MemberDependencyCollection
    {
        $dependencies = new MemberDependencyCollection();
        $visitedMembers = [];

        $this->collectTransitiveOutgoing($memberHash, $visitedMembers, $dependencies);

        return $dependencies;
    }

    /**
     * Returns transitive incoming dependencies reaching one member.
     *
     * @param MemberId $memberId The target member identifier.
     *
     * @return MemberDependencyCollection
     */
    public function transitiveIncoming(MemberId $memberId): MemberDependencyCollection
    {
        return $this->transitiveIncomingByHash($memberId->hash());
    }

    /**
     * Returns transitive incoming dependencies reaching one member.
     *
     * @param string $memberHash The target member hash.
     *
     * @return MemberDependencyCollection
     */
    public function transitiveIncomingByHash(string $memberHash): MemberDependencyCollection
    {
        $dependencies = new MemberDependencyCollection();
        $visitedMembers = [];

        $this->collectTransitiveIncoming($memberHash, $visitedMembers, $dependencies);

        return $dependencies;
    }

    /**
     * Recursively collects outgoing dependencies while preventing member cycles.
     *
     * @param string $memberHash The member currently being explored.
     * @param array<string, true> $visitedMembers The already visited members.
     * @param MemberDependencyCollection $dependencies The dependency accumulator.
     *
     * @return void
     */
    private function collectTransitiveOutgoing(
        string $memberHash,
        array &$visitedMembers,
        MemberDependencyCollection $dependencies,
    ): void {
        if (isset($visitedMembers[$memberHash])) {
            return;
        }

        $visitedMembers[$memberHash] = true;

        if (isset($this->outgoingByMember[$memberHash])) {
            foreach ($this->outgoingByMember[$memberHash] as $dependency) {
                $dependencies->add($dependency);
                $this->collectTransitiveOutgoing($dependency->target->hash(), $visitedMembers, $dependencies);
            }
        }
    }

    /**
     * Recursively collects incoming dependencies while preventing member cycles.
     *
     * @param string $memberHash The member currently being explored.
     * @param array<string, true> $visitedMembers The already visited members.
     * @param MemberDependencyCollection $dependencies The dependency accumulator.
     *
     * @return void
     */
    private function collectTransitiveIncoming(
        string $memberHash,
        array &$visitedMembers,
        MemberDependencyCollection $dependencies,
    ): void {
        if (isset($visitedMembers[$memberHash])) {
            return;
        }

        $visitedMembers[$memberHash] = true;

        if (isset($this->incomingByMember[$memberHash])) {
            foreach ($this->incomingByMember[$memberHash] as $dependency) {
                $dependencies->add($dependency);
                $this->collectTransitiveIncoming($dependency->source->hash(), $visitedMembers, $dependencies);
            }
        }
    }

    /**
     * Returns the outgoing collection for one member hash, creating it when needed.
     *
     * @param string $memberHash The member hash.
     *
     * @return MemberDependencyCollection
     */
    private function outgoingCollectionFor(string $memberHash): MemberDependencyCollection
    {
        return $this->outgoingByMember[$memberHash] ??= new MemberDependencyCollection();
    }

    /**
     * Returns the incoming collection for one member hash, creating it when needed.
     *
     * @param string $memberHash The member hash.
     *
     * @return MemberDependencyCollection
     */
    private function incomingCollectionFor(string $memberHash): MemberDependencyCollection
    {
        return $this->incomingByMember[$memberHash] ??= new MemberDependencyCollection();
    }

    /**
     * Copies dependencies into a detached collection.
     *
     * @param MemberDependencyCollection $dependencies The dependencies to copy.
     *
     * @return MemberDependencyCollection
     */
    private function copyDependencies(MemberDependencyCollection $dependencies): MemberDependencyCollection
    {
        $copy = new MemberDependencyCollection();

        foreach ($dependencies as $dependency) {
            $copy->add($dependency);
        }

        return $copy;
    }
}
