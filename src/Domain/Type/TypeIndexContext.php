<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Type;

use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;

/**
 * Class TypeIndexContext
 */
final class TypeIndexContext
{
    public string $fullFilePath = '';
    public string $virtualFilePath = '';
    public string $namespace = '';
    public string $owner = '';
    public string $member = '';
    public UsesByAliasCollection $usesByAlias;

    public function setFullFilePath(string $fullFilePath): self
    {
        $this->fullFilePath = $fullFilePath;

        return $this;
    }

    public function setVirtualFilePath(string $virtualFilePath): self
    {
        $this->virtualFilePath = $virtualFilePath;

        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function setOwner(string $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function setMember(string $member): self
    {
        $this->member = $member;

        return $this;
    }

    public function setUsesByAlias(UsesByAliasCollection $usesByAlias): self
    {
        $this->usesByAlias = $usesByAlias;

        return $this;
    }
}
