<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;

/**
 * Resolves function-like call return types and template substitutions.
 */
final readonly class FunctionLikeCallResolver
{
    /**
     * Constructor.
     *
     * @param FunctionLikeFlatReturnResolver         $flatReturnResolver      the flat return resolver
     * @param FunctionLikeStructuredCallResolver     $structuredCallResolver  the structured call resolver
     * @param PhpDocValueExtractionStrategyInterface $valueExtractionStrategy the value extraction strategy
     */
    public function __construct(
        private FunctionLikeFlatReturnResolver $flatReturnResolver,
        private FunctionLikeStructuredCallResolver $structuredCallResolver,
        private PhpDocValueExtractionStrategyInterface $valueExtractionStrategy,
    ) {
    }

    /**
     * Resolves one function-like call to flat symbols.
     *
     * @param MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression             the call expression
     * @param string|null                                       $owner                  the method owner, or null for functions
     * @param string                                            $methodName             the method or function name
     * @param ExpressionResolutionContext                       $context                the expression resolution context
     * @param ExpressionTypeResolverInterface                   $fallbackResolver       the fallback expression resolver
     * @param ResolvedPhpDocType|null                           $receiverStructuredType the receiver structured type
     */
    public function resolveTypes(
        MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression,
        ?string $owner,
        string $methodName,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
        ?ResolvedPhpDocType $receiverStructuredType = null,
    ): SymbolCollection {
        $isMethodLike = null !== $owner;
        $structuredType = $this->resolveStructuredType(
            $expression,
            $owner,
            $methodName,
            $context,
            $fallbackResolver,
            $receiverStructuredType,
        );

        if ($structuredType instanceof ResolvedPhpDocType) {
            $resolved = $this->valueExtractionStrategy->extract($structuredType);

            if (!$resolved->isEmpty()) {
                return $resolved;
            }
        }

        return $this->flatReturnResolver->resolve($owner, $methodName, $isMethodLike);
    }

    /**
     * Resolves one function-like call to one structured PHPDoc type when possible.
     *
     * @param MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression             the call expression
     * @param string|null                                       $owner                  the method owner, or null for functions
     * @param string                                            $methodName             the method or function name
     * @param ExpressionResolutionContext                       $context                the expression resolution context
     * @param ExpressionTypeResolverInterface                   $fallbackResolver       the fallback expression resolver
     * @param ResolvedPhpDocType|null                           $receiverStructuredType the receiver structured type
     */
    public function resolveStructuredType(
        MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression,
        ?string $owner,
        string $methodName,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
        ?ResolvedPhpDocType $receiverStructuredType = null,
    ): ?ResolvedPhpDocType {
        return $this->structuredCallResolver->resolve(
            $expression,
            $owner,
            $methodName,
            $context,
            $fallbackResolver,
            $receiverStructuredType,
        );
    }
}
