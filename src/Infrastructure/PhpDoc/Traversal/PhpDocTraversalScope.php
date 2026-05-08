<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Traversal;

use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;

/**
 * Tracks namespace, owner, and use-import scope while traversing PHPDoc-aware AST nodes.
 */
final class PhpDocTraversalScope
{
    /**
     * Current namespace.
     */
    private string $currentNamespace = '';

    /**
     * Current owner FQCN.
     */
    private string $currentOwner = '';

    /**
     * Current class-like stack.
     *
     * @var list<ClassLike>
     */
    private array $classLikeStack = [];

    /**
     * Current use-import stack.
     *
     * One UsesByAliasCollection is maintained per namespace/class-like scope level.
     *
     * @var list<UsesByAliasCollection>
     */
    private array $usesStack = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Resets the tracked traversal scope.
     */
    public function reset(): void
    {
        $this->currentNamespace = '';
        $this->currentOwner = '';
        $this->classLikeStack = [];
        $this->usesStack = [new UsesByAliasCollection()];
    }

    /**
     * Enters one namespace scope.
     *
     * @param Namespace_ $namespaceNode the namespace node
     */
    public function enterNamespace(Namespace_ $namespaceNode): void
    {
        $this->currentNamespace = $namespaceNode->name instanceof Name
            ? $namespaceNode->name->toString()
            : '';

        $this->usesStack = [new UsesByAliasCollection()];
    }

    /**
     * Leaves the current namespace scope.
     */
    public function leaveNamespace(): void
    {
        $this->currentNamespace = '';
        $this->usesStack = [new UsesByAliasCollection()];
    }

    /**
     * Enters one class-like scope.
     *
     * @param ClassLike $classLike the class-like node
     */
    public function enterClassLike(ClassLike $classLike): void
    {
        $this->classLikeStack[] = $classLike;
        $this->currentOwner = $this->resolveClassLikeFqcn($classLike);

        $this->usesStack[] = $this->cloneUsesByAliasCollection($this->currentUsesByAlias());
    }

    /**
     * Leaves one class-like scope.
     */
    public function leaveClassLike(): void
    {
        array_pop($this->classLikeStack);
        array_pop($this->usesStack);

        $currentClassLike = end($this->classLikeStack);
        $this->currentOwner = $currentClassLike instanceof ClassLike
            ? $this->resolveClassLikeFqcn($currentClassLike)
            : '';
    }

    /**
     * Registers one regular use statement in the current scope.
     *
     * @param Use_ $useNode the use statement
     */
    public function registerUseStatement(Use_ $useNode): void
    {
        $currentUses = $this->currentUsesByAlias();

        foreach ($useNode->uses as $useUse) {
            $fqcn = $useUse->name->toString();
            $alias = null !== $useUse->alias
                ? $useUse->alias->toString()
                : $useUse->name->getLast();

            $currentUses->set($alias, $fqcn);
        }
    }

    /**
     * Registers one group use statement in the current scope.
     *
     * @param GroupUse $groupUseNode the group use statement
     */
    public function registerGroupUseStatement(GroupUse $groupUseNode): void
    {
        $currentUses = $this->currentUsesByAlias();
        $prefix = $groupUseNode->prefix->toString();

        foreach ($groupUseNode->uses as $useUse) {
            $suffix = $useUse->name->toString();
            $fqcn = $prefix.'\\'.$suffix;
            $alias = null !== $useUse->alias
                ? $useUse->alias->toString()
                : $useUse->name->getLast();

            $currentUses->set($alias, $fqcn);
        }
    }

    /**
     * Checks whether the scope currently points to a named owner.
     */
    public function hasCurrentOwner(): bool
    {
        return '' !== $this->currentOwner;
    }

    /**
     * Returns the current namespace.
     */
    public function currentNamespace(): string
    {
        return $this->currentNamespace;
    }

    /**
     * Returns the current owner FQCN.
     */
    public function currentOwner(): string
    {
        return $this->currentOwner;
    }

    /**
     * Creates a type-index context for the current traversal scope.
     *
     * @param string $fullFilePath    the original full file path
     * @param string $virtualFilePath the virtual file path
     * @param string $member          the current member name
     */
    public function createContext(string $fullFilePath, string $virtualFilePath, string $member): TypeIndexContext
    {
        $context = new TypeIndexContext();
        $context->fullFilePath = $fullFilePath;
        $context->virtualFilePath = $virtualFilePath;
        $context->namespace = $this->currentNamespace;
        $context->owner = $this->currentOwner;
        $context->member = $member;
        $context->usesByAlias = $this->cloneUsesByAliasCollection($this->currentUsesByAlias());

        return $context;
    }

    /**
     * Returns the current use-import collection.
     */
    private function currentUsesByAlias(): UsesByAliasCollection
    {
        $current = end($this->usesStack);

        if ($current instanceof UsesByAliasCollection) {
            return $current;
        }

        $fallback = new UsesByAliasCollection();
        $this->usesStack[] = $fallback;

        return $fallback;
    }

    /**
     * Clones one use-import collection.
     *
     * @param UsesByAliasCollection $source the source collection
     */
    private function cloneUsesByAliasCollection(UsesByAliasCollection $source): UsesByAliasCollection
    {
        $clone = new UsesByAliasCollection();

        foreach ($source as $alias => $fqcn) {
            $clone->set($alias, $fqcn);
        }

        return $clone;
    }

    /**
     * Resolves the FQCN of one class-like node.
     *
     * @param ClassLike $classLike the class-like node
     */
    private function resolveClassLikeFqcn(ClassLike $classLike): string
    {
        $namespacedName = $classLike->getAttribute('namespacedName');

        if ($namespacedName instanceof Name) {
            return $namespacedName->toString();
        }

        $name = $classLike->name?->toString() ?? '';

        if ('' === $name) {
            return '';
        }

        if ('' === $this->currentNamespace) {
            return $name;
        }

        return $this->currentNamespace.'\\'.$name;
    }
}
