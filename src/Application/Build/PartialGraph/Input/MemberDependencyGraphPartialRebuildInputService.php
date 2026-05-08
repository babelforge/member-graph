<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;

/**
 * Prepares cache-backed inputs for future partial member graph rebuilds.
 */
final readonly class MemberDependencyGraphPartialRebuildInputService
{
    /**
     * Prepares a partial rebuild input when the selected rebuild plan is eligible.
     *
     * @param MemberGraphCache                        $cache       the member graph cache
     * @param MemberDependencyGraphFactoryRebuildPlan $rebuildPlan the selected rebuild plan
     */
    public function prepare(
        MemberGraphCache $cache,
        MemberDependencyGraphFactoryRebuildPlan $rebuildPlan,
    ): ?MemberDependencyGraphPartialRebuildInput {
        if (MemberDependencyGraphFactoryRebuildMode::PARTIAL_BUILD_CANDIDATE !== $rebuildPlan->mode) {
            return null;
        }

        $snapshot = $cache->globalIndexInputSnapshot();
        $knownOwners = $cache->knownOwners();

        if (null === $snapshot || !$snapshot->isCompatible() || null === $knownOwners) {
            return null;
        }

        $fragmentsToReuse = $cache->graphFragments($rebuildPlan->fragmentsToReuse->all());

        if (count($rebuildPlan->fragmentsToReuse) !== count($fragmentsToReuse)) {
            return null;
        }

        return new MemberDependencyGraphPartialRebuildInput(
            filesToBuild: $rebuildPlan->filesToBuild,
            fragmentsToReuse: $fragmentsToReuse,
            globalIndexInputSnapshot: $snapshot,
            virtualFileReferences: $cache->virtualFileReferences(),
            knownOwners: $knownOwners,
            filesToDelete: $rebuildPlan->filesToDelete,
        );
    }
}
