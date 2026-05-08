<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTemplateReference;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\VariadicPlaceholder;

/**
 * Resolves structured PHPDoc types produced by object construction expressions.
 */
final readonly class NewExpressionTypeResolver
{
    /**
     * Constructor.
     *
     * @param ClassNameResolver                    $classNameResolver                    the class-name resolver
     * @param MethodNodeIndex                      $methodNodeIndex                      the global method node index
     * @param ClassTemplateDefinitionIndex         $classTemplateDefinitionIndex         the class template definition index
     * @param ConstructorTemplateInferenceResolver $constructorTemplateInferenceResolver the constructor template inference resolver
     * @param SpecialClassReferenceNormalizer      $specialClassReferenceNormalizer      the special class reference normalizer
     */
    public function __construct(
        private ClassNameResolver $classNameResolver,
        private MethodNodeIndex $methodNodeIndex,
        private ClassTemplateDefinitionIndex $classTemplateDefinitionIndex,
        private ConstructorTemplateInferenceResolver $constructorTemplateInferenceResolver,
        private SpecialClassReferenceNormalizer $specialClassReferenceNormalizer,
    ) {
    }

    /**
     * Resolves the structured PHPDoc type produced by a new-expression.
     *
     * @param New_                            $expression       the new-expression node
     * @param ExpressionResolutionContext     $context          the current expression resolution context
     * @param ExpressionTypeResolverInterface $fallbackResolver the facade resolver for recursive resolution
     */
    public function resolve(
        New_ $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $fallbackResolver,
    ): ?ResolvedPhpDocType {
        if (!$expression->class instanceof Name) {
            return null;
        }

        $className = $this->classNameResolver->resolve($expression->class, $context->currentClass, $context->usesByAlias);

        if ('' === $className) {
            return null;
        }

        $symbols = new SymbolCollection();
        $symbols->add($className);

        $classTemplateDefinitions = $this->classTemplateDefinitionIndex->get($className);

        if (null === $classTemplateDefinitions || $classTemplateDefinitions->isEmpty()) {
            return $this->normalize(ResolvedPhpDocType::regular($symbols), $context->currentClass);
        }

        $constructorNode = $this->methodNodeIndex->get($className, '__construct');

        if (!$constructorNode instanceof ClassMethod) {
            return $this->normalize(ResolvedPhpDocType::regular($symbols), $context->currentClass);
        }

        $substitutionContext = $this->constructorTemplateInferenceResolver->infer(
            $this->callArguments($expression->args),
            $constructorNode,
            $className,
            $context,
            $fallbackResolver,
        );
        $genericArguments = new ResolvedPhpDocTypeCollection();

        foreach ($classTemplateDefinitions as $templateDefinition) {
            if ($substitutionContext->has($templateDefinition->name)) {
                $substitutedType = $substitutionContext->get($templateDefinition->name);

                if ($substitutedType instanceof ResolvedPhpDocType) {
                    $genericArguments->add($substitutedType);
                }

                continue;
            }

            $genericArguments->add(
                ResolvedPhpDocType::template(
                    symbols: new SymbolCollection(),
                    templateReference: new ResolvedPhpDocTemplateReference($templateDefinition->name),
                ),
            );
        }

        if ($genericArguments->isEmpty()) {
            return $this->normalize(ResolvedPhpDocType::regular($symbols), $context->currentClass);
        }

        return $this->normalize(
            ResolvedPhpDocType::newGeneric($symbols, $genericArguments),
            $context->currentClass,
        );
    }

    /**
     * Normalizes one resolved new-expression type when a class context is available.
     *
     * @param ResolvedPhpDocType $type         the resolved type to normalize
     * @param string             $currentClass the current class FQCN
     */
    private function normalize(ResolvedPhpDocType $type, string $currentClass): ResolvedPhpDocType
    {
        if ('' === $currentClass) {
            return $type;
        }

        return $this->specialClassReferenceNormalizer->normalize($type, $currentClass);
    }

    /**
     * Filters parser call arguments to concrete argument nodes.
     *
     * @param array<array-key, Arg|VariadicPlaceholder> $arguments the parser arguments
     *
     * @return list<Arg>
     */
    private function callArguments(array $arguments): array
    {
        return array_values(array_filter($arguments, static fn (mixed $argument): bool => $argument instanceof Arg));
    }
}
