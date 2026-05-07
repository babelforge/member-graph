<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Traverse;

use PhpNoobs\MemberGraph\Application\Build\Context\MemberGraphBuildContext;
use PhpNoobs\MemberGraph\Application\Collect\InferredStructuredReturnCollector;
use PhpNoobs\MemberGraph\Application\Collect\LocalVariableTypeCollector;
use PhpNoobs\MemberGraph\Application\Collect\MemberDeclarationCollector;
use PhpNoobs\MemberGraph\Application\Collect\MemberUsageCollector;
use PhpNoobs\MemberGraph\Application\Collect\ParameterUsageCollector;
use PhpNoobs\MemberGraph\Application\Collect\VariableTypePropagationResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\LocalVarPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\StructuredPhpDocTypeSelector;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\VarLikeIdentifier;
use PhpParser\NodeVisitorAbstract;

/**
 * Class MemberGraphBuilderVisitor
 */
final class MemberGraphBuilderVisitor extends NodeVisitorAbstract
{
    private MemberGraphTraversalState $state;

    private MemberDeclarationCollector $memberDeclarationCollector;

    private MemberUsageCollector $memberUsageCollector;

    private ParameterUsageCollector $parameterUsageCollector;

    private InferredStructuredReturnCollector $inferredStructuredReturnCollector;

    private LocalVariableTypeCollector $localVariableTypeCollector;

    private MemberGraphBuildContext $context;

    /**
     * @param string $fullFilePath The full file path.
     * @param string $virtualFilePath The current virtual file path.
     * @param MemberDeclarationCollection $declarations The declarations collection.
     * @param MemberUsageCollection $usages The usages collection.
     * @param ParameterUsageCollection $parameterUsages The parameter usages collection.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The expression type resolver.
     * @param LocalVarPhpDocTypeExtractor $localVarPhpDocTypeExtractor The local variable type extractor.
     * @param ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor The parameter type extractor.
     * @param PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor The PHPDoc template definition extractor.
     * @param UsesByAliasCollection $usesByAlias The uses by alias map.
     * @param MemberGraphBuildContext $context The enriched member graph build context.
     */
    public function __construct(
        string                                                     $fullFilePath,
        string                                                     $virtualFilePath,
        MemberDeclarationCollection                                $declarations,
        MemberUsageCollection                                      $usages,
        ParameterUsageCollection                                   $parameterUsages,
        private readonly ExpressionTypeResolverInterface           $expressionTypeResolver,
        LocalVarPhpDocTypeExtractor                                $localVarPhpDocTypeExtractor,
        ParamPhpDocTypeExtractor                                   $paramPhpDocTypeExtractor,
        private readonly PhpDocTemplateDefinitionExtractor         $phpDocTemplateDefinitionExtractor,
        private readonly UsesByAliasCollection                     $usesByAlias,
        MemberGraphBuildContext                                    $context,
    ) {
        $this->context = $context;
        $this->state = new MemberGraphTraversalState($fullFilePath, $virtualFilePath);
        $this->memberDeclarationCollector = new MemberDeclarationCollector($declarations, $virtualFilePath);
        $this->memberUsageCollector = new MemberUsageCollector(
            $usages,
            $this->context->polymorphicImplementationsIndex,
            $virtualFilePath,
        );
        $this->parameterUsageCollector = new ParameterUsageCollector(
            $parameterUsages,
            $this->context->polymorphicImplementationsIndex,
            $virtualFilePath,
        );
        $structuredPhpDocTypeSelector = new StructuredPhpDocTypeSelector();
        $this->inferredStructuredReturnCollector = new InferredStructuredReturnCollector(
            $this->expressionTypeResolver,
            $this->context->methodReturnStructuredTypeIndex,
            $this->context->methodReturnInferredStructuredTypeIndex,
            $this->context->functionReturnStructuredTypeIndex,
            $this->context->functionReturnInferredStructuredTypeIndex,
            $this->usesByAlias,
            $structuredPhpDocTypeSelector,
        );
        $variableTypePropagationResolver = new VariableTypePropagationResolver();
        $this->localVariableTypeCollector = new LocalVariableTypeCollector(
            $this->expressionTypeResolver,
            $localVarPhpDocTypeExtractor,
            $paramPhpDocTypeExtractor,
            $this->context->methodParameterStructuredTypeIndex,
            $this->context->functionParameterStructuredTypeIndex,
            $this->usesByAlias,
            $structuredPhpDocTypeSelector,
            $variableTypePropagationResolver,
        );
    }

