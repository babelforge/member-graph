<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot;

use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;

/**
 * Stores cacheable source metadata for one virtual registry file.
 */
final readonly class MemberGraphVirtualSourceMetadata
{
    /**
     * Constructor.
     *
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string|null $namespace The declared namespace.
     * @param string|null $ownerName The class-like owner FQCN, when the virtual source declares one.
     * @param OwnerKind|null $ownerKind The owner kind.
     * @param string|null $parentFqcn The direct parent class FQCN.
     * @param bool $isAbstract Whether the owner is abstract.
     * @param list<string> $traits The directly used traits.
     * @param list<string> $interfaces The directly implemented interfaces.
     * @param list<string> $extendsInterfaces The directly extended interfaces.
     */
    public function __construct(
        public string $fullFilePath,
        public string $virtualFilePath,
        public ?string $namespace = null,
        public ?string $ownerName = null,
        public ?OwnerKind $ownerKind = null,
        public ?string $parentFqcn = null,
        public bool $isAbstract = false,
        public array $traits = [],
        public array $interfaces = [],
        public array $extendsInterfaces = [],
    ) {
    }

    /**
     * Creates source metadata from a known owner.
     *
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param KnownOwner $knownOwner The known owner.
     * @param string|null $namespace The declared namespace.
     *
     * @return self
     */
    public static function fromKnownOwner(
        string $fullFilePath,
        string $virtualFilePath,
        KnownOwner $knownOwner,
        ?string $namespace = null,
    ): self {
        return new self(
            fullFilePath: $fullFilePath,
            virtualFilePath: $virtualFilePath,
            namespace: $namespace,
            ownerName: $knownOwner->fqcn,
            ownerKind: $knownOwner->kind,
            parentFqcn: $knownOwner->parentFqcn,
            isAbstract: $knownOwner->isAbstract,
            traits: $knownOwner->traits,
            interfaces: $knownOwner->interfaces,
            extendsInterfaces: $knownOwner->extendsInterfaces,
        );
    }

    /**
     * Indicates whether this virtual source declares a class-like owner.
     *
     * @return bool
     */
    public function hasOwner(): bool
    {
        return null !== $this->ownerName && null !== $this->ownerKind;
    }
}
