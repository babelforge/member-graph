<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot;

use BabelForge\MemberGraph\Domain\Owner\KnownOwner;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;

/**
 * Stores cacheable source metadata for one virtual registry file.
 */
final readonly class MemberGraphVirtualSourceMetadata
{
    /**
     * Constructor.
     *
     * @param string         $fullFilePath      the physical file path
     * @param string         $virtualFilePath   the virtual file path
     * @param string|null    $namespace         the declared namespace
     * @param string|null    $ownerName         the class-like owner FQCN, when the virtual source declares one
     * @param OwnerKind|null $ownerKind         the owner kind
     * @param string|null    $parentFqcn        the direct parent class FQCN
     * @param bool           $isAbstract        whether the owner is abstract
     * @param list<string>   $traits            the directly used traits
     * @param list<string>   $interfaces        the directly implemented interfaces
     * @param list<string>   $extendsInterfaces the directly extended interfaces
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
     * @param string      $fullFilePath    the physical file path
     * @param string      $virtualFilePath the virtual file path
     * @param KnownOwner  $knownOwner      the known owner
     * @param string|null $namespace       the declared namespace
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
     */
    public function hasOwner(): bool
    {
        return null !== $this->ownerName && null !== $this->ownerKind;
    }
}
