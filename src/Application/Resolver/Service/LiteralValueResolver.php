<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\LiteralArrayKeyResolverInterface;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;

/**
 * Resolves simple literal values used by expression-resolution services.
 */
final readonly class LiteralValueResolver implements LiteralArrayKeyResolverInterface
{
    /**
     * Constructor.
     *
     * @param StaticOwnerResolver $staticOwnerResolver The static owner resolver.
     * @param ClassConstantOwnerResolver $classConstantOwnerResolver The class constant owner resolver.
     * @param ClassConstantValueIndex $classConstantValueIndex The scalar class constant value index.
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
        private ClassConstantOwnerResolver $classConstantOwnerResolver,
        private ClassConstantValueIndex $classConstantValueIndex,
    ) {
    }

    /**
     * Resolves one literal array key for structured array-shape access.
     *
     * @param Expr|null $dimension The dimension expression.
     * @param string $currentClass The current class-like owner.
     *
     * @return int|string|null
     */
    public function resolveLiteralArrayKeyForArrayShapeAccess(?Expr $dimension, string $currentClass): int|string|null
    {
        if ($dimension instanceof String_) {
            return $dimension->value;
        }

        if ($dimension instanceof Int_) {
            return $dimension->value;
        }

        if (
            $dimension instanceof ClassConstFetch
            && $dimension->class instanceof Name
            && $dimension->name instanceof Identifier
        ) {
            return $this->resolveClassConstantScalarValue(
                $dimension->class,
                $dimension->name->toString(),
                $currentClass,
            );
        }

        return null;
    }

    /**
     * Resolves a simple scalar class constant value.
     *
     * @param Name $class The class name node.
     * @param string $constantName The class constant name.
     * @param string $currentClass The current class-like owner.
     *
     * @return int|string|null
     */
    private function resolveClassConstantScalarValue(Name $class, string $constantName, string $currentClass): int|string|null
    {
        $owners = $this->staticOwnerResolver->resolve($class, $currentClass);

        foreach ($owners as $owner) {
            $declaringOwners = $this->classConstantOwnerResolver->resolve($owner, $constantName);
            $declaringOwner = $declaringOwners->first() ?? $owner;
            $value = $this->classConstantValueIndex->get($declaringOwner, $constantName);

            if (null !== $value) {
                return $value;
            }
        }

        return null;
    }
}
