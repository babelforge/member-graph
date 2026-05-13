<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryBuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildReason;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphFastPathRunner;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphFullBuildRunner;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphPartialBuildRunner;
use PhpNoobs\MemberGraph\Application\Build\InMemoryRefresh\MemberGraphInMemoryRefreshExecutor;
use PhpNoobs\MemberGraph\Application\Build\InMemoryRefresh\MemberGraphInMemoryRefreshWorkingSet;
use PhpNoobs\MemberGraph\Application\Build\InMemoryRefresh\MemberGraphInMemoryRefreshWorkingSetResolver;
use PhpNoobs\MemberGraph\Application\Build\Input\MemberGraphBuildInput;
use PhpNoobs\MemberGraph\Application\Build\MemberDependencyGraphBuilder;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Assembly\MemberDependencyGraphPartialRebuildAssembler;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInputService;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSetResolver;
use PhpNoobs\MemberGraph\Application\Build\Source\MemberGraphPhpFileScanner;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheLoadResult;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheLoadStatus;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCacheWriteResult;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCachePlan;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use PhpNoobs\MemberGraph\Infrastructure\PhpParser\Indexing\StructuralNodeIndexBuilder;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;

/**
 * Builds member dependency graphs from project directories.
 */
final readonly class MemberDependencyGraphFactory
{
    /**
     * Builds a fresh in-memory member dependency graph from existing virtual files.
     *
     * This entry point is intended for transactional workflows that already mutated
     * VirtualPhpSourceFile AST nodes and need a fresh semantic build without reading
     * physical files or refreshing the persistent cache.
     *
     * @param VirtualPhpSourceFileCollection           $virtualFiles          the virtual files to analyze
     * @param MemberGraphIssueCollection|null          $dependencyGraphIssues the optional dependency graph issue collection
     * @param MemberDependencyGraphFactoryOptions|null $options               reserved factory behavior flags
     */
    public static function fromVirtualFiles(
        VirtualPhpSourceFileCollection $virtualFiles,
        ?MemberGraphIssueCollection $dependencyGraphIssues = null,
        ?MemberDependencyGraphFactoryOptions $options = null,
    ): MemberDependencyGraphBuild {
        return self::buildFromVirtualFiles(
            virtualFiles: $virtualFiles,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildMode: MemberDependencyGraphFactoryBuildMode::FULL_BUILD,
            refreshAllVirtualFiles: false,
        );
    }

    /**
     * Refreshes a graph from touched in-memory virtual files and a previous complete build.
     *
     * This entry point is intended for transactional tools that mutate a subset of
     * VirtualPhpSourceFile ASTs and need a complete current build without reading
     * physical files. The current implementation is a conservative full in-memory
     * fallback: it merges touched virtual files into the previous source view and
     * rebuilds the full graph from that merged collection.
     *
     * @param MemberDependencyGraphBuild     $previousBuild       the previous complete member graph build
     * @param VirtualPhpSourceFileCollection $touchedVirtualFiles the AST-mutated virtual files
     */
    public static function refreshFromTouchedVirtualFiles(
        MemberDependencyGraphBuild $previousBuild,
        VirtualPhpSourceFileCollection $touchedVirtualFiles,
    ): MemberDependencyGraphBuild {
        $dependencyGraphIssues = new MemberGraphIssueCollection();
        $mergedVirtualFiles = self::mergeTouchedVirtualFiles($previousBuild->virtualFiles, $touchedVirtualFiles);

        self::refreshStructuralAttributes($touchedVirtualFiles, true);
        $workingSet = new MemberGraphInMemoryRefreshWorkingSetResolver()->resolve(
            previousBuild: $previousBuild,
            touchedVirtualFiles: $touchedVirtualFiles,
        );
        $partialRefreshBuild = new MemberGraphInMemoryRefreshExecutor()->execute(
            previousBuild: $previousBuild,
            mergedVirtualFiles: $mergedVirtualFiles,
            workingSet: $workingSet,
            dependencyGraphIssues: $dependencyGraphIssues,
        );

        if (null !== $partialRefreshBuild) {
            return $partialRefreshBuild;
        }

        return self::buildFromVirtualFiles(
            virtualFiles: $mergedVirtualFiles,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildMode: MemberDependencyGraphFactoryBuildMode::IN_MEMORY_FULL_FALLBACK,
            refreshAllVirtualFiles: false,
            inMemoryRefreshWorkingSet: $workingSet,
        );
    }

    /**
     * Builds a member dependency graph from a complete in-memory virtual-file collection.
     *
     * @param VirtualPhpSourceFileCollection            $virtualFiles              the complete virtual-file collection
     * @param MemberGraphIssueCollection|null           $dependencyGraphIssues     the optional dependency graph issue collection
     * @param MemberDependencyGraphFactoryBuildMode     $buildMode                 the build mode to expose in the build report
     * @param bool                                      $refreshAllVirtualFiles    whether every virtual file must refresh structural attributes
     * @param MemberGraphInMemoryRefreshWorkingSet|null $inMemoryRefreshWorkingSet the in-memory refresh working set when available
     */
    private static function buildFromVirtualFiles(
        VirtualPhpSourceFileCollection $virtualFiles,
        ?MemberGraphIssueCollection $dependencyGraphIssues,
        MemberDependencyGraphFactoryBuildMode $buildMode,
        bool $refreshAllVirtualFiles,
        ?MemberGraphInMemoryRefreshWorkingSet $inMemoryRefreshWorkingSet = null,
    ): MemberDependencyGraphBuild {
        $dependencyGraphIssues ??= new MemberGraphIssueCollection();
        $fileRegistry = new MemberGraphPhpSourceRegistryInstance();
        $knownOwners = $fileRegistry->getKnownOwners();

        self::refreshStructuralAttributes($virtualFiles, $refreshAllVirtualFiles);
        $fileRegistry->registerVirtualFiles($virtualFiles);

        $memberDependencyGraph = new MemberDependencyGraphBuilder(
            fileRegistry: $fileRegistry,
            dependencyGraphIssues: $dependencyGraphIssues,
        )->build(new MemberGraphBuildInput(
            knownOwners: $knownOwners,
            virtualFiles: $virtualFiles,
        ));
        $virtualFileReferences = MemberGraphVirtualFileReferenceCollection::fromVirtualFiles($virtualFiles);

        return new MemberDependencyGraphBuild(
            memberDependencyGraph: $memberDependencyGraph,
            virtualFiles: $virtualFiles,
            virtualFileReferences: $virtualFileReferences,
            knownOwners: $knownOwners,
            dependencyGraphIssues: $dependencyGraphIssues,
            buildReport: self::createInMemoryBuildReport(
                loadedVirtualFileCount: count($virtualFiles),
                virtualFileReferenceCount: count($virtualFileReferences),
                buildMode: $buildMode,
                inMemoryRefreshWorkingSet: $inMemoryRefreshWorkingSet,
            ),
            sourceRegistry: $fileRegistry,
        );
    }

    /**
     * Builds a member dependency graph from directories.
     *
     * @param list<string>                             $directories           base directories to scan
     * @param string                                   $cacheFilePath         cache file path
     * @param list<string>                             $excludedDirectories   directories to exclude
     * @param bool                                     $clearCache            whether the cache must be cleared first
     * @param MemberGraphIssueCollection|null          $dependencyGraphIssues the optional dependency graph issue collection
     * @param MemberDependencyGraphFactoryOptions|null $options               the optional factory behavior flags
     */
    public static function fromDirectory(
        array $directories,
        string $cacheFilePath,
        array $excludedDirectories = [],
        bool $clearCache = false,
        ?MemberGraphIssueCollection $dependencyGraphIssues = null,
        ?MemberDependencyGraphFactoryOptions $options = null,
    ): MemberDependencyGraphBuild {
        $fileRegistry = new MemberGraphPhpSourceRegistryInstance();

        $options ??= new MemberDependencyGraphFactoryOptions();
        $dependencyGraphIssues ??= new MemberGraphIssueCollection();
        $scanner = new MemberGraphPhpFileScanner();
        $directories = $scanner->normalizeDirectories($directories);
        $excludedDirectories = $scanner->normalizeDirectories($excludedDirectories);
        $cache = new MemberGraphCache(
            cacheFilePath: $cacheFilePath,
            directories: $directories,
            clearCache: $clearCache,
            dependencyGraphIssues: $dependencyGraphIssues,
        );
        $files = $scanner->scan($directories, $excludedDirectories);

        return self::fromFiles($fileRegistry, $files, $cache, $options);
    }

    /**
     * Builds a member dependency graph from files and a cache instance.
     *
     * @param list<string>                        $files   files to parse
     * @param MemberGraphCache                    $cache   cache instance
     * @param MemberDependencyGraphFactoryOptions $options the factory behavior flags
     */
    private static function fromFiles(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        array $files,
        MemberGraphCache $cache,
        MemberDependencyGraphFactoryOptions $options,
    ): MemberDependencyGraphBuild {
        $dependencyGraphIssues = $cache->dependencyGraphIssues ?? new MemberGraphIssueCollection();
        $cachePlan = $cache->planForFiles($files);
        $rebuildPlan = MemberDependencyGraphFactoryRebuildPlan::fromCachePlan($cachePlan);
        $partialRebuildInput = new MemberDependencyGraphPartialRebuildInputService()->prepare($cache, $rebuildPlan);
        $partialRebuildPreparedInput = self::preparePartialRebuildInput($fileRegistry, $partialRebuildInput, $cache);
        $partialRebuildWorkingSet = self::resolvePartialRebuildWorkingSet($partialRebuildPreparedInput);

        if (MemberDependencyGraphFactoryRebuildMode::FAST_PATH === $rebuildPlan->mode) {
            return new MemberDependencyGraphFastPathRunner($fileRegistry)->run(
                files: $files,
                cache: $cache,
                cachePlan: $cachePlan,
                rebuildPlan: $rebuildPlan,
                dependencyGraphIssues: $dependencyGraphIssues,
                partialRebuildInput: $partialRebuildInput,
                partialRebuildWorkingSet: $partialRebuildWorkingSet,
            );
        }

        if (
            $options->enablePartialRebuild
            && null !== $partialRebuildInput
            && null !== $partialRebuildPreparedInput
            && null !== $partialRebuildWorkingSet
        ) {
            return new MemberDependencyGraphPartialBuildRunner($fileRegistry)->run(
                files: $files,
                cache: $cache,
                cachePlan: $cachePlan,
                rebuildPlan: $rebuildPlan,
                dependencyGraphIssues: $dependencyGraphIssues,
                partialRebuildInput: $partialRebuildInput,
                preparedInput: $partialRebuildPreparedInput,
                workingSet: $partialRebuildWorkingSet,
            );
        }

        return new MemberDependencyGraphFullBuildRunner($fileRegistry)->run(
            files: $files,
            cache: $cache,
            cachePlan: $cachePlan,
            rebuildPlan: $rebuildPlan,
            dependencyGraphIssues: $dependencyGraphIssues,
            partialRebuildInput: $partialRebuildInput,
            partialRebuildWorkingSet: $partialRebuildWorkingSet,
        );
    }

    /**
     * Prepares partial rebuild input when cached declaration data is available.
     *
     * @param MemberGraphPhpSourceRegistryInstance          $fileRegistry        the file registry
     * @param MemberDependencyGraphPartialRebuildInput|null $partialRebuildInput the prepared partial rebuild input
     * @param MemberGraphCache                              $cache               the member graph cache
     */
    private static function preparePartialRebuildInput(
        MemberGraphPhpSourceRegistryInstance $fileRegistry,
        ?MemberDependencyGraphPartialRebuildInput $partialRebuildInput,
        MemberGraphCache $cache,
    ): ?MemberDependencyGraphPartialRebuildPreparedInput {
        $cachedDeclarationSnapshot = $cache->declarationSnapshot();

        if (null === $partialRebuildInput || null === $cachedDeclarationSnapshot) {
            return null;
        }

        $preparedInput = new MemberDependencyGraphPartialRebuildAssembler($fileRegistry)->assemble(
            partialRebuildInput: $partialRebuildInput,
            cachedDeclarationSnapshot: $cachedDeclarationSnapshot,
        );

        return $preparedInput;
    }

    /**
     * Resolves the dry-run partial rebuild working set when partial rebuild inputs are available.
     *
     * @param MemberDependencyGraphPartialRebuildPreparedInput|null $preparedInput the prepared partial rebuild input
     */
    private static function resolvePartialRebuildWorkingSet(
        ?MemberDependencyGraphPartialRebuildPreparedInput $preparedInput,
    ): ?MemberDependencyGraphPartialRebuildWorkingSet {
        if (null === $preparedInput) {
            return null;
        }

        return new MemberDependencyGraphPartialRebuildWorkingSetResolver()->resolve($preparedInput);
    }

    /**
     * Merges touched virtual files into a previous complete virtual-file collection.
     *
     * @param VirtualPhpSourceFileCollection $previousVirtualFiles the previous complete virtual-file collection
     * @param VirtualPhpSourceFileCollection $touchedVirtualFiles  the touched virtual files to replace or append
     */
    private static function mergeTouchedVirtualFiles(
        VirtualPhpSourceFileCollection $previousVirtualFiles,
        VirtualPhpSourceFileCollection $touchedVirtualFiles,
    ): VirtualPhpSourceFileCollection {
        $mergedVirtualFiles = new VirtualPhpSourceFileCollection();
        $touchedByVirtualFilePath = self::indexVirtualFilesByVirtualFilePath($touchedVirtualFiles);

        foreach ($previousVirtualFiles as $previousVirtualFile) {
            $replacement = $touchedByVirtualFilePath[$previousVirtualFile->virtualFilePath] ?? null;
            $mergedVirtualFiles->add($replacement ?? $previousVirtualFile);
            unset($touchedByVirtualFilePath[$previousVirtualFile->virtualFilePath]);
        }

        foreach ($touchedByVirtualFilePath as $touchedVirtualFile) {
            $mergedVirtualFiles->add($touchedVirtualFile);
        }

        return $mergedVirtualFiles;
    }

    /**
     * Indexes virtual files by their stable virtual-file path.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles the virtual files to index
     *
     * @return array<string, VirtualPhpSourceFile>
     */
    private static function indexVirtualFilesByVirtualFilePath(
        VirtualPhpSourceFileCollection $virtualFiles,
    ): array {
        $indexedVirtualFiles = [];

        foreach ($virtualFiles as $virtualFile) {
            $indexedVirtualFiles[$virtualFile->virtualFilePath] = $virtualFile;
        }

        return $indexedVirtualFiles;
    }

    /**
     * Refreshes structural PHPParser attributes for selected virtual files.
     *
     * @param VirtualPhpSourceFileCollection $virtualFiles           the virtual files to inspect
     * @param bool                           $refreshAllVirtualFiles whether every virtual file must be refreshed
     */
    private static function refreshStructuralAttributes(
        VirtualPhpSourceFileCollection $virtualFiles,
        bool $refreshAllVirtualFiles,
    ): void {
        $structuralNodeIndexBuilder = new StructuralNodeIndexBuilder();

        foreach ($virtualFiles as $virtualFile) {
            if (!$refreshAllVirtualFiles && !$virtualFile->isUpdated()) {
                continue;
            }

            $structuralNodeIndexBuilder->build(array_values($virtualFile->getAst()));
        }
    }

    /**
     * Creates a build report for cache-free in-memory virtual-file builds.
     *
     * @param int                                       $loadedVirtualFileCount    the number of virtual files analyzed
     * @param int                                       $virtualFileReferenceCount the number of virtual file references exposed by the result
     * @param MemberDependencyGraphFactoryBuildMode     $buildMode                 the in-memory build mode
     * @param MemberGraphInMemoryRefreshWorkingSet|null $inMemoryRefreshWorkingSet the in-memory refresh working set when available
     */
    private static function createInMemoryBuildReport(
        int $loadedVirtualFileCount,
        int $virtualFileReferenceCount,
        MemberDependencyGraphFactoryBuildMode $buildMode,
        ?MemberGraphInMemoryRefreshWorkingSet $inMemoryRefreshWorkingSet = null,
    ): MemberDependencyGraphFactoryBuildReport {
        $cachePlan = new MemberGraphCachePlan(
            freshFiles: new MemberGraphCacheFileCollection(),
            staleFiles: new MemberGraphCacheFileCollection(),
            missingFiles: new MemberGraphCacheFileCollection(),
            canUseFastPath: false,
        );
        $rebuildPlan = new MemberDependencyGraphFactoryRebuildPlan(
            mode: MemberDependencyGraphFactoryRebuildMode::FULL_BUILD,
            reason: MemberDependencyGraphFactoryRebuildReason::GLOBAL_INDEX_REBUILD_REQUIRED,
            cachePlan: $cachePlan,
            filesToBuild: new MemberGraphCacheFileCollection(),
            fragmentsToReuse: new MemberGraphCacheFileCollection(),
        );

        return new MemberDependencyGraphFactoryBuildReport(
            buildMode: $buildMode,
            cacheLoadResult: MemberGraphCacheLoadResult::notLoaded(MemberGraphCacheLoadStatus::CACHE_FILE_MISSING),
            cacheWriteResult: MemberGraphCacheWriteResult::notWritten('memory://member-graph'),
            cachePlan: $cachePlan,
            rebuildPlan: $rebuildPlan,
            scannedFileCount: 0,
            loadedVirtualFileCount: $loadedVirtualFileCount,
            virtualFileReferenceCount: $virtualFileReferenceCount,
            inMemoryRefreshWorkingSet: $inMemoryRefreshWorkingSet,
        );
    }
}