    /**
     * Handles node entry.
     *
     * @param Node $node The current node.
     *
     * @return null
     */
    public function enterNode(Node $node): null
    {
        if ($node instanceof Namespace_) {
            $this->state->enterNamespace($node->name->toString());
        }

        if ((
            ($node instanceof Class_)
                || ($node instanceof Interface_)
                || ($node instanceof Trait_)
                || ($node instanceof Enum_)
        ) && isset($node->namespacedName)) {
            $this->enterClassLikeNode($node);

            return null;
        }

        if ($node instanceof ClassMethod) {
            $this->enterClassMethodNode($node);

            return null;
        }

        if ($node instanceof Function_ && isset($node->namespacedName)) {
            $this->enterFunctionNode($node);

            return null;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            $this->enterClosureLikeNode($node);

            return null;
        }

        if ($node instanceof Assign) {
            $this->localVariableTypeCollector->collectAssignment($node, $this->state);

            return null;
        }

        if ($node instanceof Return_) {
            $this->inferredStructuredReturnCollector->collect($node, $this->state);

            return null;
        }

        if ($node instanceof Property) {
            $this->memberDeclarationCollector->collectProperties($node, $this->state->currentClass());

            return null;
        }

        if ($node instanceof ClassConst) {
            $this->memberDeclarationCollector->collectClassConstants($node, $this->state->currentClass());

            return null;
        }

        if ($node instanceof EnumCase) {
            $this->memberDeclarationCollector->collectEnumCase($node, $this->state->currentClass());

            return null;
        }

        if ($node instanceof ClassConstFetch && $node->class instanceof Name && $node->name instanceof Identifier) {
            $this->collectClassConstantFetchUsage($node);

            return null;
        }

        if ($node instanceof PropertyFetch && $node->name instanceof Identifier) {
            $this->collectPropertyFetchUsage($node);

            return null;
        }

        if ($node instanceof StaticPropertyFetch && $node->class instanceof Name) {
            $this->collectStaticPropertyFetchUsage($node);

            return null;
        }

        if ($node instanceof Expression) {
            $this->localVariableTypeCollector->collectLocalVarPhpDoc($node, $this->state);
        }

        return null;
    }

    /**
     * Handles node exit.
     *
     * @param Node $node The current node.
     *
     * @return null
     */
    public function leaveNode(Node $node): mixed
    {
        if ($node instanceof ClassMethod) {
            $this->leaveClassMethodNode();

            return null;
        }
        if ($node instanceof Function_) {
            $this->leaveFunctionNode();

            return null;
        }

        if ($node instanceof Closure || $node instanceof ArrowFunction) {
            $this->state->popClosureVariableScope();

            return null;
        }

        if ($node instanceof Class_ || $node instanceof Trait_ || $node instanceof Interface_ || $node instanceof Enum_) {
            $this->leaveClassLikeNode();
        }

        // We do this in leaveNode so we can process things like $service->getBox()->get()->send();

        if ($node instanceof MethodCall && $node->name instanceof Identifier) {
            $this->collectMethodCallUsage($node);

            return null;
        }

        if ($node instanceof NullsafeMethodCall && $node->name instanceof Identifier) {
            $this->collectMethodCallUsage($node);

            return null;
        }

        if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
            $this->collectStaticCallUsage($node);

            return null;
        }

        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $this->collectFunctionCallUsage($node);

