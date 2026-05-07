<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;

/**
 * Coordinates closure-local PHPDoc tag extraction and type resolution.
 */
final readonly class ClosureDocTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClosureDocTagExtractor $closureDocTagExtractor The closure PHPDoc tag extractor.
     * @param ClosureLocalPhpDocTypeResolver $closureLocalPhpDocTypeResolver The closure-local PHPDoc type resolver.
     */
    public function __construct(
        private ClosureDocTagExtractor $closureDocTagExtractor,
        private ClosureLocalPhpDocTypeResolver $closureLocalPhpDocTypeResolver,
    ) {
    }

    /**
     * Resolves closure PHPDoc parameter types indexed by parameter name.
     *
     * @param Closure|ArrowFunction $expression The closure-like expression.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     *
     * @return array<string, ResolvedPhpDocType>
     */
    public function resolveParameterTypes(
        Closure|ArrowFunction $expression,
        ExpressionResolutionContext $context,
    ): array {
        $doc = $expression->getDocComment();

        if (null === $doc) {
            return [];
        }

        $parameterTypes = [];

        foreach ($this->closureDocTagExtractor->extractParameterTypes($doc) as $parameterName => $rawType) {
            $type = $this->closureLocalPhpDocTypeResolver->resolve(
                $rawType,
                $context->currentClass,
                $context->usesByAlias,
            );

            if ($type instanceof ResolvedPhpDocType) {
                $parameterTypes[$parameterName] = $type;
            }
        }

        return $parameterTypes;
    }

    /**
     * Resolves a simple local PHPDoc var type inside a closure.
     *
     * @param Node $node The node carrying the PHPDoc.
     * @param ExpressionResolutionContext $context The current expression resolution context.
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveLocalVarType(Node $node, ExpressionResolutionContext $context): ?ResolvedPhpDocType
    {
        $doc = $node->getDocComment();

        if (null === $doc) {
            return null;
        }

        $rawType = $this->closureDocTagExtractor->extractLocalVarType($doc);

        if (null === $rawType) {
            return null;
        }

        return $this->closureLocalPhpDocTypeResolver->resolve(
            $rawType,
            $context->currentClass,
            $context->usesByAlias,
        );
    }
}
