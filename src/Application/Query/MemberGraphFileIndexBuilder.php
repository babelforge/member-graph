<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Query;

use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;

/**
 * Builds a read-side file index from a member dependency graph.
 */
final readonly class MemberGraphFileIndexBuilder
{
    /**
     * Builds the file index.
     *
     * @param MemberDependencyGraph $graph the member dependency graph
     */
    public function build(MemberDependencyGraph $graph): MemberGraphFileIndex
    {
        $index = new MemberGraphFileIndex();

        foreach ($graph->declarations->all() as $declaration) {
            $index->addMemberFile($declaration->id, $declaration->file);
        }

        foreach ($graph->usages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $index->addMemberFile($usage->target, $usage->file);
                $index->addOwnerFile($this->ownerFromSourceSymbol($usage->sourceSymbol), $usage->file);
            }
        }

        foreach ($graph->parameterUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $index->addOwnerFile($usage->target->owner, $usage->file);
                $index->addOwnerFile($this->ownerFromSourceSymbol($usage->sourceSymbol), $usage->file);
            }
        }

        return $index;
    }

    /**
     * Extracts an owner FQCN from a member source symbol.
     *
     * @param string $sourceSymbol the source symbol
     */
    private function ownerFromSourceSymbol(string $sourceSymbol): string
    {
        $separatorPosition = strpos($sourceSymbol, '::');

        if (false === $separatorPosition) {
            return '';
        }

        return substr($sourceSymbol, 0, $separatorPosition);
    }
}
