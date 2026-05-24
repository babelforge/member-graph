<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Owner;

use BabelForge\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Represents a declared class-like owner.
 */
final readonly class OwnerDeclaration
{
    /**
     * Constructor.
     *
     * @param string            $fqcn         the declared owner FQCN
     * @param OwnerKind         $kind         the declared owner kind
     * @param string            $file         the virtual file path containing the declaration
     * @param SourceNodeId|null $sourceNodeId the source node identifier when available
     */
    public function __construct(
        public string $fqcn,
        public OwnerKind $kind,
        public string $file,
        public ?SourceNodeId $sourceNodeId = null,
    ) {
    }
}
