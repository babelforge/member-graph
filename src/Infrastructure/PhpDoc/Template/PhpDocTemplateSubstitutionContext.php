<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Template;

use Countable;
use IteratorAggregate;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use Traversable;

/**
 * Class PhpDocTemplateSubstitutionContext
 *
 * @implements IteratorAggregate<string, ResolvedPhpDocType>
 */
final class PhpDocTemplateSubstitutionContext implements IteratorAggregate, Countable
{
    /** @var array<string, ResolvedPhpDocType> */
    private array $resolvedByTemplateName = [];

    public function set(string $templateName, ResolvedPhpDocType $resolvedType): self
    {
        $this->resolvedByTemplateName[$templateName] = $resolvedType;

        return $this;
    }

    public function get(string $templateName): ?ResolvedPhpDocType
    {
        return $this->resolvedByTemplateName[$templateName] ?? null;
    }

    public function has(string $templateName): bool
    {
        return isset($this->resolvedByTemplateName[$templateName]);
    }

    /**
     * @return ResolvedPhpDocType[]
     */
    public function all(): array
    {
        return $this->resolvedByTemplateName;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->resolvedByTemplateName;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->resolvedByTemplateName);
    }
}
