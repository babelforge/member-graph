<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Source\Node;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Application\Impact\MemberGraphImpact;
use PhpNoobs\MemberGraph\Application\Impact\MemberGraphImpactService;
use PhpNoobs\MemberGraph\Application\Impact\MemberImpactTarget;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterId;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\VarLikeIdentifier;

/**
 * Locates AST source nodes related to member graph impact targets.
 */
final readonly class MemberGraphSourceNodeLocator
{
    /**
     * Constructor.
     *
     * @param MemberGraphImpactService $impactService The impact service used to restrict source inspection.
     * @param bool $allowFallbackMatching Whether name-based fallback matching is allowed when graph facts do not carry source-node identifiers. Use this only for legacy graphs or focused tests built manually without `SourceNodeId`; production source lookup should keep it disabled so returned nodes are exact.
     */
    public function __construct(
        private MemberGraphImpactService $impactService,
        private bool $allowFallbackMatching = false,
    ) {
    }

    /**
     * Creates a source node locator from a factory build result.
     *
     * @param MemberDependencyGraphBuild $build The member dependency graph build result.
     * @param bool $allowFallbackMatching Whether name-based fallback matching is allowed when graph facts do not carry source-node identifiers. Use this only for legacy graphs or focused tests built manually without `SourceNodeId`; factory builds should normally keep it disabled so returned nodes are exact.
     *
     * @return self
     */
    public static function fromBuild(MemberDependencyGraphBuild $build, bool $allowFallbackMatching = false): self
    {
        return new self(MemberGraphImpactService::fromBuild($build), $allowFallbackMatching);
    }

    /**
     * Creates a source node locator from a graph and its virtual files.
     *
     * @param MemberDependencyGraph $graph The member dependency graph.
     * @param VirtualPhpSourceFileCollection $virtualFiles The virtual files to inspect.
     * @param bool $allowFallbackMatching Whether name-based fallback matching is allowed when graph facts do not carry source-node identifiers. Use this only for legacy graphs or focused tests built manually without `SourceNodeId`; production source lookup should keep it disabled so returned nodes are exact.
     *
     * @return self
     */
    public static function fromGraphAndVirtualFiles(
        MemberDependencyGraph          $graph,
        VirtualPhpSourceFileCollection $virtualFiles,
        bool                           $allowFallbackMatching = false,
    ): self {
        return new self(MemberGraphImpactService::fromGraphAndVirtualFiles($graph, $virtualFiles), $allowFallbackMatching);
    }

    /**
     * Locates source nodes for one method target.
     *
     * @param string $owner The method owner FQCN.
     * @param string $name The method name.
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function method(string $owner, string $name): VirtualPhpSourceFileNodeMatchCollection
    {
        return $this->target(MemberImpactTarget::method($owner, $name));
    }

    /**
     * Locates source nodes for one property target.
     *
     * @param string $owner The property owner FQCN.
     * @param string $name The property name.
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function property(string $owner, string $name): VirtualPhpSourceFileNodeMatchCollection
    {
        return $this->target(MemberImpactTarget::property($owner, $name));
    }

    /**
     * Locates source nodes for one class-constant target.
     *
     * @param string $owner The class-constant owner FQCN.
     * @param string $name The class-constant name.
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function classConstant(string $owner, string $name): VirtualPhpSourceFileNodeMatchCollection
    {
        return $this->target(MemberImpactTarget::classConstant($owner, $name));
    }

    /**
     * Locates source nodes for one function target.
     *
     * @param string $name The fully-qualified function name.
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function function(string $name): VirtualPhpSourceFileNodeMatchCollection
    {
        return $this->target(MemberImpactTarget::forFunction($name));
    }

    /**
     * Locates source nodes for one parameter target.
     *
     * @param string $owner The owner FQCN, or an empty string for functions.
     * @param string $functionLikeName The method name or fully-qualified function name.
     * @param string $parameterName The parameter name without "$".
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function parameter(
        string $owner,
        string $functionLikeName,
        string $parameterName,
    ): VirtualPhpSourceFileNodeMatchCollection {
        return $this->target(MemberImpactTarget::parameter($owner, $functionLikeName, $parameterName));
    }

    /**
     * Locates source nodes for one impact target.
     *
     * @param MemberImpactTarget $target The impact target.
     *
     * @return VirtualPhpSourceFileNodeMatchCollection
     */
    public function target(MemberImpactTarget $target): VirtualPhpSourceFileNodeMatchCollection
    {
        $impact = $this->impactService->target($target);
        $matches = new VirtualPhpSourceFileNodeMatchCollection();

        foreach ($this->virtualFilesForImpact($target, $impact) as $virtualFile) {
            foreach ($virtualFile->getAst() as $node) {
                $this->inspectNode($node, $virtualFile, $target, $impact, $matches);
            }
        }

        return $matches;
    }

    /**
     * Returns virtual files that should be inspected for one target impact.
     *
     * @param MemberImpactTarget $target The impact target.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     *
     * @return VirtualPhpSourceFileCollection
     */
    private function virtualFilesForImpact(
        MemberImpactTarget $target,
        MemberGraphImpact $impact,
    ): VirtualPhpSourceFileCollection {
        $virtualFiles = new VirtualPhpSourceFileCollection();

        foreach ($impact->virtualFiles as $virtualFile) {
            $this->addVirtualFileOnce($virtualFiles, $virtualFile);
        }

        if (null === $target->parameterId) {
            return $virtualFiles;
        }

        foreach ($this->functionLikeDeclarationVirtualFiles($target->parameterId) as $virtualFile) {
            $this->addVirtualFileOnce($virtualFiles, $virtualFile);
        }

        return $virtualFiles;
    }

    /**
     * Returns declaration virtual files for a parameter target's function-like owner.
     *
     * @param ParameterId $parameterId The parameter identifier.
     *
     * @return VirtualPhpSourceFileCollection
     */
    private function functionLikeDeclarationVirtualFiles(ParameterId $parameterId): VirtualPhpSourceFileCollection
    {
        if ('' === $parameterId->owner) {
            return $this->impactService->function($parameterId->functionLikeName)->virtualFiles;
        }

        return $this->impactService->method($parameterId->owner, $parameterId->functionLikeName)->virtualFiles;
    }

    /**
     * Adds one virtual file if the collection does not already contain it.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles The collection to update.
     * @param VirtualPhpSourceFile $virtualFile The virtual file to add.
     *
     * @return void
     */
    private function addVirtualFileOnce(
        VirtualPhpSourceFileCollection $virtualFiles,
        VirtualPhpSourceFile           $virtualFile,
    ): void {
        if (!$virtualFiles->has($virtualFile->virtualFilePath)) {
            $virtualFiles->add($virtualFile);
        }
    }

    /**
     * Inspects one node and its descendants.
     *
     * @param Node $node The node to inspect.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The target to locate.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     * @param string $currentOwner The current class-like owner FQCN.
     * @param string $currentFunctionLike The current function-like name.
     *
     * @return void
     */
    private function inspectNode(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        MemberGraphImpact                       $impact,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string                                  $currentOwner = '',
        string                                  $currentFunctionLike = '',
    ): void {
        $nextOwner = $this->nextOwner($node, $currentOwner);
        $nextFunctionLike = $this->nextFunctionLike($node, $nextOwner, $currentFunctionLike);

        $this->matchNode($node, $virtualFile, $target, $impact, $matches, $nextOwner, $nextFunctionLike);

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                $this->inspectNode($subNode, $virtualFile, $target, $impact, $matches, $nextOwner, $nextFunctionLike);
                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $subNodeItem) {
                if ($subNodeItem instanceof Node) {
                    $this->inspectNode($subNodeItem, $virtualFile, $target, $impact, $matches, $nextOwner, $nextFunctionLike);
                }
            }
        }
    }

    /**
     * Returns the current owner after entering a node.
     *
     * @param Node $node The node being entered.
     * @param string $currentOwner The current owner before entering the node.
     *
     * @return string
     */
    private function nextOwner(Node $node, string $currentOwner): string
    {
        if ($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_ || $node instanceof Enum_) {
            $namespacedName = $node->namespacedName ?? null;

            if ($namespacedName instanceof Name) {
                return $namespacedName->toString();
            }
        }

        return $currentOwner;
    }

    /**
     * Returns the current function-like name after entering a node.
     *
     * @param Node $node The node being entered.
     * @param string $currentOwner The current owner after entering the node.
     * @param string $currentFunctionLike The current function-like name before entering the node.
     *
     * @return string
     */
    private function nextFunctionLike(Node $node, string $currentOwner, string $currentFunctionLike): string
    {
        if ($node instanceof ClassMethod) {
            return $node->name->toString();
        }

        if ($node instanceof Function_) {
            return $this->resolvedFunctionName($node, $currentOwner);
        }

        return $currentFunctionLike;
    }

    /**
     * Matches one AST node against the target.
     *
     * @param Node $node The node to match.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The target to locate.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     * @param string $currentOwner The current class-like owner FQCN.
     * @param string $currentFunctionLike The current function-like name.
     *
     * @return void
     */
    private function matchNode(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        MemberGraphImpact                       $impact,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string                                  $currentOwner,
        string                                  $currentFunctionLike,
    ): void {
        if ($this->matchSourceNodeId($node, $virtualFile, $target, $impact, $matches)) {
            return;
        }

        if (null !== $target->parameterId && $this->matchParameterDeclarationNode(
            $node,
            $virtualFile,
            $target,
            $target->parameterId,
            $matches,
            $currentOwner,
            $currentFunctionLike,
        )) {
            return;
        }

        if (!$this->allowFallbackMatching) {
            return;
        }

        if (null !== $target->memberId) {
            $this->matchMemberNode($node, $virtualFile, $target, $impact, $target->memberId, $matches, $currentOwner);
        }

        if (null !== $target->parameterId) {
            $this->matchParameterUsageNode($node, $virtualFile, $target, $impact, $target->parameterId, $matches);
        }
    }

    /**
     * Matches one node by source-node identifier when graph facts carry one.
     *
     * @param Node $node The node to match.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The original impact target.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     *
     * @return bool
     */
    private function matchSourceNodeId(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        MemberGraphImpact                       $impact,
        VirtualPhpSourceFileNodeMatchCollection $matches,
    ): bool {
        $sourceNodeId = SourceNodeId::fromNode($virtualFile->virtualFilePath, $node);

        if (null === $sourceNodeId) {
            return false;
        }

        if ($this->matchesDeclarationSourceNodeId($sourceNodeId, $impact)) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION,
            ));

            return true;
        }

        if ($this->matchesMemberUsageSourceNodeId($sourceNodeId, $impact)) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE,
            ));

            return true;
        }

        if ($this->matchesParameterUsageSourceNodeId($sourceNodeId, $impact)) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE,
            ));

            return true;
        }

        return false;
    }

    /**
     * Indicates whether one source node id matches an impacted declaration.
     *
     * @param SourceNodeId $sourceNodeId The source node identifier.
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function matchesDeclarationSourceNodeId(SourceNodeId $sourceNodeId, MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->declarations->all() as $declaration) {
            if ($declaration instanceof MemberDeclaration && $declaration->sourceNodeId?->equals($sourceNodeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether one source node id matches an impacted member usage.
     *
     * @param SourceNodeId $sourceNodeId The source node identifier.
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function matchesMemberUsageSourceNodeId(SourceNodeId $sourceNodeId, MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->memberUsages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($usage->sourceNodeId?->equals($sourceNodeId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Indicates whether one source node id matches an impacted parameter usage.
     *
     * @param SourceNodeId $sourceNodeId The source node identifier.
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function matchesParameterUsageSourceNodeId(SourceNodeId $sourceNodeId, MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->parameterUsages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($usage->sourceNodeId?->equals($sourceNodeId)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Matches one node against a member target.
     *
     * @param Node $node The node to match.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The original impact target.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     * @param MemberId $memberId The member identifier.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     * @param string $currentOwner The current class-like owner FQCN.
     *
     * @return void
     */
    private function matchMemberNode(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        MemberGraphImpact                       $impact,
        MemberId                                $memberId,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string                                  $currentOwner,
    ): void {
        if (
            !$this->hasDeclarationSourceNodeIds($impact)
            && $this->isMemberDeclarationNode($node, $memberId, $currentOwner)
        ) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::MEMBER_DECLARATION,
            ));
        }

        if (!$this->hasMemberUsageSourceNodeIds($impact) && $this->isMemberUsageNode($node, $memberId)) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::MEMBER_USAGE,
            ));
        }
    }

    /**
     * Matches one parameter declaration node against a parameter target.
     *
     * @param Node $node The node to match.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The original impact target.
     * @param ParameterId $parameterId The parameter identifier.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     * @param string $currentOwner The current class-like owner FQCN.
     * @param string $currentFunctionLike The current function-like name.
     *
     * @return bool
     */
    private function matchParameterDeclarationNode(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        ParameterId                             $parameterId,
        VirtualPhpSourceFileNodeMatchCollection $matches,
        string                                  $currentOwner,
        string                                  $currentFunctionLike,
    ): bool {
        if (!$node instanceof Param || !$this->isParameterDeclaration($node, $parameterId, $currentOwner, $currentFunctionLike)) {
            return false;
        }

        $matches->add(new VirtualPhpSourceFileNodeMatch(
            virtualFile: $virtualFile,
            node: $node,
            target: $target,
            role: VirtualPhpSourceFileNodeMatchRole::PARAMETER_DECLARATION,
        ));

        return true;
    }

    /**
     * Matches one parameter usage node against a parameter target through fallback matching.
     *
     * @param Node $node The node to match.
     * @param VirtualPhpSourceFile $virtualFile The virtual file containing the node.
     * @param MemberImpactTarget $target The original impact target.
     * @param MemberGraphImpact $impact The precomputed graph impact.
     * @param ParameterId $parameterId The parameter identifier.
     * @param VirtualPhpSourceFileNodeMatchCollection $matches The output match collection.
     *
     * @return void
     */
    private function matchParameterUsageNode(
        Node                                    $node,
        VirtualPhpSourceFile                    $virtualFile,
        MemberImpactTarget                      $target,
        MemberGraphImpact                       $impact,
        ParameterId                             $parameterId,
        VirtualPhpSourceFileNodeMatchCollection $matches,
    ): void {
        if (
            !$this->hasParameterUsageSourceNodeIds($impact)
            && $node instanceof Arg
            && $node->name instanceof Identifier
            && $node->name->toString() === $parameterId->parameterName
        ) {
            $matches->add(new VirtualPhpSourceFileNodeMatch(
                virtualFile: $virtualFile,
                node: $node,
                target: $target,
                role: VirtualPhpSourceFileNodeMatchRole::PARAMETER_USAGE,
            ));
        }
    }

    /**
     * Indicates whether impacted declarations carry source-node identifiers.
     *
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function hasDeclarationSourceNodeIds(MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->declarations->all() as $declaration) {
            if ($declaration instanceof MemberDeclaration && null !== $declaration->sourceNodeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether impacted member usages carry source-node identifiers.
     *
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function hasMemberUsageSourceNodeIds(MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->memberUsages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if (null !== $usage->sourceNodeId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Indicates whether impacted parameter usages carry source-node identifiers.
     *
     * @param MemberGraphImpact $impact The graph impact.
     *
     * @return bool
     */
    private function hasParameterUsageSourceNodeIds(MemberGraphImpact $impact): bool
    {
        foreach ($impact->memberImpact->parameterUsages as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if (null !== $usage->sourceNodeId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Indicates whether a node declares the target member.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The member identifier.
     * @param string $currentOwner The current class-like owner FQCN.
     *
     * @return bool
     */
    private function isMemberDeclarationNode(Node $node, MemberId $memberId, string $currentOwner): bool
    {
        if (MemberType::METHOD === $memberId->type) {
            return $node instanceof ClassMethod
                && $memberId->owner === $currentOwner
                && $node->name->toString() === $memberId->name;
        }

        if (MemberType::PROPERTY === $memberId->type) {
            return $memberId->owner === $currentOwner
                && (
                    $this->isPropertyPropertyDeclaration($node, $memberId)
                    || $this->isPromotedPropertyDeclaration($node, $memberId)
                );
        }

        if (MemberType::CLASS_CONSTANT === $memberId->type) {
            return $memberId->owner === $currentOwner
                && (
                    $this->isClassConstantDeclaration($node, $memberId)
                    || $this->isEnumCaseDeclaration($node, $memberId)
                );
        }

        if (MemberType::FUNCTION_ === $memberId->type) {
            return $node instanceof Function_
                && $this->functionNameMatches($this->resolvedFunctionName($node, $currentOwner), $memberId->name);
        }

        return false;
    }

    /**
     * Indicates whether a node is a property declaration for the target.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The property member identifier.
     *
     * @return bool
     */
    private function isPropertyPropertyDeclaration(Node $node, MemberId $memberId): bool
    {
        return $node instanceof PropertyProperty
            && $node->name->toString() === $memberId->name
            && $node->getAttribute('parent') instanceof Property;
    }

    /**
     * Indicates whether a node is a class-constant declaration for the target.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The class-constant member identifier.
     *
     * @return bool
     */
    private function isClassConstantDeclaration(Node $node, MemberId $memberId): bool
    {
        return $node instanceof Const_
            && $node->name->toString() === $memberId->name
            && $node->getAttribute('parent') instanceof ClassConst;
    }

    /**
     * Indicates whether a node is an enum-case declaration for the target.
     *
     * Enum cases are represented as CLASS_CONSTANT members in the graph, but PHPParser exposes declarations as
     * EnumCase nodes rather than Const_ nodes.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The class-constant member identifier.
     *
     * @return bool
     */
    private function isEnumCaseDeclaration(Node $node, MemberId $memberId): bool
    {
        return $node instanceof EnumCase
            && $node->name->toString() === $memberId->name;
    }

    /**
     * Indicates whether a node is a promoted property declaration for the target.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The property member identifier.
     *
     * @return bool
     */
    private function isPromotedPropertyDeclaration(Node $node, MemberId $memberId): bool
    {
        return $node instanceof Param
            && 0 !== $node->flags
            && $node->var instanceof Variable
            && is_string($node->var->name)
            && $node->var->name === $memberId->name;
    }

    /**
     * Indicates whether a node uses the target member.
     *
     * @param Node $node The node to inspect.
     * @param MemberId $memberId The member identifier.
     *
     * @return bool
     */
    private function isMemberUsageNode(Node $node, MemberId $memberId): bool
    {
        if (MemberType::METHOD === $memberId->type) {
            return $this->isMethodUsageNode($node, $memberId->name);
        }

        if (MemberType::PROPERTY === $memberId->type) {
            return $this->isPropertyUsageNode($node, $memberId->name);
        }

        if (MemberType::CLASS_CONSTANT === $memberId->type) {
            return $node instanceof ClassConstFetch
                && $node->name instanceof Identifier
                && $node->name->toString() === $memberId->name;
        }

        if (MemberType::FUNCTION_ === $memberId->type) {
            return $node instanceof FuncCall
                && $node->name instanceof Name
                && $this->functionNameMatches($this->resolvedName($node->name), $memberId->name);
        }

        return false;
    }

    /**
     * Indicates whether a node uses a method with the given name.
     *
     * @param Node $node The node to inspect.
     * @param string $methodName The target method name.
     *
     * @return bool
     */
    private function isMethodUsageNode(Node $node, string $methodName): bool
    {
        return (
            ($node instanceof MethodCall || $node instanceof NullsafeMethodCall || $node instanceof StaticCall)
            && $node->name instanceof Identifier
            && $node->name->toString() === $methodName
        );
    }

    /**
     * Indicates whether a node uses a property with the given name.
     *
     * @param Node $node The node to inspect.
     * @param string $propertyName The target property name.
     *
     * @return bool
     */
    private function isPropertyUsageNode(Node $node, string $propertyName): bool
    {
        if ($node instanceof PropertyFetch && $node->name instanceof Identifier) {
            return $node->name->toString() === $propertyName;
        }

        return $node instanceof StaticPropertyFetch
            && $node->name instanceof VarLikeIdentifier
            && $node->name->toString() === $propertyName;
    }

    /**
     * Indicates whether a node declares the target parameter.
     *
     * @param Param $node The parameter node to inspect.
     * @param ParameterId $parameterId The parameter identifier.
     * @param string $currentOwner The current class-like owner FQCN.
     * @param string $currentFunctionLike The current function-like name.
     *
     * @return bool
     */
    private function isParameterDeclaration(
        Param $node,
        ParameterId $parameterId,
        string $currentOwner,
        string $currentFunctionLike,
    ): bool {
        return $parameterId->owner === $currentOwner
            && $parameterId->functionLikeName === $currentFunctionLike
            && $node->var instanceof Variable
            && is_string($node->var->name)
            && $node->var->name === $parameterId->parameterName;
    }

    /**
     * Resolves a function declaration name.
     *
     * @param Function_ $function The function node.
     * @param string $currentOwner The current class-like owner FQCN.
     *
     * @return string
     */
    private function resolvedFunctionName(Function_ $function, string $currentOwner): string
    {
        $namespacedName = $function->namespacedName ?? null;

        if ($namespacedName instanceof Name) {
            return $namespacedName->toString();
        }

        if ('' !== $currentOwner) {
            return $function->name->toString();
        }

        return $function->name->toString();
    }

    /**
     * Resolves a name node with NameResolver attributes when available.
     *
     * @param Name $name The name node.
     *
     * @return string
     */
    private function resolvedName(Name $name): string
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

    /**
     * Indicates whether two function names designate the same target.
     *
     * @param string $actualName The actual function name.
     * @param string $targetName The target function name.
     *
     * @return bool
     */
    private function functionNameMatches(string $actualName, string $targetName): bool
    {
        return ltrim($actualName, '\\') === ltrim($targetName, '\\');
    }
}
