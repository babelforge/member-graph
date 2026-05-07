<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;

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
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached declaration snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The declaration snapshot extracted from rebuilt files.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return MemberGraphDeclarationSnapshot
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param array<string, true> $excludedScopes The callable scopes removed from cache.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $merged The target snapshot.
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached snapshot.
     * @param MemberGraphDeclarationSnapshot $loadedSnapshot The loaded snapshot.
     * @param array<string, true> $excludedScopes The scopes removed from cache.
     *
     * @return void
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
     * @param MemberGraphDeclarationSnapshot $cachedSnapshot The cached declaration snapshot.
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files rebuilt from source.
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
