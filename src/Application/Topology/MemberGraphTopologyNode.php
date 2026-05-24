<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Topology;

use BabelForge\MemberGraph\Domain\Graph\MemberId;

/**
 * Represents one node in a member graph topology projection.
 */
final readonly class MemberGraphTopologyNode
{
    /**
     * Constructor.
     *
     * @param string                      $id       the stable topology node identifier
     * @param MemberGraphTopologyNodeKind $kind     the node kind
     * @param int                         $depth    the shortest observed depth from the topology root
     * @param MemberId|null               $memberId the member identifier when the node represents a member
     * @param string|null                 $owner    the owner FQCN when the node represents an owner
     * @param string|null                 $label    the display label when the node has no domain identifier
     */
    public function __construct(
        public string $id,
        public MemberGraphTopologyNodeKind $kind,
        public int $depth,
        public ?MemberId $memberId = null,
        public ?string $owner = null,
        public ?string $label = null,
    ) {
    }

    /**
     * Creates the root codebase topology node.
     */
    public static function codebase(): self
    {
        return new self(
            id: self::codebaseId(),
            kind: MemberGraphTopologyNodeKind::CODEBASE,
            depth: 0,
            label: 'codebase',
        );
    }

    /**
     * Creates a topology node from an owner FQCN.
     *
     * @param string $owner the owner FQCN
     * @param int    $depth the shortest observed depth from the topology root
     */
    public static function owner(string $owner, int $depth): self
    {
        return new self(
            id: self::ownerId($owner),
            kind: MemberGraphTopologyNodeKind::OWNER,
            depth: $depth,
            owner: $owner,
        );
    }

    /**
     * Creates a topology node from a member identifier.
     *
     * @param MemberId $memberId the member identifier
     * @param int      $depth    the shortest observed depth from the topology root
     */
    public static function member(MemberId $memberId, int $depth): self
    {
        return new self(
            id: $memberId->hash(),
            kind: MemberGraphTopologyNodeKind::MEMBER,
            depth: $depth,
            memberId: $memberId,
        );
    }

    /**
     * Builds the stable topology node identifier for an owner.
     *
     * @param string $owner the owner FQCN
     */
    public static function ownerId(string $owner): string
    {
        return 'owner:'.$owner;
    }

    /**
     * Builds the stable topology node identifier for the codebase root.
     */
    public static function codebaseId(): string
    {
        return 'codebase';
    }
}
