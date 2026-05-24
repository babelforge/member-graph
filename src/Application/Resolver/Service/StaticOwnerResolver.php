<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpParser\Node\Name;

/**
 * Resolves static-access owner names such as self, static, parent, or imported class names.
 */
final readonly class StaticOwnerResolver
{
    /**
     * Constructor.
     *
     * @param KnownOwnerCollection $knownOwners the known class-like owner metadata
     */
    public function __construct(
        private KnownOwnerCollection $knownOwners,
    ) {
    }

    /**
     * Resolves the effective owners for one static access class node.
     *
     * @param Name   $class        the static access class node
     * @param string $currentClass the current class-like owner FQCN
     */
    public function resolve(Name $class, string $currentClass): SymbolCollection
    {
        $returnedTypes = new SymbolCollection();
        $lowerClass = $class->toLowerString();

        if ('self' === $lowerClass || 'static' === $lowerClass) {
            return $returnedTypes->add($currentClass);
        }

        if ('parent' === $lowerClass) {
            $parentClassName = $this->resolveParentClassName($currentClass);

            if ('' !== $parentClassName) {
                $returnedTypes->add($parentClassName);
            }

            return $returnedTypes;
        }

        $resolvedName = $class->getAttribute('resolvedName');

        if ($resolvedName instanceof Name) {
            return $returnedTypes->add($resolvedName->toString());
        }

        $className = $class->toString();

        if ('' !== $className) {
            $returnedTypes->add($className);
        }

        return $returnedTypes;
    }

    /**
     * Resolves the parent class name for one current class-like owner.
     *
     * @param string $currentClass the current class-like owner FQCN
     */
    public function resolveParentClassName(string $currentClass): string
    {
        $knownOwner = $this->knownOwners->get($currentClass);

        if (null === $knownOwner || null === $knownOwner->parentFqcn) {
            return '';
        }

        return $knownOwner->parentFqcn;
    }
}
