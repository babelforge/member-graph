<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\VariadicPlaceholder;

/**
 * Resolves function-like call parameter names and structured parameter types.
 */
final readonly class FunctionLikeParameterResolver
{
    /**
     * Constructor.
     *
     * @param MethodParameterStructuredTypeIndex   $methodStructuredParameterTypeIndex   the method structured parameter type index
     * @param FunctionParameterStructuredTypeIndex $functionStructuredParameterTypeIndex the function structured parameter type index
     * @param DeclaringMethodResolver              $declaringMethodResolver              the declaring method resolver
     */
    public function __construct(
        private MethodParameterStructuredTypeIndex $methodStructuredParameterTypeIndex,
        private FunctionParameterStructuredTypeIndex $functionStructuredParameterTypeIndex,
        private DeclaringMethodResolver $declaringMethodResolver,
    ) {
    }

    /**
     * Resolves the target parameter name for one function-like call argument.
     *
     * @param Arg|VariadicPlaceholder $arg          the call argument
     * @param int                     $position     the zero-based argument position
     * @param Node                    $parentNode   the declaring function-like node
     * @param bool                    $isMethodLike whether the target is method-like
     */
    public function resolveCallParameterName(
        Arg|VariadicPlaceholder $arg,
        int $position,
        Node $parentNode,
        bool $isMethodLike,
    ): ?string {
        if ($isMethodLike) {
            if (!$parentNode instanceof ClassMethod) {
                return null;
            }

            return $this->resolveMethodCallParameterName($arg, $position, $parentNode);
        }

        if (!$parentNode instanceof Function_) {
            return null;
        }

        return $this->resolveFunctionCallParameterName($arg, $position, $parentNode);
    }

    /**
     * Resolves the structured PHPDoc type declared for one function-like parameter.
     *
     * @param string|null $owner         the owner FQCN for methods, or null for functions
     * @param string      $methodName    the method or function name
     * @param string      $parameterName the parameter name
     * @param bool        $isMethodLike  whether the target is method-like
     */
    public function resolveStructuredParameterType(
        ?string $owner,
        string $methodName,
        string $parameterName,
        bool $isMethodLike,
    ): ?ResolvedPhpDocType {
        if ($isMethodLike) {
            if (null === $owner || '' === $owner) {
                return null;
            }

            $declaringOwner = $this->declaringMethodResolver->resolve($owner, $methodName) ?? $owner;

            return $this->methodStructuredParameterTypeIndex->get($declaringOwner, $methodName, $parameterName);
        }

        return $this->functionStructuredParameterTypeIndex->get($methodName, $parameterName);
    }

    /**
     * Resolves the target parameter name for one method call argument.
     *
     * @param Arg|VariadicPlaceholder $arg        the call argument
     * @param int                     $position   the zero-based argument position
     * @param ClassMethod             $methodNode the declaring method node
     */
    private function resolveMethodCallParameterName(
        Arg|VariadicPlaceholder $arg,
        int $position,
        ClassMethod $methodNode,
    ): ?string {
        if (!$arg instanceof Arg) {
            return null;
        }

        if (null !== $arg->name) {
            return $arg->name->toString();
        }

        $parameter = $methodNode->params[$position] ?? null;

        if (null === $parameter || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }

    /**
     * Resolves the target parameter name for one function call argument.
     *
     * @param Arg|VariadicPlaceholder $arg          the call argument
     * @param int                     $position     the zero-based argument position
     * @param Function_               $functionNode the declaring function node
     */
    private function resolveFunctionCallParameterName(
        Arg|VariadicPlaceholder $arg,
        int $position,
        Function_ $functionNode,
    ): ?string {
        if (!$arg instanceof Arg) {
            return null;
        }

        if (null !== $arg->name) {
            return $arg->name->toString();
        }

        $parameter = $functionNode->params[$position] ?? null;

        if (null === $parameter || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }
}
