<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Index\Template;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class PhpDocTemplateDefinition
 */
final class PhpDocTemplateDefinition
{
    public function __construct(
        public string $name,
        public ?ResolvedPhpDocType $bound = null,
    ) {
    }
}
