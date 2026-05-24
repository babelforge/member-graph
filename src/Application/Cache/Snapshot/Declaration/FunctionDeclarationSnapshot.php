<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable function declaration.
 */
final readonly class FunctionDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string                                 $name             the function FQCN
     * @param string                                 $fullFilePath     the physical file path
     * @param string                                 $virtualFilePath  the virtual file path
     * @param string|null                            $namespace        the function namespace
     * @param string|null                            $nativeReturnType the native return type
     * @param string|null                            $phpDocReturnType the resolved PHPDoc return type
     * @param ParameterDeclarationSnapshotCollection $parameters       the function parameters
     * @param TemplateDeclarationSnapshotCollection  $templates        the function templates
     */
    public function __construct(
        public string $name,
        public string $fullFilePath,
        public string $virtualFilePath,
        public ?string $namespace = null,
        public ?string $nativeReturnType = null,
        public ?string $phpDocReturnType = null,
        public ParameterDeclarationSnapshotCollection $parameters = new ParameterDeclarationSnapshotCollection(),
        public TemplateDeclarationSnapshotCollection $templates = new TemplateDeclarationSnapshotCollection(),
    ) {
    }
}
