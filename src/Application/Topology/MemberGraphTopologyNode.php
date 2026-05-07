<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Topology;

use PhpNoobs\MemberGraph\Domain\Graph\MemberId;

/**
 * Represents one node in a member graph topology projection.
 */
final readonly class MemberGraphTopologyNode
{
    /**
     * Constructor.
     *
     * @param string $id The stable topology node identifier.
     * @param MemberGraphTopologyNodeKind $kind The node kind.
     * @param int $depth The shortest observed depth from the topology root.
     * @param MemberId|null $memberId The member identifier when the node represents a member.
     * @param string|null $owner The owner FQCN when the node represents an owner.
     * @param string|null $label The display label when the node has no domain identifier.
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
     *
     * @return self
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
     * @param string $owner The owner FQCN.
     * @param int $depth The shortest observed depth from the topology root.
     *
     * @return self
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
     * @param MemberId $memberId The member identifier.
     * @param int $depth The shortest observed depth from the topology root.
     *
     * @return self
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
     * @param string $owner The owner FQCN.
     *
     * @return string
     */
    public static function ownerId(string $owner): string
    {
        return 'owner:' . $owner;
    }

    /**
     * Builds the stable topology node identifier for the codebase root.
     *
     * @return string
     */
    public static function codebaseId(): string
    {
        return 'codebase';
    }
}
