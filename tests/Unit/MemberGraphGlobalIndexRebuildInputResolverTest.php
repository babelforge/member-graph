<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInputResolver;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers global-index rebuild input resolution.
 */
final class MemberGraphGlobalIndexRebuildInputResolverTest extends TestCase
{
    /**
     * Ensures reusable sources exclude metadata belonging to files that must be rebuilt.
     */
    public function testItExcludesSourcesForFilesToBuild(): void
    {
        $freshFilePath = '/project/src/Fresh.php';
        $changedFilePath = '/project/src/Changed.php';
        $sources = new MemberGraphVirtualSourceMetadataCollection();
        $filesToBuild = $this->files($changedFilePath);
        $fragmentsToReuse = $this->fragments($freshFilePath);
        $knownOwners = new KnownOwnerCollection();
        $virtualFileReferences = new MemberGraphVirtualFileReferenceCollection();

        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $freshFilePath,
            virtualFilePath: $freshFilePath.'.virtual.0',
        ));
        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
        ));

        $input = new MemberGraphGlobalIndexRebuildInputResolver()->resolve(new MemberDependencyGraphPartialRebuildInput(
            filesToBuild: $filesToBuild,
            fragmentsToReuse: $fragmentsToReuse,
            globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot($sources),
            virtualFileReferences: $virtualFileReferences,
            knownOwners: $knownOwners,
        ));

        self::assertCount(1, $input->reusableSources);
        self::assertNotNull($input->reusableSources->get($freshFilePath.'.virtual.0'));
        self::assertNull($input->reusableSources->get($changedFilePath.'.virtual.0'));
        self::assertSame($filesToBuild, $input->filesToBuild);
        self::assertSame($fragmentsToReuse, $input->fragmentsToReuse);
        self::assertSame($knownOwners, $input->knownOwners);
        self::assertSame($virtualFileReferences, $input->virtualFileReferences);
    }

    /**
     * Ensures every snapshot source remains reusable when no file is scheduled for rebuild.
     */
    public function testItKeepsAllSourcesWhenNoFilesAreScheduledForBuild(): void
    {
        $firstFilePath = '/project/src/First.php';
        $secondFilePath = '/project/src/Second.php';
        $sources = new MemberGraphVirtualSourceMetadataCollection();

        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $firstFilePath,
            virtualFilePath: $firstFilePath.'.virtual.0',
        ));
        $sources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $secondFilePath,
            virtualFilePath: $secondFilePath.'.virtual.0',
        ));

        $input = new MemberGraphGlobalIndexRebuildInputResolver()->resolve(new MemberDependencyGraphPartialRebuildInput(
            filesToBuild: new MemberGraphCacheFileCollection(),
            fragmentsToReuse: new MemberGraphFragmentCollection(),
            globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot($sources),
            virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
            knownOwners: new KnownOwnerCollection(),
        ));

        self::assertCount(2, $input->reusableSources);
        self::assertNotNull($input->reusableSources->get($firstFilePath.'.virtual.0'));
        self::assertNotNull($input->reusableSources->get($secondFilePath.'.virtual.0'));
    }

    /**
     * Creates a cache file collection.
     *
     * @param string ...$filePaths The file paths.
     */
    private function files(string ...$filePaths): MemberGraphCacheFileCollection
    {
        $files = new MemberGraphCacheFileCollection();

        foreach ($filePaths as $filePath) {
            $files->add($filePath);
        }

        return $files;
    }

    /**
     * Creates a graph fragment collection.
     *
     * @param string ...$filePaths The file paths.
     */
    private function fragments(string ...$filePaths): MemberGraphFragmentCollection
    {
        $fragments = new MemberGraphFragmentCollection();

        foreach ($filePaths as $filePath) {
            $fragments->add($filePath, $this->graph());
        }

        return $fragments;
    }

    /**
     * Creates an empty graph fragment.
     */
    private function graph(): MemberDependencyGraph
    {
        return new MemberDependencyGraph(
            declarations: new MemberDeclarationCollection(),
            usages: new MemberUsageCollection(),
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }
}
