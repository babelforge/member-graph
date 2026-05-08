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
     * @param string                                 $ownerFqcn        the declaring owner FQCN
     * @param string                                 $name             the method name
     * @param string                                 $fullFilePath     the physical file path
     * @param string                                 $virtualFilePath  the virtual file path
     * @param string                                 $visibility       the method visibility
     * @param bool                                   $isStatic         whether the method is static
     * @param bool                                   $isAbstract       whether the method is abstract
     * @param string|null                            $nativeReturnType the native return type
     * @param string|null                            $phpDocReturnType the resolved PHPDoc return type
     * @param string|null                            $effectivePhpDoc  the effective PHPDoc after inheritance merging
     * @param ParameterDeclarationSnapshotCollection $parameters       the method parameters
     * @param TemplateDeclarationSnapshotCollection  $templates        the method templates
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
     */
    public function callableId(): string
    {
        return $this->ownerFqcn.'::'.$this->name;
    }
}
