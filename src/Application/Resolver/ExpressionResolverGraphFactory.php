<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver;

use PhpNoobs\MemberGraph\Application\Resolver\Expression\ArrayDimFetchExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\ArrayExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\ClassConstFetchExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\ConstFetchExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\FunctionCallExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\MethodCallExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\NewExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\PropertyFetchExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\StaticCallExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\StaticPropertyFetchExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Expression\VariableExpressionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ArgumentStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ArrayLiteralStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ArrayShapeAccessResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\CallableInvocationStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClassConstantOwnerResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClassNameResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureAssignmentVariableTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureDocTagExtractor;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureDocTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureLocalPhpDocTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureLocalVariableTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureParameterVariableTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ClosureReturnTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ConstructorArgumentParameterResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\ConstructorTemplateInferenceResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\DeclaringMethodResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeCallTemplateContextResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeFlatReturnResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeParameterResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeStructuredCallResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionLikeStructuredReturnResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionNameResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\FunctionStructuredReturnResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\InstancePropertyStructuredTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\LiteralValueResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\MethodCallOwnerResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\MethodStructuredReturnResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\NativeReturnTypePriorityResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\NativeTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\NewExpressionTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\OwnerTemplateSubstitutionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\PropertyTypeResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\SpecialClassReferenceNormalizer;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StructuredPhpDocTypeInspector;
use PhpNoobs\MemberGraph\Application\Resolver\Service\StructuredReturnTypeSelector;
use PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitutionCollector;
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
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;

/**
 * Builds the service and strategy graph used by the expression type resolver facade.
 */
