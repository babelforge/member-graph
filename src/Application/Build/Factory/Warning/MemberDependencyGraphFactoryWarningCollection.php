<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Factory\Warning;

use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use BabelForge\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteStatus;

/**
 * Stores non-blocking member dependency graph factory warnings.
 *
 * @implements \IteratorAggregate<int, MemberDependencyGraphFactoryWarning>
 */
final class MemberDependencyGraphFactoryWarningCollection implements \Countable, \IteratorAggregate
{
    /** @var list<MemberDependencyGraphFactoryWarning> */
    private array $warnings = [];

    /**
     * Creates a warning collection from a cache write result.
     *
     * @param MemberGraphCacheWriteResult $cacheWriteResult the cache write result
     */
    public static function fromCacheWriteResult(MemberGraphCacheWriteResult $cacheWriteResult): self
    {
        $warnings = new self();

        if (
            MemberGraphCacheWriteStatus::WRITTEN === $cacheWriteResult->status
            || MemberGraphCacheWriteStatus::NOT_WRITTEN === $cacheWriteResult->status
        ) {
            return $warnings;
        }

        $warnings->add(new MemberDependencyGraphFactoryWarning(
            code: MemberDependencyGraphFactoryWarningCode::CACHE_WRITE_FAILED,
            message: 'Member graph cache write failed.',
            cacheFilePath: $cacheWriteResult->cacheFilePath,
            tempFilePath: $cacheWriteResult->tempFilePath,
            cacheWriteStatus: $cacheWriteResult->status,
        ));

        return $warnings;
    }

    /**
     * Adds a warning.
     *
     * @param MemberDependencyGraphFactoryWarning $warning the warning to add
     */
    public function add(MemberDependencyGraphFactoryWarning $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Indicates whether the collection is empty.
     */
    public function isEmpty(): bool
    {
        return [] === $this->warnings;
    }

    /**
     * Returns all warnings.
     *
     * @return list<MemberDependencyGraphFactoryWarning>
     */
    public function all(): array
    {
        return $this->warnings;
    }

    /**
     * Counts warnings.
     */
    public function count(): int
    {
        return count($this->warnings);
    }

    /**
     * Returns an iterator over warnings.
     *
     * @return \Traversable<int, MemberDependencyGraphFactoryWarning>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->warnings);
    }
}
