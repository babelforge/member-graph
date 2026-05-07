<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Contracts;

use PhpParser\Node\Expr;

/**
 * Resolves array-shape keys from literal dimension expressions.
 */
interface LiteralArrayKeyResolverInterface
{
    /**
     * Resolves one literal array key.
     *
     * @param Expr|null $dimension The array dimension expression.
     * @param string $currentClass The current class-like owner.
     *
     * @return int|string|null
     */
    public function resolveLiteralArrayKeyForArrayShapeAccess(?Expr $dimension, string $currentClass): int|string|null;
}
