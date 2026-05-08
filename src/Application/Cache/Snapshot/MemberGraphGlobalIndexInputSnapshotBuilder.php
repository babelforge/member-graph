<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;

/**
 * Builds cacheable global-index input snapshots from loaded virtual files.
 */
final readonly class MemberGraphGlobalIndexInputSnapshotBuilder
{
    /**
     * Builds a global-index input snapshot.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the loaded virtual files
     * @param KnownOwnerCollection           $knownOwners  the known owners discovered during parsing
     */
    public function build(
        VirtualPhpSourceFileCollection $virtualFiles,
        KnownOwnerCollection $knownOwners,
    ): MemberGraphGlobalIndexInputSnapshot {
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        foreach ($virtualFiles as $virtualFile) {
            $sources->add($this->metadataForVirtualFile($virtualFile, $knownOwners));
        }

        return new MemberGraphGlobalIndexInputSnapshot($sources);
    }

    /**
     * Builds metadata for one virtual file.
     *
     * @param VirtualPhpSourceFile $virtualFile the loaded virtual file
     * @param KnownOwnerCollection $knownOwners the known owners discovered during parsing
     */
    private function metadataForVirtualFile(
        VirtualPhpSourceFile $virtualFile,
        KnownOwnerCollection $knownOwners,
    ): MemberGraphVirtualSourceMetadata {
        $nodes = array_values($virtualFile->nodes);
        $namespace = $this->namespaceForNodes($nodes);
        $ownerName = $this->ownerNameForNodes($nodes, $namespace);

        if (null === $ownerName) {
            return new MemberGraphVirtualSourceMetadata(
                fullFilePath: $virtualFile->fullFilePath,
                virtualFilePath: $virtualFile->virtualFilePath,
                namespace: $namespace,
            );
        }

        $knownOwner = $knownOwners->get($ownerName);

        if (null === $knownOwner) {
            return new MemberGraphVirtualSourceMetadata(
                fullFilePath: $virtualFile->fullFilePath,
                virtualFilePath: $virtualFile->virtualFilePath,
                namespace: $namespace,
                ownerName: $ownerName,
            );
        }

        return MemberGraphVirtualSourceMetadata::fromKnownOwner(
            fullFilePath: $virtualFile->fullFilePath,
            virtualFilePath: $virtualFile->virtualFilePath,
            knownOwner: $knownOwner,
            namespace: $namespace,
        );
    }

    /**
     * Resolves the namespace declared by a virtual file.
     *
     * @param list<Node> $nodes the virtual file nodes
     */
    private function namespaceForNodes(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                return $node->name?->toString();
            }
        }

        return null;
    }

    /**
     * Resolves the first class-like owner declared by a virtual file.
     *
     * @param list<Node>  $nodes     the virtual file nodes
     * @param string|null $namespace the virtual file namespace
     */
    private function ownerNameForNodes(array $nodes, ?string $namespace): ?string
    {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                return $this->ownerNameForNodes(array_values($node->stmts), $node->name?->toString());
            }

            if (!$node instanceof ClassLike || null === $node->name) {
                continue;
            }

            $name = $node->name->toString();

            if (null === $namespace || '' === $namespace) {
                return $name;
            }

            return $namespace.'\\'.$name;
        }

        return null;
    }
}
