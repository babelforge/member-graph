<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Validator\PhpDoc;

/**
 * Class PhpDocResolutionIssueType
 */
enum PhpDocResolutionIssueType: string
{
    case INHERIT_DOC_PARENT_NOT_FOUND = 'inherit_doc_parent_not_found';
    case INHERIT_DOC_PARENT_NOT_USABLE = 'inherit_doc_parent_not_usable';
    case INHERIT_DOC_MERGE_INCOHERENT = 'inherit_doc_merge_incoherent';
    case TEMPLATE_REFERENCE_UNRESOLVED = 'template_reference_unresolved';
    case TEMPLATE_TAG_NOT_USABLE = 'template_tag_not_usable';
    case CLASS_TAG_NOT_USABLE = 'class_tag_not_usable';
    case RETURN_TAG_NOT_USABLE = 'return_tag_not_usable';
    case PARAM_TAG_NOT_USABLE = 'param_tag_not_usable';
    case VAR_TAG_NOT_USABLE = 'var_tag_not_usable';
    case INCONSISTENT_DOCBLOCK = 'inconsistent_docblock';
}
