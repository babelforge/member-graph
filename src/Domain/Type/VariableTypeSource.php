<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Type;

/**
 * Enum VariableTypeSource.
 */
enum VariableTypeSource
{
    case ASSIGNMENT;
    case PARAMETER;
    case PHPDOC;
}
