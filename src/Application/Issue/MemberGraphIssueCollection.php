<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Issue;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Class PhpDocResolutionIssueCollection
 *
 *
 * @implements IteratorAggregate<int, MemberGraphIssueInterface>
 */
final class MemberGraphIssueCollection implements Countable, IteratorAggregate
{
    /** @var MemberGraphIssueInterface[] */
    private array $items = [];

    public function add(MemberGraphIssueInterface $issue): void
    {
        foreach ($this->items as $item) {
            if ($issue->isSame($item)) {
                return;
            }
        }

        $this->items[] = $issue;
    }

    /**
     * @return MemberGraphIssueInterface[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }
}
