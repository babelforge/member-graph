<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable function declaration.
 */
final readonly class FunctionDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $name The function FQCN.
     * @param string $fullFilePath The physical file path.
     * @param string $virtualFilePath The virtual file path.
     * @param string|null $namespace The function namespace.
     * @param string|null $nativeReturnType The native return type.
     * @param string|null $phpDocReturnType The resolved PHPDoc return type.
     * @param ParameterDeclarationSnapshotCollection $parameters The function parameters.
     * @param TemplateDeclarationSnapshotCollection $templates The function templates.
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
