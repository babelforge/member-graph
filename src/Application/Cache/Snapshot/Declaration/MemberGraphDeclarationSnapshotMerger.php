<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration;

use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;

/**
 * Merges cached and loaded declaration snapshots for a future partial rebuild.
 */
final readonly class MemberGraphDeclarationSnapshotMerger
{
    /**
     * Merges reusable cached declarations with freshly loaded declarations.
     *
     * Cached declarations belonging to rebuilt files are removed before loaded declarations are added, so deleted
     * members do not survive a partial rebuild.
     *
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached declaration snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the declaration snapshot extracted from rebuilt files
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    public function merge(
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): MemberGraphDeclarationSnapshot {
        $excludedScopes = $this->excludedScopes($cachedSnapshot, $filesToBuild);
        $merged = new MemberGraphDeclarationSnapshot();

        $this->mergeOwners($merged, $cachedSnapshot, $loadedSnapshot, $filesToBuild);
        $this->mergeMethods($merged, $cachedSnapshot, $loadedSnapshot, $filesToBuild);
        $this->mergeFunctions($merged, $cachedSnapshot, $loadedSnapshot, $filesToBuild);
        $this->mergeParameters($merged, $cachedSnapshot, $loadedSnapshot, $excludedScopes);
        $this->mergeProperties($merged, $cachedSnapshot, $loadedSnapshot, $filesToBuild);
        $this->mergeClassConstants($merged, $cachedSnapshot, $loadedSnapshot, $filesToBuild);
        $this->mergeTemplates($merged, $cachedSnapshot, $loadedSnapshot, $excludedScopes);

        return $merged;
    }

    /**
     * Merges owner declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    private function mergeOwners(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): void {
        foreach ($cachedSnapshot->owners as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                continue;
            }

            $merged->owners->add($snapshot);
        }

        foreach ($loadedSnapshot->owners as $snapshot) {
            $merged->owners->add($snapshot);
        }
    }

    /**
     * Merges method declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    private function mergeMethods(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): void {
        foreach ($cachedSnapshot->methods as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                continue;
            }

            $merged->methods->add($snapshot);
        }

        foreach ($loadedSnapshot->methods as $snapshot) {
            $merged->methods->add($snapshot);
        }
    }

    /**
     * Merges function declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    private function mergeFunctions(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): void {
        foreach ($cachedSnapshot->functions as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                continue;
            }

            $merged->functions->add($snapshot);
        }

        foreach ($loadedSnapshot->functions as $snapshot) {
            $merged->functions->add($snapshot);
        }
    }

    /**
     * Merges parameter declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param array<string, true>            $excludedScopes the callable scopes removed from cache
     */
    private function mergeParameters(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        array $excludedScopes,
    ): void {
        foreach ($cachedSnapshot->parameters as $snapshot) {
            if (isset($excludedScopes[$snapshot->callableId])) {
                continue;
            }

            $merged->parameters->add($snapshot);
        }

        foreach ($loadedSnapshot->parameters as $snapshot) {
            $merged->parameters->add($snapshot);
        }
    }

    /**
     * Merges property declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    private function mergeProperties(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): void {
        foreach ($cachedSnapshot->properties as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                continue;
            }

            $merged->properties->add($snapshot);
        }

        foreach ($loadedSnapshot->properties as $snapshot) {
            $merged->properties->add($snapshot);
        }
    }

    /**
     * Merges class constant declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     */
    private function mergeClassConstants(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): void {
        foreach ($cachedSnapshot->classConstants as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                continue;
            }

            $merged->classConstants->add($snapshot);
        }

        foreach ($loadedSnapshot->classConstants as $snapshot) {
            $merged->classConstants->add($snapshot);
        }
    }

    /**
     * Merges template declarations.
     *
     * @param MemberGraphDeclarationSnapshot $merged         the target snapshot
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached snapshot
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot the loaded snapshot
     * @param array<string, true>            $excludedScopes the scopes removed from cache
     */
    private function mergeTemplates(
        MemberGraphDeclarationSnapshot $merged,
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphDeclarationSnapshot $loadedSnapshot,
        array $excludedScopes,
    ): void {
        foreach ($cachedSnapshot->templates as $snapshot) {
            if (isset($excludedScopes[$snapshot->scopeId])) {
                continue;
            }

            $merged->templates->add($snapshot);
        }

        foreach ($loadedSnapshot->templates as $snapshot) {
            $merged->templates->add($snapshot);
        }
    }

    /**
     * Builds the cached scopes that must be removed before loaded declarations are added.
     *
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot the cached declaration snapshot
     * @param MemberGraphCacheFileCollection $filesToBuild   the physical files rebuilt from source
     *
     * @return array<string, true>
     */
    private function excludedScopes(
        MemberGraphDeclarationSnapshot $cachedSnapshot,
        MemberGraphCacheFileCollection $filesToBuild,
    ): array {
        $scopes = [];

        foreach ($cachedSnapshot->owners as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                $scopes[$snapshot->fqcn] = true;
            }
        }

        foreach ($cachedSnapshot->methods as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                $scopes[$snapshot->callableId()] = true;
            }
        }

        foreach ($cachedSnapshot->functions as $snapshot) {
            if ($filesToBuild->contains($snapshot->fullFilePath)) {
                $scopes[$snapshot->name] = true;
            }
        }

        return $scopes;
    }
}
