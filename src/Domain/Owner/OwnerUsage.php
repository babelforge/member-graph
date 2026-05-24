<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Owner;

use BabelForge\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Represents a usage of a class-like owner.
 */
final readonly class OwnerUsage
{
    /**
     * Constructor.
     *
     * @param string            $sourceSymbol the symbol where the usage appears
     * @param string            $target       the targeted owner FQCN
     * @param OwnerUsageType    $type         the owner usage type
     * @param string            $file         the virtual file path containing the usage
     * @param SourceNodeId|null $sourceNodeId the source node identifier when available
     */
    public function __construct(
        public string $sourceSymbol,
        public string $target,
        public OwnerUsageType $type,
        public string $file,
        public ?SourceNodeId $sourceNodeId = null,
    ) {
    }
}
