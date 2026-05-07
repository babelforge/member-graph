<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Declaration;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Represents a declared member.
 */
final readonly class MemberDeclaration
{
    /**
     * Constructor.
     *
     * @param MemberId $id The declared member identifier.
     * @param string $file The virtual file path containing the declaration.
     * @param SourceNodeId|null $sourceNodeId The source node identifier when available.
     */
    public function __construct(
        public MemberId $id,
        public string $file,
        public ?SourceNodeId $sourceNodeId = null,
    ) {
    }
}
