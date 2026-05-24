<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Source\Node;

use BabelForge\MemberGraph\Domain\Parameter\ParameterId;
use PhpParser\Node;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;

/**
 * Locates local variable nodes that read or write a targeted function-like parameter.
 *
 * This locator is intentionally query-time only: local parameter usages are useful for source refactoring, but they are
 * too numerous and too local to be persisted in the member dependency graph cache.
 */
final readonly class ParameterLocalUsageNodeLocator
{
    /**
     * Locates local usages of one parameter inside its declaring function-like body.
     *
     * @param ClassMethod|Function_ $functionLike the function-like declaration to inspect
     * @param ParameterId           $parameterId  the targeted parameter identifier
     *
     * @return list<Variable>
     */
    public function locate(ClassMethod|Function_ $functionLike, ParameterId $parameterId): array
    {
        $usages = [];

        foreach ($functionLike->stmts ?? [] as $statement) {
            $this->collectFromNode($statement, $parameterId, $usages);
        }

        return $usages;
    }

    /**
     * Collects local usages recursively from one node.
     *
     * @param Node           $node        the node to inspect
     * @param ParameterId    $parameterId the targeted parameter identifier
     * @param list<Variable> $usages      the collected local usage nodes
     */
    private function collectFromNode(Node $node, ParameterId $parameterId, array &$usages): void
    {
        if ($node instanceof ClassMethod || $node instanceof Function_ || $node instanceof Class_) {
            return;
        }

        if ($node instanceof Closure) {
            $this->collectFromClosure($node, $parameterId, $usages);

            return;
        }

        if ($node instanceof ArrowFunction) {
            $this->collectFromArrowFunction($node, $parameterId, $usages);

            return;
        }

        if ($node instanceof Variable && $this->isTargetVariable($node, $parameterId)) {
            $usages[] = $node;
        }

        $this->collectFromChildren($node, $parameterId, $usages);
    }

    /**
     * Collects local usages from a closure when it explicitly captures the target parameter.
     *
     * @param Closure        $closure     the closure node to inspect
     * @param ParameterId    $parameterId the targeted parameter identifier
     * @param list<Variable> $usages      the collected local usage nodes
     */
    private function collectFromClosure(Closure $closure, ParameterId $parameterId, array &$usages): void
    {
        if ($this->hasParameterNamed(array_values($closure->params), $parameterId->parameterName)) {
            return;
        }

        if (!$this->collectClosureUse(array_values($closure->uses), $parameterId, $usages)) {
            return;
        }

        foreach ($closure->stmts as $statement) {
            $this->collectFromNode($statement, $parameterId, $usages);
        }
    }

    /**
     * Collects local usages from an arrow function when the target parameter is not shadowed.
     *
     * @param ArrowFunction  $arrowFunction the arrow function node to inspect
     * @param ParameterId    $parameterId   the targeted parameter identifier
     * @param list<Variable> $usages        the collected local usage nodes
     */
    private function collectFromArrowFunction(ArrowFunction $arrowFunction, ParameterId $parameterId, array &$usages): void
    {
        if ($this->hasParameterNamed(array_values($arrowFunction->params), $parameterId->parameterName)) {
            return;
        }

        $this->collectFromNode($arrowFunction->expr, $parameterId, $usages);
    }

    /**
     * Collects a closure-use variable when it captures the target parameter.
     *
     * @param list<ClosureUse> $closureUses the closure-use nodes
     * @param ParameterId      $parameterId the targeted parameter identifier
     * @param list<Variable>   $usages      the collected local usage nodes
     */
    private function collectClosureUse(array $closureUses, ParameterId $parameterId, array &$usages): bool
    {
        foreach ($closureUses as $closureUse) {
            if ($this->isTargetVariable($closureUse->var, $parameterId)) {
                $usages[] = $closureUse->var;

                return true;
            }
        }

        return false;
    }

    /**
     * Collects local usages recursively from child nodes.
     *
     * @param Node           $node        the parent node to inspect
     * @param ParameterId    $parameterId the targeted parameter identifier
     * @param list<Variable> $usages      the collected local usage nodes
     */
    private function collectFromChildren(Node $node, ParameterId $parameterId, array &$usages): void
    {
        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->{$subNodeName};

            if ($subNode instanceof Node) {
                $this->collectFromNode($subNode, $parameterId, $usages);

                continue;
            }

            if (!is_array($subNode)) {
                continue;
            }

            foreach ($subNode as $subNodeItem) {
                if ($subNodeItem instanceof Node) {
                    $this->collectFromNode($subNodeItem, $parameterId, $usages);
                }
            }
        }
    }

    /**
     * Indicates whether one function-like parameter list shadows the targeted parameter.
     *
     * @param list<Param> $parameters    the parameter nodes to inspect
     * @param string      $parameterName the targeted parameter name without "$"
     */
    private function hasParameterNamed(array $parameters, string $parameterName): bool
    {
        foreach ($parameters as $parameter) {
            if ($this->isParameterNamed($parameter, $parameterName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Indicates whether one parameter node declares the given name.
     *
     * @param Param  $parameter     the parameter node to inspect
     * @param string $parameterName the targeted parameter name without "$"
     */
    private function isParameterNamed(Param $parameter, string $parameterName): bool
    {
        return $parameter->var instanceof Variable
            && is_string($parameter->var->name)
            && $parameter->var->name === $parameterName;
    }

    /**
     * Indicates whether one node is the targeted variable.
     *
     * @param Variable    $node        the variable node to inspect
     * @param ParameterId $parameterId the targeted parameter identifier
     */
    private function isTargetVariable(Variable $node, ParameterId $parameterId): bool
    {
        return is_string($node->name)
            && $node->name === $parameterId->parameterName;
    }
}
