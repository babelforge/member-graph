<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Type\FunctionLikeReturnType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;
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
     * @param FunctionLikeParameterResolver     $parameterResolver                 the function-like parameter resolver
     * @param ArgumentStructuredTypeResolver    $argumentStructuredTypeResolver    the argument structured type resolver
     * @param OwnerTemplateSubstitutionResolver $ownerTemplateSubstitutionResolver the owner template substitution resolver
     * @param TemplateSubstitutionCollector     $templateSubstitutionCollector     the template substitution collector
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
     * @param MethodCall|NullsafeMethodCall|StaticCall|FuncCall $expression             the call expression
     * @param string|null                                       $owner                  the method owner, or null for functions
     * @param string                                            $methodName             the method or function name
     * @param bool                                              $isMethodLike           whether the call targets a method-like member
     * @param FunctionLikeReturnType|null                       $functionLikeReturnType the function-like return details
     * @param ExpressionResolutionContext                       $context                the expression resolution context
     * @param ExpressionTypeResolverInterface                   $fallbackResolver       the fallback expression resolver
     * @param ResolvedPhpDocType|null                           $receiverStructuredType the receiver structured type
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
