<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Diagnostics;

/**
 * Lists partial rebuild working set closure diagnostic reasons.
 */
enum MemberDependencyGraphPartialRebuildClosureDiagnosticReason: string
{
    case UNRESOLVED_REFERENCE = 'unresolved_reference';
    case CONSERVATIVE_EXPANSION = 'conservative_expansion';
    case MAX_ITERATIONS_REACHED = 'max_iterations_reached';
}
