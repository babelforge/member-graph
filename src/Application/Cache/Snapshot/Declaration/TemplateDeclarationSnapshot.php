<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable PHPDoc template declaration.
 */
final readonly class TemplateDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string $scopeId The owner, method, or function scope identifier.
     * @param string $name The template name.
     * @param string|null $boundType The resolved template bound type.
     * @param string|null $defaultType The resolved default template type.
     * @param string|null $variance The declared template variance when available.
     */
    public function __construct(
        public string $scopeId,
        public string $name,
        public ?string $boundType = null,
        public ?string $defaultType = null,
        public ?string $variance = null,
    ) {
    }
}
