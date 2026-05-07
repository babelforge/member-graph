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
     * @param string $sourceSymbol The symbol where the usage appears.
     * @param ParameterId $target The targeted parameter identifier.
     * @param ParameterUsageType $type The usage type.
     * @param string $file The file path containing the usage.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
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
