<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology;

use BabelForge\MemberGraph\Application\Query\MemberDependency;

/**
 * Represents one directed edge in a member graph topology projection.
 */
final readonly class MemberGraphTopologyEdge
{
    /**
     * Constructor.
     *
     * @param string                      $sourceNodeId the source topology node identifier
     * @param string                      $targetNodeId the target topology node identifier
     * @param int                         $depth        the edge depth from the topology root
     * @param MemberGraphTopologyEdgeKind $kind         the edge kind
     * @param MemberDependency|null       $dependency   the underlying member dependency
     * @param string|null                 $file         the source file when the edge is tied to a source file
     */
    public function __construct(
        public string $sourceNodeId,
        public string $targetNodeId,
        public int $depth,
        public MemberGraphTopologyEdgeKind $kind = MemberGraphTopologyEdgeKind::MEMBER_DEPENDENCY,
        public ?MemberDependency $dependency = null,
        public ?string $file = null,
    ) {
    }

    /**
     * Returns a stable topology edge hash.
     */
    public function hash(): string
    {
        if (null !== $this->dependency) {
            return $this->dependency->hash();
        }

        return sprintf(
            '%s:%s->%s:%s',
            $this->kind->name,
            $this->sourceNodeId,
            $this->targetNodeId,
            $this->file ?? '',
        );
    }
}
