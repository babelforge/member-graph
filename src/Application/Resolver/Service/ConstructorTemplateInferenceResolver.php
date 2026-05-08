<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;
use PhpParser\Node\Arg;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Infers class template substitutions from constructor arguments.
 */
final readonly class ConstructorTemplateInferenceResolver
{
    /**
     * Constructor.
     *
     * @param MethodParameterStructuredTypeIndex   $methodParameterStructuredTypeIndex   the structured method parameter type index
     * @param ConstructorArgumentParameterResolver $constructorArgumentParameterResolver the constructor argument parameter resolver
     * @param ArgumentStructuredTypeResolver       $argumentStructuredTypeResolver       the argument structured type resolver
     * @param TemplateSubstitutionCollector        $templateSubstitutionCollector        the template substitution collector
     */
    public function __construct(
        private MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex,
        private ConstructorArgumentParameterResolver $constructorArgumentParameterResolver,
        private ArgumentStructuredTypeResolver $argumentStructuredTypeResolver,
        private TemplateSubstitutionCollector $templateSubstitutionCollector,
    ) {
    }

    /**
     * Infers template substitutions from constructor arguments.
     *
     * @param list<Arg>                       $arguments        the constructor arguments
     * @param ClassMethod                     $constructorNode  the constructor method node
     * @param string                          $className        the constructed class FQCN
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function infer(
        array $arguments,
        ClassMethod $constructorNode,
        string $className,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): PhpDocTemplateSubstitutionContext {
        $substitutionContext = new PhpDocTemplateSubstitutionContext();

        foreach ($arguments as $position => $arg) {
            $this->collectArgumentSubstitution(
                $arg,
                $position,
                $constructorNode,
                $className,
                $context,
                $fallbackResolver,
                $substitutionContext,
            );
        }

        return $substitutionContext;
    }

    /**
     * Collects template substitutions from one constructor argument.
     *
     * @param Arg                               $arg                 the constructor argument
     * @param int                               $position            the positional argument index
     * @param ClassMethod                       $constructorNode     the constructor method node
     * @param string                            $className           the constructed class FQCN
     * @param ExpressionResolutionContext       $context             the current expression resolution context
     * @param ExpressionTypeResolverInterface   $fallbackResolver    the facade resolver for recursive resolution
     * @param PhpDocTemplateSubstitutionContext $substitutionContext the mutable substitution context
     */
    private function collectArgumentSubstitution(
        Arg $arg,
        int $position,
        ClassMethod $constructorNode,
        string $className,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
        PhpDocTemplateSubstitutionContext $substitutionContext,
    ): void {
        $parameterName = $this->constructorArgumentParameterResolver->resolve($arg, $position, $constructorNode);

        if (null === $parameterName || '' === $parameterName) {
            return;
        }

        $structuredParameterType = $this->methodParameterStructuredTypeIndex->get(
            $className,
            '__construct',
            $parameterName,
        );

        if (!$structuredParameterType instanceof ResolvedPhpDocType) {
            return;
        }

        $structuredArgumentType = $this->argumentStructuredTypeResolver->resolve(
            $arg->value,
            $context,
            $fallbackResolver,
        );

        if (!$structuredArgumentType instanceof ResolvedPhpDocType) {
            return;
        }

        $templateName = $structuredParameterType->templateReference->name;

        if ('' !== $templateName) {
            $this->templateSubstitutionCollector->set(
                $substitutionContext,
                $templateName,
                $structuredArgumentType,
            );

            return;
        }

        $this->templateSubstitutionCollector->collect(
            $structuredParameterType,
            $structuredArgumentType,
            $substitutionContext,
        );
    }
}
