<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;

/**
 * Stores one cacheable class-like owner declaration.
 */
final readonly class OwnerDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $fqcn The owner FQCN.
     * @param OwnerKind $kind The owner kind.
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string|null $namespace The declared namespace.
     * @param string|null $parentFqcn The direct parent class FQCN.
     * @param bool $isAbstract Whether the owner is abstract.
     * @param list<string> $traits The directly used traits.
     * @param list<string> $interfaces The directly implemented interfaces.
     * @param list<string> $extendsInterfaces The directly extended interfaces.
     * @param TemplateDeclarationSnapshotCollection $templates The owner template declarations.
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
