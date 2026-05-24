<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Fragment;

use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;

/**
 * Stores member dependency graph fragments indexed by physical file path.
 *
 * @implements \IteratorAggregate<string, MemberDependencyGraph>
 */
final class MemberGraphFragmentCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberDependencyGraph>
     */
    private array $items = [];

    /**
     * Adds one graph fragment.
     *
     * @param string                $filePath      the physical file path
     * @param MemberDependencyGraph $graphFragment the graph fragment
     */
    public function add(string $filePath, MemberDependencyGraph $graphFragment): void
    {
        $this->items[$filePath] = $graphFragment;
    }

    /**
     * Returns one graph fragment by physical file path.
     *
     * @param string $filePath the physical file path
     */
    public function get(string $filePath): ?MemberDependencyGraph
    {
        return $this->items[$filePath] ?? null;
    }

    /**
     * Returns all graph fragments.
     *
     * @return array<string, MemberDependencyGraph>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Returns an iterator over graph fragments.
     *
     * @return \Traversable<string, MemberDependencyGraph>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * Counts graph fragments.
     */
    public function count(): int
    {
        return count($this->items);
    }
}
