<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable method declaration.
 */
final readonly class MethodDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $ownerFqcn The declaring owner FQCN.
     * @param string $name The method name.
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string $visibility The method visibility.
     * @param bool $isStatic Whether the method is static.
     * @param bool $isAbstract Whether the method is abstract.
     * @param string|null $nativeReturnType The native return type.
     * @param string|null $phpDocReturnType The resolved PHPDoc return type.
     * @param string|null $effectivePhpDoc The effective PHPDoc after inheritance merging.
     * @param ParameterDeclarationSnapshotCollection $parameters The method parameters.
     * @param TemplateDeclarationSnapshotCollection $templates The method templates.
     */
    public function __construct(
        public string $ownerFqcn,
        public string $name,
        public string $fullFilePath,
        public string $virtualFilePath,
        public string $visibility = 'public',
        public bool $isStatic = false,
        public bool $isAbstract = false,
        public ?string $nativeReturnType = null,
        public ?string $phpDocReturnType = null,
        public ?string $effectivePhpDoc = null,
        public ParameterDeclarationSnapshotCollection $parameters = new ParameterDeclarationSnapshotCollection(),
        public TemplateDeclarationSnapshotCollection $templates = new TemplateDeclarationSnapshotCollection(),
    ) {
    }

    /**
     * Returns the stable method identifier.
     *
     * @return string
     */
    public function callableId(): string
    {
        return $this->ownerFqcn . '::' . $this->name;
    }
}
