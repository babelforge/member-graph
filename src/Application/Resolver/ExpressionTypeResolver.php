<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver;

use BabelForge\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\CollectionLikePhpDocValueExtractionStrategy;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Float_;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;

/**
 * Resolves simple expression types without external analyzers.
 */
final readonly class ExpressionTypeResolver implements ExpressionTypeResolverInterface
{
    private ExpressionTypeResolverRegistry $expressionTypeResolverRegistry;

    private ClosureExpressionStructuredTypeResolver $closureExpressionStructuredTypeResolver;

    /**
     * Constructor.
     *
     * @param MethodReturnTypeIndex                     $globalMethodReturnTypeIndex                     the method return type index
     * @param MethodNodeIndex                           $globalMethodNodeIndex                           the method node index
     * @param MethodReturnStructuredTypeIndex           $globalMethodStructuredReturnTypeIndex           the method structured return type index
     * @param MethodParameterStructuredTypeIndex        $globalMethodStructuredParameterTypeIndex        the method structured parameter type index
     * @param MethodReturnInferredStructuredTypeIndex   $globalMethodReturnInferredStructuredTypeIndex   the method inferred structured return type index
     * @param FunctionReturnTypeIndex                   $globalFunctionReturnTypeIndex                   the function return type index
     * @param FunctionReturnStructuredTypeIndex         $globalFunctionStructuredReturnTypeIndex         the function structured return type index
     * @param FunctionParameterStructuredTypeIndex      $globalFunctionStructuredParameterTypeIndex      the function structured parameter type index
     * @param FunctionReturnInferredStructuredTypeIndex $globalFunctionReturnInferredStructuredTypeIndex the function inferred structured return type index
     * @param PropertyTypeIndex                         $globalPropertyTypeIndex                         the property type index
     * @param PropertyStructuredTypeIndex               $globalPropertyStructuredTypeIndex               the property structured type build result
     * @param ClassConstantTypeIndex                    $globalClassConstantTypeIndex                    the class constant type index
     * @param ClassConstantValueIndex                   $globalClassConstantValueIndex                   the scalar class constant value index
     * @param ClassTemplateDefinitionIndex              $classTemplateDefinitionIndex                    the class template definition index
     * @param KnownOwnerCollection                      $knownOwners                                     the known owners collection
     * @param PhpDocValueExtractionStrategyInterface    $valueExtractionStrategy                         the value extraction strategy
     */
    public function __construct(
        MethodReturnTypeIndex $globalMethodReturnTypeIndex,
        MethodNodeIndex $globalMethodNodeIndex,
        MethodReturnStructuredTypeIndex $globalMethodStructuredReturnTypeIndex,
        MethodParameterStructuredTypeIndex $globalMethodStructuredParameterTypeIndex,
        MethodReturnInferredStructuredTypeIndex $globalMethodReturnInferredStructuredTypeIndex,
        FunctionReturnTypeIndex $globalFunctionReturnTypeIndex,
        FunctionReturnStructuredTypeIndex $globalFunctionStructuredReturnTypeIndex,
        FunctionParameterStructuredTypeIndex $globalFunctionStructuredParameterTypeIndex,
        FunctionReturnInferredStructuredTypeIndex $globalFunctionReturnInferredStructuredTypeIndex,
        PropertyTypeIndex $globalPropertyTypeIndex,
        PropertyStructuredTypeIndex $globalPropertyStructuredTypeIndex,
        ClassConstantTypeIndex $globalClassConstantTypeIndex,
        ClassConstantValueIndex $globalClassConstantValueIndex,
        ClassTemplateDefinitionIndex $classTemplateDefinitionIndex,
        KnownOwnerCollection $knownOwners,
        private PhpDocValueExtractionStrategyInterface $valueExtractionStrategy = new CollectionLikePhpDocValueExtractionStrategy(),
    ) {
        $graph = new ExpressionResolverGraphFactory()->create(
            globalMethodReturnTypeIndex: $globalMethodReturnTypeIndex,
            globalMethodNodeIndex: $globalMethodNodeIndex,
            globalMethodStructuredReturnTypeIndex: $globalMethodStructuredReturnTypeIndex,
            globalMethodStructuredParameterTypeIndex: $globalMethodStructuredParameterTypeIndex,
            globalMethodReturnInferredStructuredTypeIndex: $globalMethodReturnInferredStructuredTypeIndex,
            globalFunctionReturnTypeIndex: $globalFunctionReturnTypeIndex,
            globalFunctionStructuredReturnTypeIndex: $globalFunctionStructuredReturnTypeIndex,
            globalFunctionStructuredParameterTypeIndex: $globalFunctionStructuredParameterTypeIndex,
            globalFunctionReturnInferredStructuredTypeIndex: $globalFunctionReturnInferredStructuredTypeIndex,
            globalPropertyTypeIndex: $globalPropertyTypeIndex,
            globalPropertyStructuredTypeIndex: $globalPropertyStructuredTypeIndex,
            globalClassConstantTypeIndex: $globalClassConstantTypeIndex,
            globalClassConstantValueIndex: $globalClassConstantValueIndex,
            classTemplateDefinitionIndex: $classTemplateDefinitionIndex,
            knownOwners: $knownOwners,
            valueExtractionStrategy: $this->valueExtractionStrategy,
        );

        $this->expressionTypeResolverRegistry = $graph->expressionTypeResolverRegistry;
        $this->closureExpressionStructuredTypeResolver = $graph->closureExpressionStructuredTypeResolver;
    }

    /**
     * Creates an expression type resolver from a member graph build context.
     *
     * @param MemberGraphBuildContext                $context                 the member graph build context
     * @param PhpDocValueExtractionStrategyInterface $valueExtractionStrategy the value extraction strategy
     */
    public static function fromMemberGraphBuildContext(
        MemberGraphBuildContext $context,
        PhpDocValueExtractionStrategyInterface $valueExtractionStrategy = new CollectionLikePhpDocValueExtractionStrategy(),
    ): self {
        return new self(
            globalMethodReturnTypeIndex: $context->methodReturnTypeIndex,
            globalMethodNodeIndex: $context->methodNodeIndex,
            globalMethodStructuredReturnTypeIndex: $context->methodReturnStructuredTypeIndex,
            globalMethodStructuredParameterTypeIndex: $context->methodParameterStructuredTypeIndex,
            globalMethodReturnInferredStructuredTypeIndex: $context->methodReturnInferredStructuredTypeIndex,
            globalFunctionReturnTypeIndex: $context->functionReturnTypeIndex,
            globalFunctionStructuredReturnTypeIndex: $context->functionReturnStructuredTypeIndex,
            globalFunctionStructuredParameterTypeIndex: $context->functionParameterStructuredTypeIndex,
            globalFunctionReturnInferredStructuredTypeIndex: $context->functionReturnInferredStructuredTypeIndex,
            globalPropertyTypeIndex: $context->propertyTypeIndex,
            globalPropertyStructuredTypeIndex: $context->propertyStructuredTypeIndex,
            globalClassConstantTypeIndex: $context->classConstantTypeIndex,
            globalClassConstantValueIndex: $context->classConstantValueIndex,
            classTemplateDefinitionIndex: $context->classTemplateDefinitionIndex,
            knownOwners: $context->knownOwners,
            valueExtractionStrategy: $valueExtractionStrategy,
        );
    }

    /**
     * Resolves the best-known type for one expression.
     *
     * {@inheritDoc}
     */
    public function resolve(
        Node $expression,
        array $variableTypes,
        string $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection $usesByAlias,
    ): SymbolCollection {
        $context = new ExpressionResolutionContext(
            variableTypes: $variableTypes,
            currentClass: $currentClass,
            templateDefinitions: $templateDefinitions,
            usesByAlias: $usesByAlias,
        );
        $registryResult = $this->expressionTypeResolverRegistry->resolve($expression, $context, $this);

        if ($registryResult instanceof SymbolCollection) {
            return $registryResult;
        }

        $types = new SymbolCollection();

        if ($expression instanceof Name) {
            $resolvedName = $expression->getAttribute('resolvedName');

            if ($resolvedName instanceof Name) {
                return $types->add($resolvedName->toString());
            }

            return $types->add($expression->toString());
        }

        return $types;
    }

    public function resolveStructuredPhpDocType(
        Expr $expression,
        array $variableTypes,
        string $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType {
        $context = new ExpressionResolutionContext(
            variableTypes: $variableTypes,
            currentClass: $currentClass,
            templateDefinitions: $templateDefinitions,
            usesByAlias: $usesByAlias,
        );
        $registryResult = $this->expressionTypeResolverRegistry->resolveStructuredPhpDocType($expression, $context, $this);

        if ($registryResult instanceof ResolvedPhpDocType) {
            return $registryResult;
        }

        if ($expression instanceof String_) {
            $symbols = new SymbolCollection();
            $symbols->add('string');

            return ResolvedPhpDocType::regular($symbols);
        }

        if ($expression instanceof Int_) {
            $symbols = new SymbolCollection();
            $symbols->add('int');

            return ResolvedPhpDocType::regular($symbols);
        }

        if ($expression instanceof Float_) {
            $symbols = new SymbolCollection();
            $symbols->add('float');

            return ResolvedPhpDocType::regular($symbols);
        }

        if ($expression instanceof Closure || $expression instanceof ArrowFunction) {
            return $this->closureExpressionStructuredTypeResolver->resolve($expression, $context, $this);
        }

        return null;
    }

    public function extractStructuredSymbols(?ResolvedPhpDocType $structuredType): SymbolCollection
    {
        if (!$structuredType instanceof ResolvedPhpDocType) {
            return new SymbolCollection();
        }

        return $this->valueExtractionStrategy->extract($structuredType);
    }
}
