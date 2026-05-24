<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Traversal;

use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

/**
 * Enriches class methods with effective PHPDoc before index building.
 */
final class EffectivePhpDocEnrichmentVisitor extends NodeVisitorAbstract
{
    /**
     * Current PHPDoc-aware traversal scope.
     */
    private PhpDocTraversalScope $scope;

    /**
     * @param EffectivePhpDocEnricher $effectivePhpDocEnricher the effective PHPDoc enricher
     * @param KnownOwnerCollection    $knownOwners             the known owners collection
     * @param string                  $fullFilePath            the original full file path
     * @param string                  $virtualFilePath         the virtual file path
     */
    public function __construct(
        private readonly EffectivePhpDocEnricher $effectivePhpDocEnricher,
        private readonly KnownOwnerCollection $knownOwners,
        private readonly string $fullFilePath,
        private readonly string $virtualFilePath,
    ) {
        $this->scope = new PhpDocTraversalScope();
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->scope->reset();

        return null;
    }

    public function enterNode(Node $node): int|Node|array|null
    {
        if ($node instanceof Namespace_) {
            $this->scope->enterNamespace($node);

            return null;
        }

        if ($node instanceof Use_) {
            $this->scope->registerUseStatement($node);

            return null;
        }

        if ($node instanceof GroupUse) {
            $this->scope->registerGroupUseStatement($node);

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->scope->enterClassLike($node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->enrichClassMethod($node);

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): int|Node|array|null
    {
        if ($node instanceof ClassLike) {
            $this->scope->leaveClassLike();

            return null;
        }

        if ($node instanceof Namespace_) {
            $this->scope->leaveNamespace();

            return null;
        }

        return null;
    }

    /**
     * Enriches one class method with an effective PHPDoc.
     *
     * @param ClassMethod $method the method node
     */
    private function enrichClassMethod(ClassMethod $method): void
    {
        if (false === $this->scope->hasCurrentOwner()) {
            return;
        }

        $this->effectivePhpDocEnricher->enrichMethod(
            method: $method,
            context: $this->scope->createContext(
                fullFilePath: $this->fullFilePath,
                virtualFilePath: $this->virtualFilePath,
                member: $method->name->toString(),
            ),
            knownOwners: $this->knownOwners,
        );
    }
}
