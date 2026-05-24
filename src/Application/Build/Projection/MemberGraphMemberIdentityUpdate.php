<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\Projection;

use BabelForge\MemberGraph\Domain\Graph\MemberType;

/**
 * Represents one member identity update requested by a graph projection.
 */
final readonly class MemberGraphMemberIdentityUpdate
{
    /**
     * Constructor.
     *
     * @param MemberType $type    the member type to update
     * @param string     $owner   the current owner identity
     * @param string     $name    the current member name
     * @param string     $newName the projected member name
     */
    public function __construct(
        public MemberType $type,
        public string $owner,
        public string $name,
        public string $newName,
    ) {
    }
}
