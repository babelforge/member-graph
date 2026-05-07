<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service\TemplateSubstitution;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Merges discovered PHPDoc template substitutions into one mutable context.
 */
final readonly class TemplateSubstitutionMerger
{
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
        if (!$context->has($templateName)) {
            $context->set($templateName, $resolvedType);

            return;
        }

        $existingType = $context->get($templateName);

        if (!$existingType instanceof ResolvedPhpDocType) {
            $context->set($templateName, $resolvedType);

            return;
        }

        $unionMembers = new ResolvedPhpDocTypeCollection();

        $this->addBranches($unionMembers, $existingType);
        $this->addBranches($unionMembers, $resolvedType);

        $context->set(
            $templateName,
            ResolvedPhpDocType::newGeneric(new SymbolCollection(), $unionMembers),
        );
    }

    /**
     * Adds one resolved type or its union branches to a substitution collection.
     *
     * @param ResolvedPhpDocTypeCollection $target The target collection.
     * @param ResolvedPhpDocType $type The type to add.
     *
     * @return void
     */
    private function addBranches(ResolvedPhpDocTypeCollection $target, ResolvedPhpDocType $type): void
    {
        if (!$type->isUnionContainer()) {
            $target->add($type);

            return;
        }

        foreach ($type->genericArguments as $branch) {
            $target->add($branch);
        }
    }
}
