<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Resolves template substitutions by comparing declared and concrete structured types.
 */
final readonly class TemplateArgumentSubstitutionResolver
{
    /**
     * Constructor.
     *
     * @param TemplateSubstitutionMerger                  $templateSubstitutionMerger                  the template substitution merger
     * @param TemplateUnionSubstitutionResolver           $templateUnionSubstitutionResolver           the union substitution resolver
     * @param TemplateCollectionShapeSubstitutionResolver $templateCollectionShapeSubstitutionResolver the collection-shape substitution resolver
     * @param TemplateGenericSubstitutionResolver         $templateGenericSubstitutionResolver         the generic substitution resolver
     * @param TemplateShapeSubstitutionResolver           $templateShapeSubstitutionResolver           the shape substitution resolver
     * @param TemplateCallableSubstitutionResolver        $templateCallableSubstitutionResolver        the callable substitution resolver
     */
    public function __construct(
        private TemplateSubstitutionMerger $templateSubstitutionMerger,
        private TemplateUnionSubstitutionResolver $templateUnionSubstitutionResolver = new TemplateUnionSubstitutionResolver(),
        private TemplateCollectionShapeSubstitutionResolver $templateCollectionShapeSubstitutionResolver = new TemplateCollectionShapeSubstitutionResolver(),
        private TemplateGenericSubstitutionResolver $templateGenericSubstitutionResolver = new TemplateGenericSubstitutionResolver(),
        private TemplateShapeSubstitutionResolver $templateShapeSubstitutionResolver = new TemplateShapeSubstitutionResolver(),
        private TemplateCallableSubstitutionResolver $templateCallableSubstitutionResolver = new TemplateCallableSubstitutionResolver(),
    ) {
    }

    /**
     * Collects substitutions by comparing one declared type against one concrete argument type.
     *
     * @param ResolvedPhpDocType                $parameterType the declared parameter type
     * @param ResolvedPhpDocType                $argumentType  the concrete argument type
     * @param PhpDocTemplateSubstitutionContext $context       the mutable substitution context
     */
    public function collect(
        ResolvedPhpDocType $parameterType,
        ResolvedPhpDocType $argumentType,
        PhpDocTemplateSubstitutionContext $context,
    ): void {
        if ($parameterType->isUnionContainer()) {
            $this->templateUnionSubstitutionResolver->collectFromUnionParameter($parameterType, $argumentType, $context, $this);

            return;
        }

        if ($parameterType->hasTemplateReference()) {
            $templateName = $parameterType->templateReference->name;

            if ('' !== $templateName) {
                $this->templateSubstitutionMerger->set($context, $templateName, $argumentType);

                return;
            }
        }

        if ($argumentType->isUnionContainer()) {
            $this->templateUnionSubstitutionResolver->collectFromUnionArgument($parameterType, $argumentType, $context, $this);

            return;
        }

        if ($argumentType->isNonEmptyShape() && !$parameterType->genericArguments->isEmpty()) {
            if ($this->templateCollectionShapeSubstitutionResolver->collect($parameterType, $argumentType, $context, $this)) {
                return;
            }
        }

        if ($this->templateGenericSubstitutionResolver->supports($parameterType, $argumentType)) {
            $this->templateGenericSubstitutionResolver->collect($parameterType, $argumentType, $context, $this);

            return;
        }

        if ($this->templateShapeSubstitutionResolver->supportsShapeFields($parameterType, $argumentType)) {
            $this->templateShapeSubstitutionResolver->collectShapeFields($parameterType, $argumentType, $context, $this);
        }

        if ($this->templateShapeSubstitutionResolver->supportsNonEmptyShapeFields($parameterType, $argumentType)) {
            $this->templateShapeSubstitutionResolver->collectNonEmptyShapeFields($parameterType, $argumentType, $context, $this);

            return;
        }

        if ($parameterType->isCallable() && $argumentType->isCallable()) {
            $this->templateCallableSubstitutionResolver->collect($parameterType, $argumentType, $context, $this);
        }

        if ($parameterType->isParenthesized()) {
            $innerType = $parameterType->getParenthesizedInnerType();

            if ($innerType instanceof ResolvedPhpDocType) {
                $this->collect($innerType, $argumentType, $context);
            }
        }
    }
}
