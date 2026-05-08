<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Traverse\MemberGraphTraversalState;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\StructuredPhpDocTypeSelector;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Comment\Doc;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Collects inferred structured return types for methods and functions.
 */
final readonly class InferredStructuredReturnCollector
{
    /**
     * Constructor.
     *
     * @param ExpressionTypeResolverInterface           $expressionTypeResolver                    the expression type resolver
     * @param MethodReturnStructuredTypeIndex           $methodReturnStructuredTypeIndex           the declared method structured return index
     * @param MethodReturnInferredStructuredTypeIndex   $methodReturnInferredStructuredTypeIndex   the inferred method structured return index
     * @param FunctionReturnStructuredTypeIndex         $functionReturnStructuredTypeIndex         the declared function structured return index
     * @param FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex the inferred function structured return index
     * @param UsesByAliasCollection                     $usesByAlias                               the current use imports indexed by alias
     * @param StructuredPhpDocTypeSelector              $structuredPhpDocTypeSelector              the structured PHPDoc type selector
     */
    public function __construct(
        private ExpressionTypeResolverInterface $expressionTypeResolver,
        private MethodReturnStructuredTypeIndex $methodReturnStructuredTypeIndex,
        private MethodReturnInferredStructuredTypeIndex $methodReturnInferredStructuredTypeIndex,
        private FunctionReturnStructuredTypeIndex $functionReturnStructuredTypeIndex,
        private FunctionReturnInferredStructuredTypeIndex $functionReturnInferredStructuredTypeIndex,
        private UsesByAliasCollection $usesByAlias,
        private StructuredPhpDocTypeSelector $structuredPhpDocTypeSelector,
    ) {
    }

    /**
     * Collects one inferred structured return type from one return statement.
     *
     * @param Return_                   $return the return statement node
     * @param MemberGraphTraversalState $state  the current traversal state
     */
    public function collect(Return_ $return, MemberGraphTraversalState $state): void
    {
        if ('' !== $state->currentClass() && '' !== $state->currentMethod()) {
            $this->collectMethodReturn($return, $state);

            return;
        }

        if ('' !== $state->currentFunction()) {
            $this->collectFunctionReturn($return, $state);
        }
    }

    /**
     * Collects one inferred structured method return type.
     *
     * @param Return_                   $return the return statement node
     * @param MemberGraphTraversalState $state  the current traversal state
     */
    private function collectMethodReturn(Return_ $return, MemberGraphTraversalState $state): void
    {
        $currentMethodNode = $state->currentMethodNode();

        if (!$currentMethodNode instanceof ClassMethod) {
            return;
        }

        if ($this->hasExplicitReturnPhpDoc($currentMethodNode)) {
            return;
        }

        if ('' === $state->currentClass() || '' === $state->currentMethod()) {
            return;
        }

        if (!$return->expr instanceof Expr) {
            return;
        }

        $declaredStructuredReturnType = $this->methodReturnStructuredTypeIndex->get(
            $state->currentClass(),
            $state->currentMethod(),
        );

        $structuredType = $this->expressionTypeResolver->resolveStructuredPhpDocType(
            $return->expr,
            $state->variableTypes(),
            $state->currentClass(),
            $state->currentTemplateDefinitions(),
            $this->usesByAlias,
        );

        if (!$structuredType instanceof ResolvedPhpDocType) {
            return;
        }

        $chosenType = $this->structuredPhpDocTypeSelector->choose(
            $declaredStructuredReturnType,
            $structuredType,
        );

        if (!$chosenType instanceof ResolvedPhpDocType) {
            return;
        }

        $this->methodReturnInferredStructuredTypeIndex->set(
            $state->currentClass(),
            $state->currentMethod(),
            $chosenType,
        );
    }

    /**
     * Collects one inferred structured function return type.
     *
     * @param Return_                   $return the return statement node
     * @param MemberGraphTraversalState $state  the current traversal state
     */
    private function collectFunctionReturn(Return_ $return, MemberGraphTraversalState $state): void
    {
        if ('' === $state->currentFunction()) {
            return;
        }

        $currentFunctionNode = $state->currentFunctionNode();

        if (!$currentFunctionNode instanceof Function_) {
            return;
        }

        if (!$return->expr instanceof Expr) {
            return;
        }

        if ($this->hasExplicitFunctionReturnPhpDoc($currentFunctionNode)) {
            return;
        }

        $declaredStructuredReturnType = $this->functionReturnStructuredTypeIndex->get(
            $state->currentFunction(),
        );

        $structuredType = $this->expressionTypeResolver->resolveStructuredPhpDocType(
            $return->expr,
            $state->variableTypes(),
            '',
            $state->currentTemplateDefinitions(),
            $this->usesByAlias,
        );

        if (!$structuredType instanceof ResolvedPhpDocType) {
            return;
        }

        $chosenType = $this->structuredPhpDocTypeSelector->choose(
            $declaredStructuredReturnType,
            $structuredType,
        );

        if (!$chosenType instanceof ResolvedPhpDocType) {
            return;
        }

        $this->functionReturnInferredStructuredTypeIndex->set(
            $state->currentFunction(),
            $chosenType,
        );
    }

    /**
     * Returns whether one method has an explicit @return PHPDoc tag.
     *
     * @param ClassMethod $method the method node
     */
    private function hasExplicitReturnPhpDoc(ClassMethod $method): bool
    {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($method);

        if (!$docComment instanceof Doc) {
            return false;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return false;
        }

        return [] !== $phpDocNode->getReturnTagValues();
    }

    /**
     * Returns whether one function has an explicit @return PHPDoc tag.
     *
     * @param Function_ $function the function node
     */
    private function hasExplicitFunctionReturnPhpDoc(Function_ $function): bool
    {
        $docComment = PhpDocInheritDocResolver::getEffectiveDocComment($function);

        if (!$docComment instanceof Doc) {
            return false;
        }

        $phpDocNode = $this->parsePhpDocNode($docComment);

        if (null === $phpDocNode) {
            return false;
        }

        return [] !== $phpDocNode->getReturnTagValues();
    }

    /**
     * Parses one PHPDoc node from one doc comment.
     *
     * @param Doc $docComment the doc comment
     */
    private function parsePhpDocNode(Doc $docComment): ?PhpDocNode
    {
        try {
            $factory = new PhpDocParserFactory();
            $tokens = new TokenIterator(
                $factory->createLexer()->tokenize($docComment->getText())
            );

            return $factory->createParser()->parse($tokens);
        } catch (\Throwable) {
            return null;
        }
    }
}
