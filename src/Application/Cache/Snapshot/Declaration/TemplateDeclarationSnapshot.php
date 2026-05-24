<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

/**
 * Stores one cacheable PHPDoc template declaration.
 */
final readonly class TemplateDeclarationSnapshot
{
    /**
     * Constructor.
     *
     * @param string      $scopeId     the owner, method, or function scope identifier
     * @param string      $name        the template name
     * @param string|null $boundType   the resolved template bound type
     * @param string|null $defaultType the resolved default template type
     * @param string|null $variance    the declared template variance when available
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
