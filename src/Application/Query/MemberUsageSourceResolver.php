<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Query;

use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;

/**
 * Resolves a member usage source symbol into a member identifier when possible.
 */
final class MemberUsageSourceResolver
{
    /**
     * Resolves one source symbol into a member identifier.
     *
     * @param string $sourceSymbol the source symbol to resolve
     */
    public function resolve(string $sourceSymbol): ?MemberId
    {
        if ('' === $sourceSymbol) {
            return null;
        }

        $separatorPosition = strpos($sourceSymbol, '::');

        if (false === $separatorPosition) {
            return new MemberId('', $sourceSymbol, MemberType::FUNCTION_);
        }

        $owner = substr($sourceSymbol, 0, $separatorPosition);
        $name = substr($sourceSymbol, $separatorPosition + 2);

        if ('' === $owner || '' === $name || 'global' === $owner) {
            return null;
        }

        return new MemberId($owner, $name, MemberType::METHOD);
    }
}