final readonly class ExpressionResolverGraphFactory
{
    /**
     * Builds the resolver graph from all member graph indexes.
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
     * @param PropertyStructuredTypeIndex $globalPropertyStructuredTypeIndex The property structured type index.
     * @param ClassConstantTypeIndex $globalClassConstantTypeIndex The class constant type index.
     * @param ClassConstantValueIndex $globalClassConstantValueIndex The scalar class constant value index.
     * @param ClassTemplateDefinitionIndex $classTemplateDefinitionIndex The class template definition index.
     * @param KnownOwnerCollection $knownOwners The known owners collection.
     * @param PhpDocValueExtractionStrategyInterface $valueExtractionStrategy The value extraction strategy.
     *
     * @return ExpressionResolverGraph
     */
    public function create(
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
        PhpDocValueExtractionStrategyInterface $valueExtractionStrategy,
    ): ExpressionResolverGraph {
        $staticOwnerResolver = new StaticOwnerResolver($knownOwners);
        $specialClassReferenceNormalizer = new SpecialClassReferenceNormalizer($staticOwnerResolver);
        $structuredPhpDocTypeInspector = new StructuredPhpDocTypeInspector($globalMethodNodeIndex);
        $classNameResolver = new ClassNameResolver($staticOwnerResolver);
        $declaringMethodResolver = new DeclaringMethodResolver($globalMethodNodeIndex, $knownOwners);
        $classConstantOwnerResolver = new ClassConstantOwnerResolver($globalClassConstantTypeIndex, $knownOwners);
        $propertyTypeResolver = new PropertyTypeResolver(
            $globalPropertyTypeIndex,
            $globalPropertyStructuredTypeIndex,
            $knownOwners,
            $staticOwnerResolver,
        );
        $functionNameResolver = new FunctionNameResolver();
        $argumentStructuredTypeResolver = new ArgumentStructuredTypeResolver();
        $methodCallOwnerResolver = new MethodCallOwnerResolver(
            $structuredPhpDocTypeInspector,
            $argumentStructuredTypeResolver,
        );
        $functionLikeFlatReturnResolver = new FunctionLikeFlatReturnResolver(
            $globalMethodReturnTypeIndex,
            $globalFunctionReturnTypeIndex,
            $knownOwners,
            $specialClassReferenceNormalizer,
        );
        $structuredReturnTypeSelector = new StructuredReturnTypeSelector();
        $methodStructuredReturnResolver = new MethodStructuredReturnResolver(
            $globalMethodReturnTypeIndex,
            $globalMethodStructuredReturnTypeIndex,
            $globalMethodReturnInferredStructuredTypeIndex,
            $declaringMethodResolver,
            $structuredReturnTypeSelector,
        );
        $functionStructuredReturnResolver = new FunctionStructuredReturnResolver(
            $globalFunctionReturnTypeIndex,
            $globalFunctionStructuredReturnTypeIndex,
            $globalFunctionReturnInferredStructuredTypeIndex,
            $structuredReturnTypeSelector,
        );
        $functionLikeStructuredReturnResolver = new FunctionLikeStructuredReturnResolver(
            $methodStructuredReturnResolver,
            $functionStructuredReturnResolver,
            new NativeReturnTypePriorityResolver(),
        );
        $functionLikeParameterResolver = new FunctionLikeParameterResolver(
            $globalMethodStructuredParameterTypeIndex,
            $globalFunctionStructuredParameterTypeIndex,
            $declaringMethodResolver,
        );
        $templateSubstitutionCollector = new TemplateSubstitutionCollector();
        $ownerTemplateSubstitutionResolver = new OwnerTemplateSubstitutionResolver($classTemplateDefinitionIndex);
        $instancePropertyStructuredTypeResolver = new InstancePropertyStructuredTypeResolver(
            $globalPropertyStructuredTypeIndex,
            $ownerTemplateSubstitutionResolver,
            $argumentStructuredTypeResolver,
        );
        $functionLikeCallTemplateContextResolver = new FunctionLikeCallTemplateContextResolver(
            $functionLikeParameterResolver,
            $argumentStructuredTypeResolver,
            $ownerTemplateSubstitutionResolver,
            $templateSubstitutionCollector,
        );
        $functionLikeStructuredCallResolver = new FunctionLikeStructuredCallResolver(
            $functionLikeStructuredReturnResolver,
            $functionLikeCallTemplateContextResolver,
            $structuredPhpDocTypeInspector,
            $specialClassReferenceNormalizer,
        );
        $functionLikeCallResolver = new FunctionLikeCallResolver(
            $functionLikeFlatReturnResolver,
            $functionLikeStructuredCallResolver,
            $valueExtractionStrategy,
        );
        $nativeTypeResolver = new NativeTypeResolver($classNameResolver);
        $closureDocTypeResolver = new ClosureDocTypeResolver(
            new ClosureDocTagExtractor(),
            new ClosureLocalPhpDocTypeResolver($classNameResolver),
        );
        $closureParameterVariableTypeResolver = new ClosureParameterVariableTypeResolver(
            $nativeTypeResolver,
            $closureDocTypeResolver,
        );
        $closureAssignmentVariableTypeResolver = new ClosureAssignmentVariableTypeResolver(
            $closureDocTypeResolver,
            $argumentStructuredTypeResolver,
        );
        $closureLocalVariableTypeResolver = new ClosureLocalVariableTypeResolver(
            $closureParameterVariableTypeResolver,
            $closureAssignmentVariableTypeResolver,
        );
        $closureExpressionStructuredTypeResolver = new ClosureExpressionStructuredTypeResolver(
            $nativeTypeResolver,
            $closureDocTypeResolver,
            $closureLocalVariableTypeResolver,
            new ClosureReturnTypeResolver($argumentStructuredTypeResolver),
        );
        $callableInvocationStructuredTypeResolver = new CallableInvocationStructuredTypeResolver();
        $literalValueResolver = new LiteralValueResolver(
            $staticOwnerResolver,
            $classConstantOwnerResolver,
            $globalClassConstantValueIndex,
        );
        $arrayLiteralStructuredTypeResolver = new ArrayLiteralStructuredTypeResolver($literalValueResolver);
        $arrayShapeAccessResolver = new ArrayShapeAccessResolver($literalValueResolver);
        $constructorTemplateInferenceResolver = new ConstructorTemplateInferenceResolver(
            $globalMethodStructuredParameterTypeIndex,
            new ConstructorArgumentParameterResolver(),
            $argumentStructuredTypeResolver,
            new TemplateSubstitutionCollector(),
        );
        $newExpressionTypeResolver = new NewExpressionTypeResolver(
            $classNameResolver,
            $globalMethodNodeIndex,
            $classTemplateDefinitionIndex,
            $constructorTemplateInferenceResolver,
            $specialClassReferenceNormalizer,
        );

        return new ExpressionResolverGraph(
            new ExpressionTypeResolverRegistry([
                new VariableExpressionResolver(),
                new ArrayDimFetchExpressionResolver($arrayShapeAccessResolver),
                new ArrayExpressionResolver($arrayLiteralStructuredTypeResolver),
                new PropertyFetchExpressionResolver(
                    $structuredPhpDocTypeInspector,
                    $instancePropertyStructuredTypeResolver,
                    $propertyTypeResolver,
                ),
                new MethodCallExpressionResolver(
                    $methodCallOwnerResolver,
                    $argumentStructuredTypeResolver,
                    $functionLikeCallResolver,
                ),
                new StaticPropertyFetchExpressionResolver(
                    $propertyTypeResolver,
                    $structuredPhpDocTypeInspector,
                    $staticOwnerResolver,
                ),
                new StaticCallExpressionResolver($staticOwnerResolver, $functionLikeCallResolver),
                new FunctionCallExpressionResolver(
                    $callableInvocationStructuredTypeResolver,
                    $functionNameResolver,
                    $functionLikeCallResolver,
                ),
                new ClassConstFetchExpressionResolver($staticOwnerResolver, $classConstantOwnerResolver),
                new ConstFetchExpressionResolver(),
                new NewExpressionResolver($newExpressionTypeResolver),
            ]),
            $closureExpressionStructuredTypeResolver,
        );
    }
}
