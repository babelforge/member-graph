<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Source\Node;

use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node;

/**
 * Collects source node matches found in virtual registry files.
 *
 * @implements \IteratorAggregate<VirtualPhpSourceFileNodeMatch>
 */
final class VirtualPhpSourceFileNodeMatchCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var list<VirtualPhpSourceFileNodeMatch>
     */
    private array $matches = [];

    /**
     * Adds one source node match.
     *
     * @param VirtualPhpSourceFileNodeMatch $match the match to add
     */
    public function add(VirtualPhpSourceFileNodeMatch $match): self
    {
        $this->matches[] = $match;

        return $this;
    }

    /**
     * Returns all matches.
     *
     * @return list<VirtualPhpSourceFileNodeMatch>
     */
    public function all(): array
    {
        return $this->matches;
    }

    /**
     * Returns matches using the given role.
     *
     * @param VirtualPhpSourceFileNodeMatchRole $role the role to keep
     */
    public function byRole(VirtualPhpSourceFileNodeMatchRole $role): self
    {
        $matches = new self();

        foreach ($this->matches as $match) {
            if ($match->role === $role) {
                $matches->add($match);
            }
        }

        return $matches;
    }

    /**
     * Returns matches declaring graph members.
     */
    public function memberDeclarations(): self
    {
        return $this->byRole(VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION);
    }

    /**
     * Returns matches using graph members.
     */
    public function memberUsages(): self
    {
        return $this->byRole(VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE);
    }

    /**
     * Returns matches declaring function-like parameters.
     */
    public function parameterDeclarations(): self
    {
        return $this->byRole(VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION);
    }

    /**
     * Returns matches using function-like parameters.
     */
    public function parameterUsages(): self
    {
        return $this->byRole(VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE);
    }

    /**
     * Returns matches contained in one virtual file.
     *
     * @param string $virtualFilePath the virtual file path to keep
     */
    public function byVirtualFilePath(string $virtualFilePath): self
    {
        $matches = new self();

        foreach ($this->matches as $match) {
            if ($match->virtualFile->virtualFilePath === $virtualFilePath) {
                $matches->add($match);
            }
        }

        return $matches;
    }

    /**
     * Returns matches whose node is an instance of the given PHPParser node class.
     *
     * @param class-string<Node> $nodeClass the PHPParser node class to keep
     */
    public function byNodeClass(string $nodeClass): self
    {
        $matches = new self();

        foreach ($this->matches as $match) {
            if ($match->node instanceof $nodeClass) {
                $matches->add($match);
            }
        }

        return $matches;
    }

    /**
     * Returns unique virtual files containing at least one match.
     */
    public function virtualFiles(): VirtualPhpSourceFileCollection
    {
        $virtualFiles = new VirtualPhpSourceFileCollection();
        $indexedVirtualFilePaths = [];

        foreach ($this->matches as $match) {
            if (true === ($indexedVirtualFilePaths[$match->virtualFile->virtualFilePath] ?? false)) {
                continue;
            }

            $indexedVirtualFilePaths[$match->virtualFile->virtualFilePath] = true;
            $virtualFiles->add($match->virtualFile);
        }

        return $virtualFiles;
    }

    /**
     * Returns matched PHPParser nodes.
     *
     * @return list<Node>
     */
    public function nodes(): array
    {
        $nodes = [];

        foreach ($this->matches as $match) {
            $nodes[] = $match->node;
        }

        return $nodes;
    }

    /**
     * Indicates whether the collection contains no match.
     */
    public function isEmpty(): bool
    {
        return 0 === count($this->matches);
    }

    /**
     * Returns an iterator over source node matches.
     *
     * @return \Traversable<VirtualPhpSourceFileNodeMatch>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->matches;
    }

    /**
     * Returns the match count.
     */
    public function count(): int
    {
        return count($this->matches);
    }
}
