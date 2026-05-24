<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Validator\PhpDoc;

use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

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
