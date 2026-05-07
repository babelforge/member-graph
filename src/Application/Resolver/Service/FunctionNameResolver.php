<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpParser\Node\Name;

/**
 * Resolves function names from parser name-resolution attributes.
 */
final readonly class FunctionNameResolver
{
    /**
     * Resolves one function name using NameResolver attributes when available.
     *
     * @param Name $name The function name node.
     *
     * @return string
     */
    public function resolve(Name $name): string
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
