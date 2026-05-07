<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution\TemplateArgumentSubstitutionResolver;
use PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution\TemplateSubstitutionMerger;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Coordinates PHPDoc template substitution collection while preserving the public service API.
 */
final readonly class TemplateSubstitutionCollector
{
    private TemplateSubstitutionMerger $templateSubstitutionMerger;

    private TemplateArgumentSubstitutionResolver $templateArgumentSubstitutionResolver;

    /**
     * Constructor.
     *
     * @param TemplateSubstitutionMerger|null $templateSubstitutionMerger The optional template substitution merger.
     * @param TemplateArgumentSubstitutionResolver|null $templateArgumentSubstitutionResolver The optional argument substitution resolver.
     */
    public function __construct(
        ?TemplateSubstitutionMerger $templateSubstitutionMerger = null,
        ?TemplateArgumentSubstitutionResolver $templateArgumentSubstitutionResolver = null,
    ) {
        $this->templateSubstitutionMerger = $templateSubstitutionMerger ?? new TemplateSubstitutionMerger();
        $this->templateArgumentSubstitutionResolver = $templateArgumentSubstitutionResolver
            ?? new TemplateArgumentSubstitutionResolver($this->templateSubstitutionMerger);
    }

    /**
     * Collects substitutions by comparing one declared type against one concrete argument type.
     *
     * @param ResolvedPhpDocType $parameterType The declared parameter type.
     * @param ResolvedPhpDocType $argumentType The concrete argument type.
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     *
     * @return void
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
    ): void {
        $this->templateArgumentSubstitutionResolver->collect($parameterType, $argumentType, $context);
    }

    /**
     * Adds one template substitution, preserving multiple discovered branches as a union.
     *
     * @param PhpDocTemplateSubstitutionContext $context The mutable substitution context.
     * @param string $templateName The template name.
     * @param ResolvedPhpDocType $resolvedType The resolved template type.
     *
     * @return void
     */
    public function set(
        PhpDocTemplateSubstitutionContext $context,
        string $templateName,
        ResolvedPhpDocType $resolvedType,
    ): void {
        $this->templateSubstitutionMerger->set($context, $templateName, $resolvedType);
    }
}
