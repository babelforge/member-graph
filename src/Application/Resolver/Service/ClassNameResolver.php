<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node\Name;

/**
 * Resolves PHP parser name nodes into fully-qualified class names.
 */
final readonly class ClassNameResolver
{
    /**
     * Constructor.
     *
     * @param StaticOwnerResolver $staticOwnerResolver The resolver used for special class references.
     */
    public function __construct(
        private StaticOwnerResolver $staticOwnerResolver,
    ) {
    }

    /**
     * Resolves one name node to a fully-qualified class name.
     *
     * @param Name $name The class name node.
     * @param string $currentClass The current class-like owner FQCN.
     * @param UsesByAliasCollection $usesByAlias The current file imports indexed by alias.
     *
     * @return string
     */
    public function resolve(Name $name, string $currentClass, UsesByAliasCollection $usesByAlias): string
    {
        $resolvedName = $name->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $resolvedName->toString();
        }

        $raw = $name->toString();

        if ($name->isSpecialClassName()) {
            return match (strtolower($raw)) {
                'self', 'static' => $currentClass,
                'parent' => $this->staticOwnerResolver->resolveParentClassName($currentClass),
                default => '',
            };
        }

        if ($name->isFullyQualified()) {
            return ltrim($raw, '\\');
        }

        $firstPart = $name->getFirst();

        if ($usesByAlias->has($firstPart)) {
            $fqcn = $usesByAlias->get($firstPart);

            if (null === $fqcn) {
                return $raw;
            }

            $remainingParts = $name->getParts();
            array_shift($remainingParts);

            if ([] === $remainingParts) {
                return $fqcn;
            }

            return $fqcn . '\\' . implode('\\', $remainingParts);
        }

        $namespace = $this->extractNamespaceFromClass($currentClass);

        if ('' !== $namespace) {
            return $namespace . '\\' . $raw;
        }

        return $raw;
    }

    /**
     * Extracts the namespace part from one fully-qualified class name.
     *
     * @param string $fqcn The fully-qualified class name.
     *
     * @return string
     */
    private function extractNamespaceFromClass(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        if (false === $pos) {
            return '';
        }

        return substr($fqcn, 0, $pos);
    }
}
