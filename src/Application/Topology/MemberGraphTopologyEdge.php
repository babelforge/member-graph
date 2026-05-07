<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

use PhpNoobs\MemberGraph\Application\Query\MemberDependency;

/**
 * Represents one directed edge in a member graph topology projection.
 */
final readonly class MemberGraphTopologyEdge
{
    /**
     * Constructor.
     *
     * @param string $sourceNodeId The source topology node identifier.
     * @param string $targetNodeId The target topology node identifier.
     * @param int $depth The edge depth from the topology root.
     * @param MemberGraphTopologyEdgeKind $kind The edge kind.
     * @param MemberDependency|null $dependency The underlying member dependency.
     * @param string|null $file The source file when the edge is tied to a source file.
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
     *
     * @return string
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
