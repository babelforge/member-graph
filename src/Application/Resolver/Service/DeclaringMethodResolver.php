<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Method\MethodNodeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;

/**
 * Resolves the class-like owner that actually declares an inherited method.
 */
final readonly class DeclaringMethodResolver
{
    /**
     * Constructor.
     *
     * @param MethodNodeIndex      $methodNodeIndex the global method node index
     * @param KnownOwnerCollection $knownOwners     the known class-like owner metadata
     */
    public function __construct(
        private MethodNodeIndex $methodNodeIndex,
        private KnownOwnerCollection $knownOwners,
    ) {
    }

    /**
     * Resolves the owner that declares a method when starting from one candidate owner.
     *
     * @param string $owner      the starting owner FQCN
     * @param string $methodName the method name
     */
    public function resolve(string $owner, string $methodName): ?string
    {
        $current = $owner;
        $visited = [];

        while ('' !== $current && !isset($visited[$current])) {
            $visited[$current] = true;

            if ($this->methodNodeIndex->has($current, $methodName)) {
                return $current;
            }

            $knownOwner = $this->knownOwners->get($current);
            $current = $knownOwner->parentFqcn ?? '';
        }

        return null;
    }
}
