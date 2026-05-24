<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Index\Template;

use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class PhpDocTemplateDefinition.
 */
final class PhpDocTemplateDefinition
{
    public function __construct(
        public string $name,
        public ?ResolvedPhpDocType $bound = null,
    ) {
    }
}
