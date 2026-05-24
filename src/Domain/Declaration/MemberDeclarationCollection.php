<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Domain\Declaration;

use BabelForge\MemberGraph\Domain\Graph\MemberId;

/**
 * Stores declared members.
 */
final class MemberDeclarationCollection
{
    /** @var array<string, MemberDeclaration> */
    private array $items = [];

    /**
     * @return MemberDeclaration[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function add(MemberDeclaration $decl): void
    {
        $this->items[$decl->id->hash()] = $decl;
    }

    public function get(MemberId $id): ?MemberDeclaration
    {
        return $this->items[$id->hash()] ?? null;
    }
}
