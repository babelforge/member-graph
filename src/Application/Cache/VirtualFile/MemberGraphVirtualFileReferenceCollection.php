<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\VirtualFile;

use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Stores virtual file references indexed by virtual file path and physical file path.
 *
 * @implements \IteratorAggregate<string, MemberGraphVirtualFileReference>
 */
final class MemberGraphVirtualFileReferenceCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, MemberGraphVirtualFileReference>
     */
    private array $byVirtualFilePath = [];

    /**
     * @var array<string, MemberGraphVirtualFileReference[]>
     */
    private array $byFullFilePath = [];

    /**
     * Creates references from loaded virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the loaded virtual files
     */
    public static function fromVirtualFiles(VirtualPhpSourceFileCollection $virtualFiles): self
    {
        $references = new self();

        foreach ($virtualFiles as $virtualFile) {
            $references->add(new MemberGraphVirtualFileReference(
                MemberGraphVirtualFileMetadata::fromVirtualFile($virtualFile),
            ));
        }

        return $references;
    }

    /**
     * Adds one virtual file reference.
     *
     * @param MemberGraphVirtualFileReference $reference the reference to add
     */
    public function add(MemberGraphVirtualFileReference $reference): void
    {
        $this->byVirtualFilePath[$reference->virtualFilePath()] = $reference;
        $this->byFullFilePath[$reference->fullFilePath()][$reference->virtualFilePath()] = $reference;
    }

    /**
     * Returns one reference by virtual file path.
     *
     * @param string $virtualFilePath the virtual file path
     */
    public function getByVirtualFilePath(string $virtualFilePath): ?MemberGraphVirtualFileReference
    {
        return $this->byVirtualFilePath[$virtualFilePath] ?? null;
    }

    /**
     * Returns references for one physical file path.
     *
     * @param string $fullFilePath the physical file path
     *
     * @return list<MemberGraphVirtualFileReference>
     */
    public function getByFullFilePath(string $fullFilePath): array
    {
        return array_values($this->byFullFilePath[$fullFilePath] ?? []);
    }

    /**
     * Returns all references.
     *
     * @return array<string, MemberGraphVirtualFileReference>
     */
    public function all(): array
    {
        return $this->byVirtualFilePath;
    }

    /**
     * Returns an iterator over references indexed by virtual file path.
     *
     * @return \Traversable<string, MemberGraphVirtualFileReference>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->byVirtualFilePath;
    }

    /**
     * Counts virtual file references.
     */
    public function count(): int
    {
        return count($this->byVirtualFilePath);
    }
}
