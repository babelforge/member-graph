<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Validator\PhpDoc;

use BabelForge\MemberGraph\Application\Issue\MemberGraphIssueCollection;

/**
 * Class PhpDodIssueCollection.
 */
final class PhpDocIssueCollection
{
    public static function add(
        ?MemberGraphIssueCollection $issues,
        PhpDocResolutionIssueType $newIssueType,
        string $fullFilePath,
        string $owner,
        string $member,
    ): void {
        if (null === $issues) {
            return;
        }

        $issues->add(new PhpDocResolutionIssue(
            type: $newIssueType,
            file: $fullFilePath,
            owner: $owner,
            member: $member,
            message: match ($newIssueType) {
                PhpDocResolutionIssueType::INHERIT_DOC_PARENT_NOT_FOUND => 'No valid inherited parent PHPDoc could be found.',
                PhpDocResolutionIssueType::INHERIT_DOC_PARENT_NOT_USABLE => 'Inherited parent PHPDoc was found but is not usable.',
                PhpDocResolutionIssueType::INHERIT_DOC_MERGE_INCOHERENT => 'Merged @inheritDoc result is semantically incoherent.',
                PhpDocResolutionIssueType::TEMPLATE_REFERENCE_UNRESOLVED => 'Template reference could not be resolved.',
                PhpDocResolutionIssueType::CLASS_TAG_NOT_USABLE => 'Class tag is not usable.',
                PhpDocResolutionIssueType::TEMPLATE_TAG_NOT_USABLE => 'Template tag is not usable.',
                PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE => 'Return tag is not usable.',
                PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE => 'Param tag is not usable.',
                PhpDocResolutionIssueType::VAR_TAG_NOT_USABLE => 'Var tag is not usable.',
                PhpDocResolutionIssueType::INCONSISTENT_DOCBLOCK => 'Docblock is inconsistent.',
            },
        ));
    }
}
