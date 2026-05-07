<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Diagnostics;

/**
 * Describes one working set closure diagnostic.
 */
final readonly class MemberDependencyGraphPartialRebuildClosureDiagnostic
{
    /**
     * Constructor.
     *
     * @param MemberDependencyGraphPartialRebuildClosureDiagnosticReason $reason The diagnostic reason.
     * @param string $message The diagnostic message.
     * @param string|null $sourceFilePath The source file path related to the diagnostic.
     * @param string|null $reference The unresolved or expanded reference.
     */
    public function __construct(
        public MemberDependencyGraphPartialRebuildClosureDiagnosticReason $reason,
        public string $message,
        public ?string $sourceFilePath = null,
        public ?string $reference = null,
    ) {
    }
}
