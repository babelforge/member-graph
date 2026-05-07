<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Impact;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores impacted file paths without duplicates.
 *
 * @implements IteratorAggregate<int, string>
 */
final class ImpactedFileCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, true>
     */
    private array $items = [];

    /**
     * Adds one impacted file path.
     *
     * @param string $file The impacted file path.
     *
     * @return void
     */
    public function add(string $file): void
    {
        if ('' === $file) {
            return;
        }

        $this->items[$file] = true;
    }

    /**
     * Returns all impacted file paths.
     *
     * @return list<string>
     */
    public function all(): array
    {
        return array_keys($this->items);
    }

    /**
     * Indicates whether the collection contains the given file path.
     *
     * @param string $file The file path to test.
     *
     * @return bool
     */
    public function contains(string $file): bool
    {
        return isset($this->items[$file]);
    }

    /**
     * Returns an iterator over impacted file paths.
     *
     * @return Traversable<int, string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->all();
    }

    /**
     * Counts impacted file paths.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }
}
