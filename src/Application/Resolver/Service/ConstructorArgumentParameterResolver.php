<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Resolves constructor parameter names targeted by positional or named arguments.
 */
final readonly class ConstructorArgumentParameterResolver
{
    /**
     * Resolves the target parameter name for one constructor argument.
     *
     * @param Arg $arg The argument node.
     * @param int $position The positional argument index.
     * @param ClassMethod $constructorNode The constructor node.
     *
     * @return string|null
     */
    public function resolve(Arg $arg, int $position, ClassMethod $constructorNode): ?string
    {
        if (null !== $arg->name) {
            return $arg->name->toString();
        }

        $parameter = $constructorNode->params[$position] ?? null;

        if (null === $parameter || !$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
            return null;
        }

        return $parameter->var->name;
    }
}