            return null;
        }

        return null;
    }

    /**
     * Enters a class-like node and collects its template definitions.
     *
     * @param Class_|Trait_|Interface_|Enum_ $node The class-like node.
     *
     * @return void
     */
    private function enterClassLikeNode(Class_|Trait_|Interface_|Enum_ $node): void
    {
        $this->state->enterClassLike($node->namespacedName->toString());
        $this->collectTemplateDefinitions($node);
    }

    /**
     * Enters a class method node and collects method-local declarations and parameter types.
     *
     * @param ClassMethod $node The class method node.
     *
     * @return void
     */
    private function enterClassMethodNode(ClassMethod $node): void
    {
        $this->state->enterMethod($node);
        $this->collectTemplateDefinitions($node);
        $this->localVariableTypeCollector->collectParameters(
            $node->params,
            $this->state->currentMethod(),
            $this->state,
        );
        $this->localVariableTypeCollector->collectParametersFromPhpDoc($node, $this->state);
        $this->memberDeclarationCollector->collectMethod($node, $this->state->currentClass());
        $this->memberDeclarationCollector->collectPromotedProperties($node, $this->state->currentClass());
    }

    /**
     * Enters a function node and collects function-local declarations and parameter types.
     *
     * @param Function_ $node The function node.
     *
     * @return void
     */
    private function enterFunctionNode(Function_ $node): void
    {
        $this->state->enterFunction($node, $node->namespacedName->toString());
        $this->collectTemplateDefinitions($node);
        $this->localVariableTypeCollector->collectParameters(
            $node->params,
            $this->state->currentFunction(),
            $this->state,
        );
        $this->localVariableTypeCollector->collectParametersFromPhpDoc($node, $this->state);
        $this->memberDeclarationCollector->collectFunction($node, $this->state->currentFunction());
    }

    /**
     * Enters a closure-like node and opens a local variable scope.
     *
     * @param Closure|ArrowFunction $node The closure-like node.
     *
     * @return void
     */
    private function enterClosureLikeNode(Closure|ArrowFunction $node): void
    {
        $this->state->pushClosureVariableScope();
        $this->localVariableTypeCollector->collectParameters($node->params, '', $this->state);
    }

    /**
     * Leaves the current class-like node.
     *
     * @return void
     */
    private function leaveClassLikeNode(): void
    {
        $this->state->leaveClassLike();
        $this->state->popTemplateDefinitions();
    }

    /**
     * Leaves the current class method node.
     *
     * @return void
     */
    private function leaveClassMethodNode(): void
    {
        $this->state->leaveMethod();
        $this->state->popTemplateDefinitions();
    }

    /**
     * Collects one class constant fetch usage.
     *
     * @param ClassConstFetch $node The class constant fetch node.
     *
     * @return void
     */
    private function collectClassConstantFetchUsage(ClassConstFetch $node): void
    {
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return;
        }

        $resolvedOwners = $this->expressionTypeResolver->resolve(
            $node,
            $this->state->variableTypes(),
            $this->state->currentClass(),
            $this->state->currentTemplateDefinitions(),
            $this->usesByAlias
        );

        if ($resolvedOwners->isEmpty()) {
            $resolvedOwners = $this->resolveStaticCallOwner($node->class);
        }

        $this->memberUsageCollector->collectClassConstantFetch(
            $this->state->sourceSymbol(),
            $resolvedOwners,
            $node->name->toString(),
            SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
        );
    }

    /**
     * Collects one property fetch usage.
     *
     * @param PropertyFetch $node The property fetch node.
     *
     * @return void
     */
    private function collectPropertyFetchUsage(PropertyFetch $node): void
    {
        if (!$node->name instanceof Identifier) {
            return;
        }

        $owners = $this->resolveExprTypes($node->var);

        $this->memberUsageCollector->collectPropertyFetch(
            $this->state->sourceSymbol(),
            $owners,
            $node->name->toString(),
            SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
        );
    }

    /**
     * Collects one static property fetch usage.
     *
     * @param StaticPropertyFetch $node The static property fetch node.
     *
     * @return void
     */
    private function collectStaticPropertyFetchUsage(StaticPropertyFetch $node): void
    {
        if (!$node->class instanceof Name) {
            return;
        }

        $name = $node->name instanceof VarLikeIdentifier ? $node->name->toString() : 'unknown';
        $resolvedOwners = $this->resolveStaticPropertyFetchOwners($node->class, $name);

        $this->memberUsageCollector->collectStaticPropertyFetch(
            $this->state->sourceSymbol(),
            $resolvedOwners,
            $name,
            SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
        );
    }

    /**
     * Collects one method or nullsafe method call usage.
     *
     * @param MethodCall|NullsafeMethodCall $node The method call node.
     *
     * @return void
     */
    private function collectMethodCallUsage(MethodCall|NullsafeMethodCall $node): void
    {
        if (!$node->name instanceof Identifier) {
            return;
        }

        $methodName = $node->name->toString();
        $owners = $this->resolveExprTypes($node->var);

        foreach ($owners as $owner) {
            $this->memberUsageCollector->collectMethodWithPolymorphism(
                sourceSymbol: $this->state->sourceSymbol(),
                owner: $owner,
                methodName: $methodName,
                usageType: MemberUsageType::METHOD_CALL,
                sourceNodeId: SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
            );

            $this->parameterUsageCollector->collectMethodLikeNamedArgumentsWithPolymorphism(
                sourceSymbol: $this->state->sourceSymbol(),
                owner: $owner,
                functionLikeName: $methodName,
                args: $node->args,
            );
        }
    }

    /**
     * Collects one static method call usage.
     *
     * @param StaticCall $node The static call node.
     *
     * @return void
     */
    private function collectStaticCallUsage(StaticCall $node): void
    {
        if (!$node->class instanceof Name || !$node->name instanceof Identifier) {
            return;
        }

        $owner = $this->resolveSingleStaticCallOwner($node->class);
        $methodName = $node->name->toString();

        $this->memberUsageCollector->collectMethodWithPolymorphism(
            sourceSymbol: $this->state->sourceSymbol(),
            owner: $owner,
            methodName: $methodName,
            usageType: MemberUsageType::STATIC_METHOD_CALL,
            sourceNodeId: SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
        );

        $this->parameterUsageCollector->collectMethodLikeNamedArgumentsWithPolymorphism(
            sourceSymbol: $this->state->sourceSymbol(),
            owner: $owner,
            functionLikeName: $methodName,
            args: $node->args,
        );
    }

    /**
     * Collects one function call usage.
     *
     * @param FuncCall $node The function call node.
     *
     * @return void
     */
    private function collectFunctionCallUsage(FuncCall $node): void
    {
        if (!$node->name instanceof Name) {
            return;
        }

        $functionName = $this->resolveFunctionName($node->name);

        $this->memberUsageCollector->collectFunctionCall(
            $this->state->sourceSymbol(),
            $functionName,
            SourceNodeId::fromNode($this->state->virtualFilePath(), $node),
        );

        $this->parameterUsageCollector->collectFunctionNamedArguments(
            $this->state->sourceSymbol(),
            $functionName,
            $node->args,
        );
    }

    /**
     * Leaves the current function node.
     *
     * @return void
     */
    private function leaveFunctionNode(): void
    {
        $this->state->leaveFunction();
        $this->state->popTemplateDefinitions();
    }

    /**
     * Collects template definitions declared by a class-like or function-like node.
     *
     * @param ClassMethod|Function_|Class_|Trait_|Interface_|Enum_ $node The node carrying template PHPDoc.
     *
     * @return void
     */
    private function collectTemplateDefinitions(
        ClassMethod|Function_|Class_|Trait_|Interface_|Enum_ $node
    ): void {
        $parent = $this->state->currentTemplateDefinitions();
        $current = $this->phpDocTemplateDefinitionExtractor->extract(
            $node,
            $this->state->currentNamespace(),
            $this->usesByAlias,
            $parent,
            $this->state->context(),
            PhpDocTagKind::TEMPLATE
        );

        $this->state->pushTemplateDefinitions($parent->merge($current));
    }

    /**
     * Resolves the best-known owner type for one expression.
     *
     * @param Node $node The expression node.
     *
     * @return SymbolCollection
     */
    private function resolveExprTypes(Node $node): SymbolCollection
    {
        $types = $this->expressionTypeResolver->resolve(
            expression: $node,
            variableTypes: $this->state->variableTypes(),
            currentClass: $this->state->currentClass(),
            templateDefinitions: $this->state->currentTemplateDefinitions(),
            usesByAlias: $this->usesByAlias,
        );

        if ($types->isEmpty()) {
            $types->add('unknown');
        }

        return $types;
    }

    /**
     * Resolves the effective owner of one static call.
     *
     * @param Name $className The static call class part.
     *
     * @return SymbolCollection
     */
    private function resolveStaticCallOwner(Name $className): SymbolCollection
    {
        $lowerName = $className->toLowerString();

        $owners = new SymbolCollection();

        if ('self' === $lowerName || 'static' === $lowerName) {
            return $owners->add($this->state->currentClass());
        }

        if ('parent' === $lowerName) {
            return $owners->add($this->context->knownOwners->get($this->state->currentClass())->parentFqcn ?? '');
        }

        $resolvedName = $className->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $owners->add($resolvedName->toString());
        }

        return $owners->add($className->toString());
    }

    /**
     * Resolves one static call owner, falling back to unknown when no owner can be resolved.
     *
     * @param Name $className The static call class part.
     *
     * @return string
     */
    private function resolveSingleStaticCallOwner(Name $className): string
    {
        $owners = $this->resolveStaticCallOwner($className);

        if (!$owners->isEmpty()) {
            return $owners->first();
        }

        return 'unknown';
    }

    /**
     * Resolves the declaring owner of one static property fetch.
     *
     * @param Name $className The static property class part.
     * @param string $propertyName The static property name without "$".
     *
     * @return SymbolCollection
     */
    private function resolveStaticPropertyFetchOwners(Name $className, string $propertyName): SymbolCollection
    {
        $owners = new SymbolCollection();

        foreach ($this->resolveStaticCallOwner($className) as $owner) {
            $current = $owner;
            $visited = [];

            while ('' !== $current && !isset($visited[$current])) {
                $visited[$current] = true;

                if (!$this->context->propertyTypeIndex->get($current, $propertyName)->isEmpty()) {
                    $owners->add($current);
                    break;
                }

                $knownOwner = $this->context->knownOwners->get($current);
                $current = $knownOwner->parentFqcn ?? '';
            }
        }

        if (!$owners->isEmpty()) {
            return $owners;
        }

        return $this->resolveStaticCallOwner($className);
    }

    /**
     * Resolves a function call name from PHPParser name attributes when available.
     *
     * @param Name $name The function call name node.
     *
     * @return string
     */
    private function resolveFunctionName(Name $name): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $resolvedName->toString();
        }

        $namespacedName = $name->getAttribute('namespacedName');

        if ($namespacedName instanceof Name) {
            return $namespacedName->toString();
        }

        if (is_string($namespacedName) && '' !== $namespacedName) {
            return $namespacedName;
        }

        return $name->toString();
    }
}
