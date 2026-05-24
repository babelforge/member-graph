<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver;

use BabelForge\MemberGraph\Application\Resolver\Expression\ArrayDimFetchExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\ArrayExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\ClassConstFetchExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\ConstFetchExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\FunctionCallExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\MethodCallExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\NewExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\PropertyFetchExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\StaticCallExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\StaticPropertyFetchExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Expression\VariableExpressionResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ArgumentStructuredTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ArrayLiteralStructuredTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ArrayShapeAccessResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\CallableInvocationStructuredTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClassConstantOwnerResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClassNameResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureAssignmentVariableTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureDocTagExtractor;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureDocTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureExpressionStructuredTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureLocalPhpDocTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureLocalVariableTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureParameterVariableTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ClosureReturnTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ConstructorArgumentParameterResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\ConstructorTemplateInferenceResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\DeclaringMethodResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeCallResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeCallTemplateContextResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeFlatReturnResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeParameterResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeStructuredCallResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionLikeStructuredReturnResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionNameResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\FunctionStructuredReturnResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\InstancePropertyStructuredTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\LiteralValueResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\MethodCallOwnerResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\MethodStructuredReturnResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\NativeReturnTypePriorityResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\NativeTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\NewExpressionTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\OwnerTemplateSubstitutionResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\PropertyTypeResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\SpecialClassReferenceNormalizer;
use BabelForge\MemberGraph\Application\Resolver\Service\StaticOwnerResolver;
use BabelForge\MemberGraph\Application\Resolver\Service\StructuredPhpDocTypeInspector;
use BabelForge\MemberGraph\Application\Resolver\Service\StructuredReturnTypeSelector;
use BabelForge\MemberGraph\Application\Resolver\Service\TemplateSubstitutionCollector;
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
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\ValueExtraction\PhpDocValueExtractionStrategyInterface;

/**
 * Builds the service and strategy graph used by the expression type resolver facade.
 */
final readonly class ExpressionResolverGraphFactory
{
    /**
     * Builds the resolver graph from all member graph indexes.
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
     * @param PropertyStructuredTypeIndex               $globalPropertyStructuredTypeIndex               the property structured type index
     * @param ClassConstantTypeIndex                    $globalClassConstantTypeIndex                    the class constant type index
     * @param ClassConstantValueIndex                   $globalClassConstantValueIndex                   the scalar class constant value index
     * @param ClassTemplateDefinitionIndex              $classTemplateDefinitionIndex                    the class template definition index
     * @param KnownOwnerCollection                      $knownOwners                                     the known owners collection
     * @param PhpDocValueExtractionStrategyInterface    $valueExtractionStrategy                         the value extraction strategy
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
