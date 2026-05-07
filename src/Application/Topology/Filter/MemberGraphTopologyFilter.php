<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology\Filter;

use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyEdgeKind;
use PhpNoobs\MemberGraph\Application\Topology\MemberGraphTopologyNodeKind;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;

/**
 * Describes optional filters to apply to a topology projection.
 */
final readonly class MemberGraphTopologyFilter
{
    /**
     * Constructor.
     *
     * @param list<MemberGraphTopologyNodeKind>|null $nodeKinds The allowed node kinds, or null for all.
     * @param list<MemberGraphTopologyEdgeKind>|null $edgeKinds The allowed edge kinds, or null for all.
     * @param list<string>|null $ownerPrefixes The allowed owner prefixes, or null for all.
     * @param list<string>|null $excludedOwnerPrefixes The excluded owner prefixes, or null for none.
     * @param list<MemberType>|null $memberTypes The allowed member types, or null for all.
     * @param list<string>|null $files The allowed file prefixes or exact paths, or null for all.
     * @param list<string>|null $excludedFiles The excluded file prefixes or exact paths, or null for none.
     */
    public function __construct(
        public ?array $nodeKinds = null,
        public ?array $edgeKinds = null,
        public ?array $ownerPrefixes = null,
        public ?array $excludedOwnerPrefixes = null,
        public ?array $memberTypes = null,
        public ?array $files = null,
        public ?array $excludedFiles = null,
    ) {
    }
}
