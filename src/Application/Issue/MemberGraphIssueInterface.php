<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Issue;

/**
 * Interface MemberGraphIssueInterface.
 */
interface MemberGraphIssueInterface
{
    public function isSame(MemberGraphIssueInterface $issue): bool;
}
