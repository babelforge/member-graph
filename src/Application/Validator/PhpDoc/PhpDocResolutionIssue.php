<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Validator\PhpDoc;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueInterface;

/**
 * Class PhpDocResolutionIssue.
 */
final readonly class PhpDocResolutionIssue implements MemberGraphIssueInterface
{
    public function __construct(
        public PhpDocResolutionIssueType $type,
        public string $file,
        public string $owner,
        public string $member,
        public string $message,
    ) {
    }

    public function isSame(MemberGraphIssueInterface $issue): bool
    {
        return self::class === $issue::class
            && $issue->file === $this->file
            && $issue->owner === $this->owner
            && $issue->member === $this->member;
    }
}
