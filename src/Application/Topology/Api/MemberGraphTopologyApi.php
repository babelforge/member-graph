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
     * @param MemberGraphTopologyService       $topologyService the topology service
     * @param MemberGraphTopologyFilterService $filterService   the filter service
     */
    public function __construct(
        private MemberGraphTopologyService $topologyService,
        private MemberGraphTopologyFilterService $filterService = new MemberGraphTopologyFilterService(),
    ) {
    }

    /**
     * Creates a topology API from a member dependency graph.
     *
     * @param MemberDependencyGraph $graph the member dependency graph
     */
    public static function fromGraph(MemberDependencyGraph $graph): self
    {
        return new self(MemberGraphTopologyService::fromGraph($graph));
    }

    /**
     * Creates a topology API from an existing graph query service.
     *
     * @param MemberGraphQueryService $query the graph query service
     */
    public static function fromQuery(MemberGraphQueryService $query): self
    {
        return new self(MemberGraphTopologyService::fromQuery($query));
    }

    /**
     * Builds an optionally filtered member topology.
     *
     * @param MemberId                       $memberId  the root member
     * @param MemberGraphTopologyDirection   $direction the traversal direction
     * @param int                            $maxDepth  the maximum traversal depth
     * @param MemberGraphTopologyFilter|null $filter    the optional filter
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
     * @param string                         $owner     the owner FQCN
     * @param MemberGraphTopologyDirection   $direction the traversal direction
     * @param int                            $maxDepth  the maximum traversal depth
     * @param MemberGraphTopologyFilter|null $filter    the optional filter
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
     * @param MemberGraphTopologyDirection   $direction the dependency direction to include
     * @param MemberGraphTopologyFilter|null $filter    the optional filter
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
     * @param MemberGraphTopology                           $topology the topology to export
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter the exporter to use
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
     * @param MemberId                                      $memberId  the root member
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter  the exporter to use
     * @param MemberGraphTopologyDirection                  $direction the traversal direction
     * @param int                                           $maxDepth  the maximum traversal depth
     * @param MemberGraphTopologyFilter|null                $filter    the optional filter
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
     * @param string                                        $owner     the owner FQCN
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter  the exporter to use
     * @param MemberGraphTopologyDirection                  $direction the traversal direction
     * @param int                                           $maxDepth  the maximum traversal depth
     * @param MemberGraphTopologyFilter|null                $filter    the optional filter
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
     * @param MemberGraphTopologyExporterInterface<TExport> $exporter  the exporter to use
     * @param MemberGraphTopologyDirection                  $direction the dependency direction to include
     * @param MemberGraphTopologyFilter|null                $filter    the optional filter
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
     * @param MemberGraphTopology            $topology the topology to filter
     * @param MemberGraphTopologyFilter|null $filter   the optional filter
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
