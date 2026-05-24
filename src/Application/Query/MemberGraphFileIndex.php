<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Query;

use BabelForge\MemberGraph\Application\Impact\ImpactedFileCollection;
use BabelForge\MemberGraph\Application\Impact\ImpactedOwnerCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberId;

/**
 * Read-side file index derived from a member dependency graph.
 */
final class MemberGraphFileIndex
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $filesByOwner = [];

    /**
     * @var array<string, array<string, true>>
     */
    private array $ownersByFile = [];

    /**
     * @var array<string, array<string, true>>
     */
    private array $filesByMember = [];

    /**
     * @var array<string, array<string, MemberId>>
     */
    private array $membersByFile = [];

    /**
     * @var array<string, true>
     */
    private array $files = [];

    /**
     * Adds a relationship between one owner and one file.
     *
     * @param string $owner the owner FQCN
     * @param string $file  the file path
     */
    public function addOwnerFile(string $owner, string $file): void
    {
        if ('' === $file) {
            return;
        }

        $this->files[$file] = true;

        if ('' === $owner) {
            return;
        }

        $this->filesByOwner[$owner][$file] = true;
        $this->ownersByFile[$file][$owner] = true;
    }

    /**
     * Adds a relationship between one member and one file.
     *
     * @param MemberId $memberId the member identifier
     * @param string   $file     the file path
     */
    public function addMemberFile(MemberId $memberId, string $file): void
    {
        if ('' === $file) {
            return;
        }

        $this->files[$file] = true;

        $memberHash = $memberId->hash();

        $this->filesByMember[$memberHash][$file] = true;
        $this->membersByFile[$file][$memberHash] = $memberId;
        $this->addOwnerFile($memberId->owner, $file);
    }

    /**
     * Returns files related to one owner.
     *
     * @param string $owner the owner FQCN
     */
    public function filesForOwner(string $owner): ImpactedFileCollection
    {
        $files = new ImpactedFileCollection();

        foreach (array_keys($this->filesByOwner[$owner] ?? []) as $file) {
            $files->add($file);
        }

        return $files;
    }

    /**
     * Returns files related to one member.
     *
     * @param MemberId $memberId the member identifier
     */
    public function filesForMember(MemberId $memberId): ImpactedFileCollection
    {
        $files = new ImpactedFileCollection();

        foreach (array_keys($this->filesByMember[$memberId->hash()] ?? []) as $file) {
            $files->add($file);
        }

        return $files;
    }

    /**
     * Returns owners related to one file.
     *
     * @param string $file the file path
     */
    public function ownersInFile(string $file): ImpactedOwnerCollection
    {
        $owners = new ImpactedOwnerCollection();

        foreach (array_keys($this->ownersByFile[$file] ?? []) as $owner) {
            $owners->add($owner);
        }

        return $owners;
    }

    /**
     * Returns members related to one file.
     *
     * @param string $file the file path
     */
    public function membersInFile(string $file): MemberIdCollection
    {
        $members = new MemberIdCollection();

        foreach ($this->membersByFile[$file] ?? [] as $memberId) {
            $members->add($memberId);
        }

        return $members;
    }

    /**
     * Returns all files known by this index.
     */
    public function sourceFiles(): ImpactedFileCollection
    {
        $files = new ImpactedFileCollection();

        foreach (array_keys($this->files) as $file) {
            $files->add($file);
        }

        return $files;
    }
}
