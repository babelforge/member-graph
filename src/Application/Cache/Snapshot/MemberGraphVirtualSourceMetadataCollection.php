<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Stores virtual source metadata indexed by virtual file path.
 *
 * @implements IteratorAggregate<string, MemberGraphVirtualSourceMetadata>
 */
final class MemberGraphVirtualSourceMetadataCollection implements Countable, IteratorAggregate
{
    /**
     * @var array<string, MemberGraphVirtualSourceMetadata>
     */
    private array $byVirtualFilePath = [];

    /**
     * Adds one virtual source metadata entry.
     *
     * @param MemberGraphVirtualSourceMetadata $metadata The metadata to add.
     *
     * @return void
     */
    public function add(MemberGraphVirtualSourceMetadata $metadata): void
    {
        $this->byVirtualFilePath[$metadata->virtualFilePath] = $metadata;
    }

    /**
     * Returns one metadata entry by virtual file path.
     *
     * @param string $virtualFilePath The virtual file path.
     *
     * @return MemberGraphVirtualSourceMetadata|null
     */
    public function get(string $virtualFilePath): ?MemberGraphVirtualSourceMetadata
    {
        return $this->byVirtualFilePath[$virtualFilePath] ?? null;
    }

    /**
     * Returns all metadata entries.
     *
     * @return array<string, MemberGraphVirtualSourceMetadata>
     */
    public function all(): array
    {
        return $this->byVirtualFilePath;
    }

    /**
     * Returns an iterator over metadata entries indexed by virtual file path.
     *
     * @return Traversable<string, MemberGraphVirtualSourceMetadata>
     */
    public function getIterator(): Traversable
    {
        yield from $this->byVirtualFilePath;
    }

    /**
     * Counts metadata entries.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->byVirtualFilePath);
    }
}
