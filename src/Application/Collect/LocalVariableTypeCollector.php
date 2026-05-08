<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Traverse\MemberGraphTraversalState;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeSource;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\StructuredPhpDocTypeSelector;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\UnionType;

/**
 * Collects local variable type information discovered during member graph traversal.
 */
final readonly class LocalVariableTypeCollector
{
    /**
     * Constructor.
     *
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The expression type resolver.
     * @param LocalVarPhpDocTypeExtractor $localVarPhpDocTypeExtractor The local variable PHPDoc type extractor.
     * @param ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor The parameter PHPDoc type extractor.
     * @param MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex The method parameter structured type index.
     * @param FunctionParameterStructuredTypeIndex $functionParameterStructuredTypeIndex The function parameter structured type index.
     * @param UsesByAliasCollection $usesByAlias The current use imports indexed by alias.
     * @param StructuredPhpDocTypeSelector $structuredPhpDocTypeSelector The structured PHPDoc type selector.
     * @param VariableTypePropagationResolver $variableTypePropagationResolver The variable type propagation resolver.
     */
    public function __construct(
        private ExpressionTypeResolverInterface $expressionTypeResolver,
        private LocalVarPhpDocTypeExtractor $localVarPhpDocTypeExtractor,
        private ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor,
        private MethodParameterStructuredTypeIndex $methodParameterStructuredTypeIndex,
        private FunctionParameterStructuredTypeIndex $functionParameterStructuredTypeIndex,
        private UsesByAliasCollection $usesByAlias,
        private StructuredPhpDocTypeSelector $structuredPhpDocTypeSelector,
        private VariableTypePropagationResolver $variableTypePropagationResolver,
    ) {
    }

    /**
     * Collects variable type information from one assignment.
     *
     * @param Assign $assign The assignment node.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return void
     */
    public function collectAssignment(Assign $assign, MemberGraphTraversalState $state): void
    {
        if (!$assign->var instanceof Variable || !is_string($assign->var->name)) {
            return;
        }

        $variableName = $assign->var->name;
        $previousTypeInfo = $state->variableType($variableName);
        $resolvedTypes = $this->resolveAssignedExprTypes($assign->expr, $state);
        $currentStructuredType = $this->resolveAssignedExprStructuredType($assign->expr, $state);

        $structuredPhpDocType = $this->variableTypePropagationResolver->chooseAssignmentStructuredPhpDocType(
            $previousTypeInfo instanceof VariableTypeInfo ? $previousTypeInfo : null,
            $currentStructuredType,
        );
        $structuredExtractedTypes = $this->expressionTypeResolver->extractStructuredSymbols($structuredPhpDocType);

        /**
         * This assignment replaces the previous value with something that cannot be
         * resolved to any meaningful type. The previous assignment-based type must
         * therefore be forgotten.
         */
        $resolvedTypeInfo = $this->variableTypePropagationResolver->resolveAssignmentTypeInfo(
            $previousTypeInfo instanceof VariableTypeInfo ? $previousTypeInfo : null,
            $resolvedTypes,
            $currentStructuredType,
            $structuredExtractedTypes,
        );

        if (!$resolvedTypeInfo instanceof VariableTypeInfo) {
            $state->forgetAssignmentVariableType($variableName);

            return;
        }

        $state->setVariableType($variableName, $resolvedTypeInfo);
    }

    /**
     * Collects parameter types into the local variable type map.
     *
     * @param Param[] $params The parameters to collect.
     * @param string $methodOrFunctionName The name of the method or function.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return void
     */
    public function collectParameters(array $params, string $methodOrFunctionName, MemberGraphTraversalState $state): void
    {
        foreach ($params as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $resolvedTypes = $this->resolveParameterTypes($param->type);

            if ($resolvedTypes->isEmpty()) {
                continue;
            }

            $variableName = $param->var->name;
            $previousTypeInfo = $state->variableType($variableName);
            $previousStructuredType = $previousTypeInfo instanceof VariableTypeInfo
                ? $previousTypeInfo->structuredPhpDocType
                : null;
            $currentStructuredType = $this->resolveParameterStructuredType($param, $methodOrFunctionName, $state);
            $structuredPhpDocType = $this->structuredPhpDocTypeSelector->choose(
                $previousStructuredType,
                $currentStructuredType,
            );

            $state->setVariableType($variableName, new VariableTypeInfo(
                types: $resolvedTypes,
                source: VariableTypeSource::PARAMETER,
                structuredPhpDocType: $structuredPhpDocType,
            ));
        }
    }

    /**
     * Collects parameter types from PHPDoc when native types did not resolve anything useful.
     *
     * Native parameter types remain authoritative.
     *
     * @param ClassMethod|Function_ $functionLike The function-like node.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return void
     */
    public function collectParametersFromPhpDoc(
        ClassMethod|Function_ $functionLike,
        MemberGraphTraversalState $state,
    ): void {
        $resolvedByParameter = $this->paramPhpDocTypeExtractor->extract(
            node: $functionLike,
            currentNamespace: $state->currentNamespace(),
            usesByAlias: $this->usesByAlias,
            templateDefinitions: $state->currentTemplateDefinitions(),
            context: $state->context(),
        );

        foreach ($resolvedByParameter as $parameterName => $resolvedType) {
            if ($state->hasNonEmptyVariableTypes($parameterName)) {
                continue;
            }

            if ($resolvedType->types->isEmpty()) {
                continue;
            }

            $state->setVariableType($parameterName, new VariableTypeInfo(
                types: $resolvedType->types,
                source: VariableTypeSource::PHPDOC,
                structuredPhpDocType: $resolvedType->structuredType,
            ));
        }
    }

    /**
     * Collects variable type information from a local @var docblock.
     *
     * @param Expression $expression The statement expression node.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return void
     */
    public function collectLocalVarPhpDoc(Expression $expression, MemberGraphTraversalState $state): void
    {
        $resolved = $this->localVarPhpDocTypeExtractor->extract(
            node: $expression,
            currentNamespace: $state->currentNamespace(),
            usesByAlias: $this->usesByAlias,
            templateDefinitions: $state->currentTemplateDefinitions(),
            context: $state->context(),
            kind: PhpDocTagKind::VAR
        );

        if (null === $resolved) {
            return;
        }

        $state->setVariableType($resolved->variableName, new VariableTypeInfo(
            types: $resolved->types,
            source: VariableTypeSource::PHPDOC,
            structuredPhpDocType: $resolved->structuredType,
        ));
    }

    /**
     * Resolves the structured type of one assignment expression when possible.
     *
     * @param Expr $expression The assigned expression.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveAssignedExprStructuredType(
        Expr $expression,
        MemberGraphTraversalState $state,
    ): ?ResolvedPhpDocType {
        return $this->expressionTypeResolver->resolveStructuredPhpDocType(
            expression: $expression,
            variableTypes: $state->variableTypes(),
            currentClass: $state->currentClass(),
            templateDefinitions: $state->currentTemplateDefinitions(),
            usesByAlias: $this->usesByAlias,
        );
    }

    /**
     * Resolves the type produced by a simple assignment expression.
     *
     * @param Expr $expression The assigned expression.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return SymbolCollection
     */
    private function resolveAssignedExprTypes(Expr $expression, MemberGraphTraversalState $state): SymbolCollection
    {
        $structuredType = $this->resolveAssignedExprStructuredType($expression, $state);

        if ($structuredType instanceof ResolvedPhpDocType) {
            return $this->variableTypePropagationResolver->extractAssignmentSymbols($structuredType);
        }

        return $this->resolveExprTypes($expression, $state);
    }

    /**
     * Resolves parameter types from a declared parameter type node.
     *
     * @param null|Identifier|Name|ComplexType $type The parameter type node.
     *
     * @return SymbolCollection
     */
    private function resolveParameterTypes(
        null|Identifier|Name|ComplexType $type,
    ): SymbolCollection {
        if ($type instanceof Name) {
            $resolvedName = $type->getAttribute('resolvedName');

            if ($resolvedName instanceof Name) {
                return new SymbolCollection()->add($resolvedName->toString());
            }

            return new SymbolCollection()->add($type->toString());
        }

        if ($type instanceof NullableType) {
            return $this->resolveParameterTypes($type->type);
        }

        $resolvedTypes = new SymbolCollection();

        if ($type instanceof UnionType) {
            foreach ($type->types as $subType) {
                foreach ($this->resolveParameterTypes($subType) as $resolvedType) {
                    if ('' === $resolvedType) {
                        continue;
                    }

                    $resolvedTypes->add($resolvedType);
                }
            }

            return $resolvedTypes;
        }

        if ($type instanceof IntersectionType) {
            foreach ($type->types as $subType) {
                foreach ($this->resolveParameterTypes($subType) as $resolvedType) {
                    if ('' === $resolvedType) {
                        continue;
                    }

                    $resolvedTypes->add($resolvedType);
                }
            }

            return $resolvedTypes;
        }

        return $resolvedTypes;
    }

    /**
     * Resolves the structured type of one parameter when possible.
     *
     * @param Param $param The parameter node.
     * @param string $methodOrFunctionName The name of the method or function.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return ResolvedPhpDocType|null
     */
    private function resolveParameterStructuredType(
        Param $param,
        string $methodOrFunctionName,
        MemberGraphTraversalState $state,
    ): ?ResolvedPhpDocType {
        $parameterName = $param->var instanceof Variable && is_string($param->var->name)
            ? $param->var->name
            : null;

        if (null === $parameterName || '' === $parameterName) {
            return null;
        }

        if ('' === $methodOrFunctionName) {
            return null;
        }

        if ('' !== $state->currentClass()) {
            return $this->methodParameterStructuredTypeIndex
                ->get($state->currentClass(), $methodOrFunctionName, $parameterName);
        }

        return $this->functionParameterStructuredTypeIndex
            ->get($methodOrFunctionName, $parameterName);
    }

    /**
     * Resolves the best-known owner type for one expression.
     *
     * @param Expr $expression The expression node.
     * @param MemberGraphTraversalState $state The current traversal state.
     *
     * @return SymbolCollection
     */
    private function resolveExprTypes(Expr $expression, MemberGraphTraversalState $state): SymbolCollection
    {
        $types = $this->expressionTypeResolver->resolve(
            expression: $expression,
            variableTypes: $state->variableTypes(),
            currentClass: $state->currentClass(),
            templateDefinitions: $state->currentTemplateDefinitions(),
            usesByAlias: $this->usesByAlias,
        );

        if ($types->isEmpty()) {
            $types->add('unknown');
        }

        return $types;
    }
}
