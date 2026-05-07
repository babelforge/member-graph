<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Contracts;

use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Domain\Type\VariableTypeInfo;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\Expr;

/**
 * Resolves the best-known type for one expression.
 */
interface ExpressionTypeResolverInterface
{
    /**
     * Resolves the best-known type for one expression.
     *
     * @param Node $expression The expression to resolve.
     * @param array<string, VariableTypeInfo> $variableTypes Known local variable types.
     * @param string $currentClass The current class-like owner.
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions Known template definitions.
     * @param UsesByAliasCollection $usesByAlias Known uses by alias.
     *
     * @return SymbolCollection Resolved FQCN or null when unknown.
     */
    public function resolve(
        Node                               $expression,
        array                              $variableTypes,
        string                             $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection              $usesByAlias,
    ): SymbolCollection;

    /**
     * Resolves the structured PHPDoc type of one expression when possible.
     *
     * @param Expr $expression
     * @param array<string, VariableTypeInfo> $variableTypes
     * @param string $currentClass
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions
     * @param UsesByAliasCollection $usesByAlias
     *
     * @return ResolvedPhpDocType|null
     */
    public function resolveStructuredPhpDocType(
        Expr                               $expression,
        array                              $variableTypes,
        string                             $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection              $usesByAlias,
    ): ?ResolvedPhpDocType;

    /**
     * Extracts flat symbols from one structured type when possible.
     *
     * @param ResolvedPhpDocType|null $structuredType
     *
     * @return SymbolCollection
     */
    public function extractStructuredSymbols(?ResolvedPhpDocType $structuredType): SymbolCollection;
}
