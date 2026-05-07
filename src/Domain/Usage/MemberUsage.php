<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Usage;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Represents a usage of a member.
 */
final readonly class MemberUsage
{
    /**
     * Constructor.
     *
     * @param string $sourceSymbol The symbol where the usage appears.
     * @param MemberId $target The targeted member.
     * @param MemberUsageType $type The usage type.
     * @param string $file The virtual file path containing the usage.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     */
    public function __construct(
        public string $sourceSymbol,
        public MemberId $target,
        public MemberUsageType $type,
        public string $file,
        public ?SourceNodeId $sourceNodeId = null,
    ) {
    }
}
