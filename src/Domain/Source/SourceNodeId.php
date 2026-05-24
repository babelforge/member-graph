<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Source;

use PhpParser\Node;

/**
 * Identifies one source node by deterministic parser position attributes.
 */
final readonly class SourceNodeId
{
    /**
     * Constructor.
     *
     * @param string $virtualFilePath the virtual file path containing the node
     * @param string $nodeType        the PHPParser node class
     * @param int    $startFilePos    the start file offset
     * @param int    $endFilePos      the end file offset
     * @param int    $startLine       the start line
     * @param int    $endLine         the end line
     */
    public function __construct(
        public string $virtualFilePath,
        public string $nodeType,
        public int $startFilePos,
        public int $endFilePos,
        public int $startLine,
        public int $endLine,
    ) {
    }

    /**
     * Creates a source node identifier from parser attributes.
     *
     * @param string $virtualFilePath the virtual file path containing the node
     * @param Node   $node            the source node
     */
    public static function fromNode(string $virtualFilePath, Node $node): ?self
    {
        $startFilePos = $node->getAttribute('startFilePos');
        $endFilePos = $node->getAttribute('endFilePos');
        $startLine = $node->getAttribute('startLine');
        $endLine = $node->getAttribute('endLine');

        if (
            !is_int($startFilePos)
            || !is_int($endFilePos)
            || !is_int($startLine)
            || !is_int($endLine)
        ) {
            return null;
        }

        return new self(
            virtualFilePath: $virtualFilePath,
            nodeType: $node::class,
            startFilePos: $startFilePos,
            endFilePos: $endFilePos,
            startLine: $startLine,
            endLine: $endLine,
        );
    }

    /**
     * Returns a stable hash for indexing and comparisons.
     */
    public function hash(): string
    {
        return hash('xxh3', implode(':', [
            $this->virtualFilePath,
            $this->nodeType,
            (string) $this->startFilePos,
            (string) $this->endFilePos,
            (string) $this->startLine,
            (string) $this->endLine,
        ]));
    }

    /**
     * Indicates whether this identifier equals another one.
     *
     * @param self $other the identifier to compare
     */
    public function equals(self $other): bool
    {
        return $this->hash() === $other->hash();
    }
}
