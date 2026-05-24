<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver;

use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Type\VariableTypeInfo;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;

/**
 * Carries local expression-resolution state for one resolver invocation.
 */
final readonly class ExpressionResolutionContext
{
    /**
     * Constructor.
     *
     * @param array<string, VariableTypeInfo>    $variableTypes       the currently known local variable types
     * @param string                             $currentClass        the current class-like owner FQCN
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the active PHPDoc template definitions
     * @param UsesByAliasCollection              $usesByAlias         the current file imports indexed by alias
     */
    public function __construct(
        public array $variableTypes,
        public string $currentClass,
        public PhpDocTemplateDefinitionCollection $templateDefinitions,
        public UsesByAliasCollection $usesByAlias,
    ) {
    }
}
