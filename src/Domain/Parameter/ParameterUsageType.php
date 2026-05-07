<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Domain\Parameter;

/**
 * Enumerates supported parameter usage kinds.
 */
enum ParameterUsageType
{
    case NAMED_ARGUMENT;
}
