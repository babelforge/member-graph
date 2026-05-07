<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Validator\PhpDoc;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;

/**
 * Class SemanticState
 */
final readonly class SemanticState
{
    /**
     * @param PhpDocTemplateDefinitionCollection $templates
     * @param bool $hasTemplate
     * @param array<string, ResolvedPhpDocType> $paramsByName
     * @param bool $hasParam
     * @param ResolvedPhpDocType|null $returnType
     * @param bool $hasReturnType
     */
    public function __construct(
        public PhpDocTemplateDefinitionCollection $templates,
        public bool                               $hasTemplate,
        public array                              $paramsByName,
        public bool                               $hasParam,
        public null|ResolvedPhpDocType            $returnType,
        public bool                               $hasReturnType,
    ) {
    }
}
