<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Issue;

/**
 * Class PhpDocResolutionIssueCollection.
 *
 * @implements \IteratorAggregate<int, MemberGraphIssueInterface>
 */
final class MemberGraphIssueCollection implements \Countable, \IteratorAggregate
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

    public function isEmpty(): bool
    {
        return [] === $this->items;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
