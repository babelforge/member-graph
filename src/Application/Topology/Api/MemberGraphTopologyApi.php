<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Api;

use PhpNoobs\MemberGraph\Application\Query\MemberGraphQueryService;
use PhpNoobs\MemberGraph\Application\Topology\Export\MemberGraphTopologyExporterInterface;
use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilter;
use PhpNoobs\MemberGraph\Application\Topology\Filter\MemberGraphTopologyFilterService;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopology;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyDirection;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyService;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Provides a compact facade for topology creation, filtering, and exporting.
 */
final readonly class MemberGraphTopologyApi
{
    /**
     * Constructor.
     *
     * @param MemberGraphTopologyService $topologyService The topology service.
     * @param MemberGraphTopologyFilterService $filterService The filter service.
     */
    public function __construct(
        private MemberGraphTopologyService $topologyService,
        private MemberGraphTopologyFilterService $filterService = new MemberGraphTopologyFilterService(),
    ) {
    }

    /**
     * Creates a topology API from a member dependency graph.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     *
     * @return self
     */
    public static function fromGraph(MemberDependencyGraph $graph): self
    {
        return new self(MemberGraphTopologyService::fromGraph($graph));
    }

    /**
     * Creates a topology API from an existing graph query service.
     *
     * @param MemberGraphQueryService $query The graph query service.
     *
     * @return self
     */
    public static function fromQuery(MemberGraphQueryService $query): self
    {
        return new self(MemberGraphTopologyService::fromQuery($query));
    }

    /**
     * Builds an optionally filtered member topology.
     *
     * @param MemberId $memberId The root member.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum traversal depth.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return MemberGraphTopology
     */
    public function member(
        MemberId $memberId,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
        ?MemberGraphTopologyFilter $filter = null,
    ): MemberGraphTopology {
        return $this->applyFilter(
            $this->topologyService->member($memberId, $direction, $maxDepth),
            $filter,
        );
    }

    /**
     * Builds an optionally filtered owner topology.
     *
     * @param string $owner The owner FQCN.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum traversal depth.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return MemberGraphTopology
     */
    public function owner(
        string $owner,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
        ?MemberGraphTopologyFilter $filter = null,
    ): MemberGraphTopology {
        return $this->applyFilter(
            $this->topologyService->owner($owner, $direction, $maxDepth),
            $filter,
        );
    }

    /**
     * Builds an optionally filtered codebase topology.
     *
     * @param MemberGraphTopologyDirection $direction The dependency direction to include.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return MemberGraphTopology
     */
    public function codebase(
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        ?MemberGraphTopologyFilter $filter = null,
    ): MemberGraphTopology {
        return $this->applyFilter(
            $this->topologyService->codebase($direction),
            $filter,
        );
    }

    /**
     * Exports an existing topology through the given exporter.
     *
     * @template TExport
     *
     * @param MemberGraphTopology $topology The topology to export.
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter The exporter to use.
     *
     * @return TExport
     */
    public function export(
        MemberGraphTopology $topology,
        MemberGraphTopologyExporterInterface $exporter,
    ): mixed {
        return $exporter->export($topology);
    }

    /**
     * Builds and exports a member topology through the given exporter.
     *
     * @template TExport
     *
     * @param MemberId $memberId The root member.
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter The exporter to use.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum traversal depth.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return TExport
     */
    public function exportMember(
        MemberId $memberId,
        MemberGraphTopologyExporterInterface $exporter,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
        ?MemberGraphTopologyFilter $filter = null,
    ): mixed {
        return $this->export(
            $this->member($memberId, $direction, $maxDepth, $filter),
            $exporter,
        );
    }

    /**
     * Builds and exports an owner topology through the given exporter.
     *
     * @template TExport
     *
     * @param string $owner The owner FQCN.
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter The exporter to use.
     * @param MemberGraphTopologyDirection $direction The traversal direction.
     * @param int $maxDepth The maximum traversal depth.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return TExport
     */
    public function exportOwner(
        string $owner,
        MemberGraphTopologyExporterInterface $exporter,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        int $maxDepth = 3,
        ?MemberGraphTopologyFilter $filter = null,
    ): mixed {
        return $this->export(
            $this->owner($owner, $direction, $maxDepth, $filter),
            $exporter,
        );
    }

    /**
     * Builds and exports a codebase topology through the given exporter.
     *
     * @template TExport
     *
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter The exporter to use.
     * @param MemberGraphTopologyDirection $direction The dependency direction to include.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return TExport
     */
    public function exportCodebase(
        MemberGraphTopologyExporterInterface $exporter,
        MemberGraphTopologyDirection $direction = MemberGraphTopologyDirection::BOTH,
        ?MemberGraphTopologyFilter $filter = null,
    ): mixed {
        return $this->export(
            $this->codebase($direction, $filter),
            $exporter,
        );
    }

    /**
     * Applies an optional topology filter.
     *
     * @param MemberGraphTopology $topology The topology to filter.
     * @param MemberGraphTopologyFilter|null $filter The optional filter.
     *
     * @return MemberGraphTopology
     */
    private function applyFilter(
        MemberGraphTopology $topology,
        ?MemberGraphTopologyFilter $filter,
    ): MemberGraphTopology {
        if (null === $filter) {
            return $topology;
        }

        return $this->filterService->filter($topology, $filter);
    }
}
