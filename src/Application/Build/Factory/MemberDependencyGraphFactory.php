<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Build\Factory;

use PhpNoobs\MemberGraph\Application\Build\Factory\Mode\MemberDependencyGraphFactoryRebuildMode;
use PhpNoobs\MemberGraph\Application\Build\Factory\Plan\MemberDependencyGraphFactoryRebuildPlan;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphFastPathRunner;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphFullBuildRunner;
use PhpNoobs\MemberGraph\Application\Build\Factory\Runner\MemberDependencyGraphPartialBuildRunner;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Assembly\MemberDependencyGraphPartialRebuildAssembler;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInputService;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSetResolver;
use PhpNoobs\MemberGraph\Application\Build\Source\MemberGraphPhpFileScanner;
use PhpNoobs\MemberGraph\Application\Cache\Core\MemberGraphCache;
use PhpNoobs\MemberGraph\Application\Issue\MemberGraphIssueCollection;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;

/**
 * Builds member dependency graphs from project directories.
 */
final readonly class MemberDependencyGraphFactory
{
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
            return new MemberDependencyGraphFastPathRunner()->run(
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
}
