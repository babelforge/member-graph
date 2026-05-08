<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Validator\PhpDoc;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class SemanticState.
 */
final readonly class SemanticState
{
    /**
     * @param array<string, ResolvedPhpDocType> $paramsByName
     */
    public function __construct(
        public PhpDocTemplateDefinitionCollection $templates,
        public bool $hasTemplate,
        public array $paramsByName,
        public bool $hasParam,
        public ?ResolvedPhpDocType $returnType,
        public bool $hasReturnType,
    ) {
    }
}
