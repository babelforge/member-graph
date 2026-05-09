<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Collect;

use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsage;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerUsageType;
use PhpNoobs\MemberGraph\Domain\Source\SourceNodeId;

/**
 * Collects class-like owner usages discovered during graph traversal.
 */
final readonly class OwnerUsageCollector
{
    /**
     * Constructor.
     *
     * @param OwnerUsageCollection $usages          the owner usages collection
     * @param string               $virtualFilePath the current virtual file path
     */
    public function __construct(
        private OwnerUsageCollection $usages,
        private string $virtualFilePath,
    ) {
    }

    /**
     * Collects one owner usage.
     *
     * @param string            $sourceSymbol the symbol where the usage appears
     * @param string            $target       the targeted owner FQCN
     * @param OwnerUsageType    $type         the owner usage type
     * @param SourceNodeId|null $sourceNodeId the source node identifier when available
     */
    public function collect(
        string $sourceSymbol,
        string $target,
        OwnerUsageType $type,
        ?SourceNodeId $sourceNodeId = null,
    ): void {
        $this->usages->add(new OwnerUsage(
            sourceSymbol: $sourceSymbol,
            target: $target,
            type: $type,
            file: $this->virtualFilePath,
            sourceNodeId: $sourceNodeId,
        ));
    }
}
