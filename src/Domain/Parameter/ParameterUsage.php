<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Parameter;

use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Represents one parameter usage.
 */
final readonly class ParameterUsage
{
    /**
     * @param string             $sourceSymbol the symbol where the usage appears
     * @param ParameterId        $target       the targeted parameter identifier
     * @param ParameterUsageType $type         the usage type
     * @param string             $file         the file path containing the usage
     * @param SourceNodeId|null  $sourceNodeId the source node identifier when available
     */
    public function __construct(
        public string $sourceSymbol,
        public ParameterId $target,
        public ParameterUsageType $type,
        public string $file,
        public ?SourceNodeId $sourceNodeId = null,
    ) {
    }
}
