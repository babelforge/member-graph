<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

use BabelForge\MemberGraph\Domain\Owner\OwnerKind;

/**
 * Stores one cacheable class-like owner declaration.
 */
final readonly class OwnerDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string                                $fqcn              the owner FQCN
     * @param OwnerKind                             $kind              the owner kind
     * @param string                                $fullFilePath      the physical file path
     * @param string                                $virtualFilePath   the virtual file path
     * @param string|null                           $namespace         the declared namespace
     * @param string|null                           $parentFqcn        the direct parent class FQCN
     * @param bool                                  $isAbstract        whether the owner is abstract
     * @param list<string>                          $traits            the directly used traits
     * @param list<string>                          $interfaces        the directly implemented interfaces
     * @param list<string>                          $extendsInterfaces the directly extended interfaces
     * @param TemplateDeclarationSnapshotCollection $templates         the owner template declarations
     */
    public function __construct(
        public string $fqcn,
        public OwnerKind $kind,
        public string $fullFilePath,
        public string $virtualFilePath,
        public ?string $namespace = null,
        public ?string $parentFqcn = null,
        public bool $isAbstract = false,
        public array $traits = [],
        public array $interfaces = [],
        public array $extendsInterfaces = [],
        public TemplateDeclarationSnapshotCollection $templates = new TemplateDeclarationSnapshotCollection(),
    ) {
    }
}
