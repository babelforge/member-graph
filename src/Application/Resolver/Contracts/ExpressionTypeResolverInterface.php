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
     * @param Node                               $expression          the expression to resolve
     * @param array<string, VariableTypeInfo>    $variableTypes       known local variable types
     * @param string                             $currentClass        the current class-like owner
     * @param PhpDocTemplateDefinitionCollection $templateDefinitions known template definitions
     * @param UsesByAliasCollection              $usesByAlias         known uses by alias
     *
     * @return SymbolCollection resolved FQCN or null when unknown
     */
    public function resolve(
        Node $expression,
        array $variableTypes,
        string $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection $usesByAlias,
    ): SymbolCollection;

    /**
     * Resolves the structured PHPDoc type of one expression when possible.
     *
     * @param array<string, VariableTypeInfo> $variableTypes
     */
    public function resolveStructuredPhpDocType(
        Expr $expression,
        array $variableTypes,
        string $currentClass,
        PhpDocTemplateDefinitionCollection $templateDefinitions,
        UsesByAliasCollection $usesByAlias,
    ): ?ResolvedPhpDocType;

    /**
     * Extracts flat symbols from one structured type when possible.
     */
    public function extractStructuredSymbols(?ResolvedPhpDocType $structuredType): SymbolCollection;
}
