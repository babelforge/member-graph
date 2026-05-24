<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Build\PartialGraph\Diagnostics;

/**
 * Describes one working set closure diagnostic.
 */
final readonly class MemberDependencyGraphPartialRebuildClosureDiagnostic
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphPartialRebuildClosureDiagnosticReason $reason         the diagnostic reason
     * @param string                                                     $message        the diagnostic message
     * @param string|null                                                $sourceFilePath the source file path related to the diagnostic
     * @param string|null                                                $reference      the unresolved or expanded reference
     */
    public function __construct(
        public MemberDependencyGraphPartialRebuildClosureDiagnosticReason $reason,
        public string $message,
        public ?string $sourceFilePath = null,
        public ?string $reference = null,
    ) {
    }
}
