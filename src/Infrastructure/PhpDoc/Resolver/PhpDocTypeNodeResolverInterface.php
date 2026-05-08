<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

/**
 * Resolves PHPDoc type nodes into normalized class-name sets.
 */
interface PhpDocTypeNodeResolverInterface
{
    /**
     * Resolves one PHPDoc type node into a structured resolved type tree.
     *
     * @param TypeNode                           $typeNode            the PHPDoc type node
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the use imports indexed by alias
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     * @param TypeIndexContext                   $context             the type index context
     * @param PhpDocTagKind                      $kind                the kind of the PHPDoc tag
     */
    public function resolveStructured(
        TypeNode $typeNode,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): ResolvedPhpDocType;

    /**
     * Resolves one PHPDoc type node into symbols intended for value usage.
     *
     * Rule:
     * - if the type has generic arguments, return the flattened symbols of the generic arguments
     * - otherwise return the direct symbols of the type itself
     *
     * @param TypeNode                           $typeNode            the PHPDoc type node
     * @param string                             $currentNamespace    the current namespace
     * @param UsesByAliasCollection              $usesByAlias         the use imports indexed by alias
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions the declared template definitions
     * @param TypeIndexContext                   $context             the type index context
     * @param PhpDocTagKind                      $kind                the kind of the PHPDoc tag
     */
    public function resolveForValueUsage(
        TypeNode $typeNode,
        string $currentNamespace,
        UsesByAliasCollection $usesByAlias,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        TypeIndexContext $context,
        PhpDocTagKind $kind,
    ): SymbolCollection;

    /**
     * Resolves one ResolvedPhpDocType into symbols intended for value usage.
     *
     * @param ResolvedPhpDocType $resolvedStructured the resolved structured PHPDoc type
     */
    public function extractValueUsage(ResolvedPhpDocType $resolvedStructured): SymbolCollection;
}
