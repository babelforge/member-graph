<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeVisitorAbstract;

/**
 * Builds structural node indexes from one AST.
 */
final class StructuralNodeIndexBuilderVisitor extends NodeVisitorAbstract
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
     * @param MethodNodeIndex    $methodNodeIndex    the method node index
     * @param FunctionNodeIndex  $functionNodeIndex  the function node index
     * @param ClassLikeNodeIndex $classLikeNodeIndex the class-like node index
     */
    public function __construct(
        private readonly MethodNodeIndex $methodNodeIndex,
        private readonly FunctionNodeIndex $functionNodeIndex,
        private readonly ClassLikeNodeIndex $classLikeNodeIndex,
    ) {
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->currentNamespace = '';
        $this->currentOwner = '';
        $this->classLikeStack = [];

        return null;
    }

    public function enterNode(Node $node): int|Node|array|null
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace = $node->name instanceof Name
                ? $node->name->toString()
                : '';

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->registerClassLike($node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->registerMethod($node);

            return null;
        }

        if ($node instanceof Function_) {
            $this->registerFunction($node);

            return null;
        }

        return null;
    }

    public function leaveNode(Node $node): int|Node|array|null
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classLikeStack);

            $currentClassLike = end($this->classLikeStack);

            $this->currentOwner = $currentClassLike instanceof ClassLike
                ? $this->resolveClassLikeFqcn($currentClassLike)
                : '';

            return null;
        }

        if ($node instanceof Namespace_) {
            $this->currentNamespace = '';

            return null;
        }

        return null;
    }

    /**
     * Registers one class-like node.
     *
     * @param ClassLike $classLike the class-like node
     */
    private function registerClassLike(ClassLike $classLike): void
    {
        $owner = $this->resolveClassLikeFqcn($classLike);

        if ('' === $owner) {
            return;
        }

        $this->classLikeNodeIndex->set($owner, $classLike);

        $this->classLikeStack[] = $classLike;
        $this->currentOwner = $owner;
    }

    /**
     * Registers one method node.
     *
     * @param ClassMethod $method the method node
     */
    private function registerMethod(ClassMethod $method): void
    {
        if ('' === $this->currentOwner) {
            return;
        }

        $this->methodNodeIndex->set(
            owner: $this->currentOwner,
            methodName: $method->name->toString(),
            methodNode: $method,
        );
    }

    /**
     * Registers one function node.
     *
     * @param Function_ $function the function node
     */
    private function registerFunction(Function_ $function): void
    {
        $functionName = $function->name->toString();
        $functionFqcn = '' !== $this->currentNamespace
            ? $this->currentNamespace.'\\'.$functionName
            : $functionName;

        $this->functionNodeIndex->set($functionFqcn, $function);
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

        return '' !== $this->currentNamespace
            ? $this->currentNamespace.'\\'.$name
            : $name;
    }
}
