<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Type\FunctionLikeReturnType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;

/**
 * Builds template substitution contexts for function-like calls.
 */
final readonly class FunctionLikeCallTemplateContextResolver
{
    /**
     * Constructor.
     *
     * @param FunctionLikeParameterResolver $parameterResolver The function-like parameter resolver.
     * @param ArgumentStructuredTypeResolver $argumentStructuredTypeResolver The argument structured type resolver.
     * @param OwnerTemplateSubstitutionResolver $ownerTemplateSubstitutionResolver The owner template substitution resolver.
     * @param TemplateSubstitutionCollector $templateSubstitutionCollector The template substitution collector.
     */
    public function __construct(
        private FunctionLikeParameterResolver $parameterResolver,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
        private OwnerTemplateSubstitutionResolver $ownerTemplateSubstitutionResolver,
        private TemplateSubstitutionCollector $templateSubstitutionCollector,
    ) {
    }

    /**
     * Resolves the substitution context for one function-like call.
     *
     * @param MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression The call expression.
     * @param string|null $owner The method owner, or null for functions.
     * @param string $methodName The method or function name.
     * @param bool $isMethodLike Whether the call targets a method-like member.
     * @param FunctionLikeReturnType|null $functionLikeReturnType The function-like return details.
     * @param ExpressionResolutionContext $context The expression resolution context.
     * @param ExpressionTypeResolverInterface $fallbackResolver The fallback expression resolver.
     * @param ResolvedPhpDocType|null $receiverStructuredType The receiver structured type.
     *
     * @return PhpDocTemplateSubstitutionContext
     */
    public function resolve(
        MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression,
        ?string $owner,
        string $methodName,
        bool $isMethodLike,
        ?FunctionLikeReturnType $functionLikeReturnType,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
        ?ResolvedPhpDocType $receiverStructuredType,
    ): PhpDocTemplateSubstitutionContext {
        $substitutionContext = new PhpDocTemplateSubstitutionContext();

        if ($isMethodLike && null !== $owner && $receiverStructuredType instanceof ResolvedPhpDocType) {
            $ownerTemplateContext = $this->ownerTemplateSubstitutionResolver->collect(
                owner: $owner,
                receiverStructuredType: $receiverStructuredType,
            );

            $this->ownerTemplateSubstitutionResolver->mergeInto(
                target: $substitutionContext,
                source: $ownerTemplateContext,
            );
        }

        if (null === $functionLikeReturnType) {
            return $substitutionContext;
        }

        foreach ($expression->args as $position => $arg) {
            if (!$arg instanceof Arg) {
                continue;
            }

            $parameterName = $this->parameterResolver->resolveCallParameterName(
                $arg,
                $position,
                $functionLikeReturnType->parentNode,
                $isMethodLike,
            );

            if (null === $parameterName || '' === $parameterName) {
                continue;
            }

            $structuredParameterType = $this->parameterResolver->resolveStructuredParameterType(
                $owner,
                $methodName,
                $parameterName,
                $isMethodLike,
            );

            if (null === $structuredParameterType) {
                continue;
            }

            $structuredArgumentType = $this->argumentStructuredTypeResolver->resolve($arg->value, $context, $fallbackResolver);

            if (null === $structuredArgumentType) {
                continue;
            }

            $templateName = $structuredParameterType->templateReference->name;

            if ('' !== $templateName) {
                $this->templateSubstitutionCollector->set($substitutionContext, $templateName, $structuredArgumentType);
                continue;
            }

            $this->templateSubstitutionCollector->collect(
                $structuredParameterType,
                $structuredArgumentType,
                $substitutionContext,
            );
        }

        return $substitutionContext;
    }
}
