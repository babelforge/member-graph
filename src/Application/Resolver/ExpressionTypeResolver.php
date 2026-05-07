<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnInferredStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\CollectionLikePhpDocValueExtractionStrategy;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
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
     * @param MethodReturnTypeIndex $globalMethodReturnTypeIndex The method return type index.
     * @param MethodNodeIndex $globalMethodNodeIndex The method node index.
     * @param MethodReturnStructuredTypeIndex $globalMethodStructuredReturnTypeIndex The method structured return type index.
     * @param MethodParameterStructuredTypeIndex $globalMethodStructuredParameterTypeIndex The method structured parameter type index.
     * @param MethodReturnInferredStructuredTypeIndex $globalMethodReturnInferredStructuredTypeIndex The method inferred structured return type index.
     * @param FunctionReturnTypeIndex $globalFunctionReturnTypeIndex The function return type index.
     * @param FunctionReturnStructuredTypeIndex $globalFunctionStructuredReturnTypeIndex The function structured return type index.
     * @param FunctionParameterStructuredTypeIndex $globalFunctionStructuredParameterTypeIndex The function structured parameter type index.
     * @param FunctionReturnInferredStructuredTypeIndex $globalFunctionReturnInferredStructuredTypeIndex The function inferred structured return type index.
     * @param PropertyTypeIndex $globalPropertyTypeIndex The property type index.
     * @param PropertyStructuredTypeIndex $globalPropertyStructuredTypeIndex The property structured type build result.
     * @param ClassConstantTypeIndex $globalClassConstantTypeIndex The class constant type index.
     * @param ClassConstantValueIndex $globalClassConstantValueIndex The scalar class constant value index.
     * @param ClassTemplateDefinitionIndex $classTemplateDefinitionIndex The class template definition index.
     * @param KnownOwnerCollection $knownOwners The known owners collection.
     * @param PhpDocValueExtractionStrategyInterface $valueExtractionStrategy The value extraction strategy.
     */
    public function __construct(
        MethodReturnTypeIndex                             $globalMethodReturnTypeIndex,
        MethodNodeIndex                                   $globalMethodNodeIndex,
        MethodReturnStructuredTypeIndex                   $globalMethodStructuredReturnTypeIndex,
        MethodParameterStructuredTypeIndex                $globalMethodStructuredParameterTypeIndex,
        MethodReturnInferredStructuredTypeIndex           $globalMethodReturnInferredStructuredTypeIndex,
        FunctionReturnTypeIndex                           $globalFunctionReturnTypeIndex,
        FunctionReturnStructuredTypeIndex                 $globalFunctionStructuredReturnTypeIndex,
        FunctionParameterStructuredTypeIndex              $globalFunctionStructuredParameterTypeIndex,
        FunctionReturnInferredStructuredTypeIndex         $globalFunctionReturnInferredStructuredTypeIndex,
        PropertyTypeIndex                                 $globalPropertyTypeIndex,
        PropertyStructuredTypeIndex                       $globalPropertyStructuredTypeIndex,
        ClassConstantTypeIndex                            $globalClassConstantTypeIndex,
        ClassConstantValueIndex                           $globalClassConstantValueIndex,
        ClassTemplateDefinitionIndex                      $classTemplateDefinitionIndex,
        KnownOwnerCollection                              $knownOwners,
        private PhpDocValueExtractionStrategyInterface    $valueExtractionStrategy = new CollectionLikePhpDocValueExtractionStrategy(),
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
     * @param MemberGraphBuildContext $context The member graph build context.
     * @param PhpDocValueExtractionStrategyInterface $valueExtractionStrategy The value extraction strategy.
     *
     * @return self
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
     * @inheritDoc
     */
    public function resolve(
        Node                               $expression,
        array                              $variableTypes,
        string                             $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection              $usesByAlias,
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


    /**
     * @inheritDoc
     */
    public function resolveStructuredPhpDocType(
        Expr                               $expression,
        array                              $variableTypes,
        string                             $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection              $usesByAlias,
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

    /**
     * @inheritDoc
     */
    public function extractStructuredSymbols(?ResolvedPhpDocType $structuredType): SymbolCollection
    {
        if (!$structuredType instanceof ResolvedPhpDocType) {
            return new SymbolCollection();
        }

        return $this->valueExtractionStrategy->extract($structuredType);
    }

}
