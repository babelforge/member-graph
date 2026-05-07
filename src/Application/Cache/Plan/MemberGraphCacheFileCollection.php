<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Plan;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores physical file paths without duplicates.
 *
 * @implements IteratorAggregate<int, string>
 */
final class MemberGraphCacheFileCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, true>
     */
    private array $items = [];

    /**
     * Adds one file path.
     *
     * @param string $filePath The physical file path.
     *
     * @return void
     */
    public function add(string $filePath): void
    {
        if ('' === $filePath) {
            return;
        }

        $this->items[$filePath] = true;
    }

    /**
     * Indicates whether the collection contains one file path.
     *
     * @param string $filePath The physical file path.
     *
     * @return bool
     */
    public function contains(string $filePath): bool
    {
        return isset($this->items[$filePath]);
    }

    /**
     * Returns all file paths.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->items);
    }

    /**
     * Returns an iterator over file paths.
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts file paths.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
