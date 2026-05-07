<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template\PhpDocTemplateSubstitutionContext;

/**
 * Resolves template substitutions carried by a generic receiver owner.
 */
final readonly class OwnerTemplateSubstitutionResolver
{
    /**
     * Constructor.
     *
     * @param ClassTemplateDefinitionIndex $classTemplateDefinitionIndex The class template definition index.
     */
    public function __construct(
        private ClassTemplateDefinitionIndex $classTemplateDefinitionIndex,
    ) {
    }

    /**
     * Collects owner template substitutions from a receiver structured type.
     *
     * Example:
     * - owner = Box
     * - receiver type = Box<Mailer>
     * - class templates = [T]
     * => T => Mailer
     *
     * @param string $owner The receiver owner FQCN.
     * @param ResolvedPhpDocType $receiverStructuredType The receiver structured type.
     *
     * @return PhpDocTemplateSubstitutionContext
     */
    public function collect(
        string $owner,
        ResolvedPhpDocType $receiverStructuredType,
    ): PhpDocTemplateSubstitutionContext {
        $context = new PhpDocTemplateSubstitutionContext();

        $classTemplateDefinitions = $this->classTemplateDefinitionIndex->get($owner);

        if (null === $classTemplateDefinitions) {
            return $context;
        }

        $genericArguments = $receiverStructuredType->genericArguments->all();

        if ([] === $genericArguments) {
            return $context;
        }

        $templateDefinitions = $classTemplateDefinitions->all();
        $templateNames = array_keys($templateDefinitions);

        foreach ($templateNames as $index => $templateName) {
            $genericArgument = $genericArguments[$index] ?? null;

            if (!$genericArgument instanceof ResolvedPhpDocType) {
                continue;
            }

            if (!$context->has($templateName)) {
                $context->set($templateName, $genericArgument);
            }
        }

        return $context;
    }

    /**
     * Merges one template substitution context into another.
     *
     * Existing target entries are preserved.
     *
     * @param PhpDocTemplateSubstitutionContext $target The mutable target context.
     * @param PhpDocTemplateSubstitutionContext $source The source context.
     *
     * @return void
     */
    public function mergeInto(
        PhpDocTemplateSubstitutionContext $target,
        PhpDocTemplateSubstitutionContext $source,
    ): void {
        foreach ($source->all() as $templateName => $resolvedType) {
            if (!$target->has($templateName)) {
                $target->set($templateName, $resolvedType);
            }
        }
    }
}
