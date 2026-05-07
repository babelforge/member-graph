<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;

/**
 * Carries local expression-resolution state for one resolver invocation.
 */
final readonly class ExpressionResolutionContext
{
    /**
     * Constructor.
     *
     * @param array<string, VariableTypeInfo> $variableTypes The currently known local variable types.
     * @param string $currentClass The current class-like owner FQCN.
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions The active PHPDoc template definitions.
     * @param UsesByAliasCollection $usesByAlias The current file imports indexed by alias.
     */
    public function __construct(
        public array $variableTypes,
        public string $currentClass,
        public PhpDocTemplateDefinitionCollection $templateDefinitions,
        public UsesByAliasCollection $usesByAlias,
    ) {
    }
}
