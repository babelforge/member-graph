<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\PartialGraph\Assembly\MemberDependencyGraphPartialRebuildAssembler;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshotCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild input assembly.
 */
final class MemberDependencyGraphPartialRebuildAssemblerTest extends TestCase
{
    private string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-partial-assembler-'.bin2hex(random_bytes(6));
        mkdir($this->workspace, 0o777, true);
    }

    /**
     * Removes the isolated filesystem workspace.
     */
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);
    }

    /**
     * Ensures partial rebuild data is assembled without executing graph rebuilding.
     */
    public function testItAssemblesPreparedInputForFuturePartialRebuild(): void
    {
        $changedFilePath = $this->workspace.'/Changed.php';
        $reusableFilePath = $this->workspace.'/Reusable.php';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $fragmentsToReuse = new MemberGraphFragmentCollection();
        $cachedSources = new MemberGraphVirtualSourceMetadataCollection();
        $cachedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();

        file_put_contents($changedFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Changed
            {
                public int $value;

                public const VALUE = 'new';

                public function run(int $value): ChangedResult
                {
                }
            }

            final class ChangedResult
            {
            }
            PHP);
        $filesToBuild->add($changedFilePath);
        $fragmentsToReuse->add($reusableFilePath, $this->emptyGraph());
        $this->addCachedSources($cachedSources, $reusableFilePath, $changedFilePath);
        $this->addCachedDeclarations($cachedDeclarationSnapshot, $reusableFilePath, $changedFilePath);

        $preparedInput = new MemberDependencyGraphPartialRebuildAssembler(new MemberGraphPhpSourceRegistryInstance())->assemble(
            partialRebuildInput: new MemberDependencyGraphPartialRebuildInput(
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $fragmentsToReuse,
                globalIndexInputSnapshot: new MemberGraphGlobalIndexInputSnapshot($cachedSources),
                virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
                knownOwners: new KnownOwnerCollection(),
            ),
            cachedDeclarationSnapshot: $cachedDeclarationSnapshot,
        );

        self::assertSame($fragmentsToReuse, $preparedInput->fragmentsToReuse);
        self::assertCount(2, $preparedInput->sourceView->loadedInput->loadedVirtualFiles);
        self::assertCount(3, $preparedInput->sourceView->allSourceMetadata);
        self::assertSame('App\\Changed', $preparedInput->partialGlobalIndexes->knownOwners->get('App\\Changed')?->fqcn);
        self::assertSame(['int'], $preparedInput->partialGlobalIndexes->propertyTypeIndex->get('App\\Changed', 'value')->all());
        self::assertTrue($preparedInput->partialGlobalIndexes->propertyTypeIndex->get('App\\Changed', 'deleted')->isEmpty());
        self::assertSame('new', $preparedInput->partialGlobalIndexes->classConstantValueIndex->get('App\\Changed', 'VALUE'));
        self::assertNull($preparedInput->partialGlobalIndexes->classConstantTypeIndex->get('App\\Changed', 'DELETED'));
        self::assertSame(['App\\ChangedResult'], $preparedInput->partialGlobalIndexes->methodReturnTypeIndex->getReturnType('App\\Changed', 'run')->all());
        self::assertSame(['int'], $preparedInput->partialGlobalIndexes->methodParameterTypeIndex->getType('App\\Changed', 'run', 'value')->all());
        self::assertNull($preparedInput->partialGlobalIndexes->mergedDeclarationSnapshot->methods->get('App\\Changed', 'deleted'));
    }

    /**
     * Adds cached source metadata.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $cachedSources    the cached sources
     * @param string                                     $reusableFilePath the reusable physical file path
     * @param string                                     $changedFilePath  the changed physical file path
     */
    private function addCachedSources(
        MemberGraphVirtualSourceMetadataCollection $cachedSources,
        string $reusableFilePath,
        string $changedFilePath,
    ): void {
        $cachedSources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $reusableFilePath,
            virtualFilePath: $reusableFilePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Reusable',
            ownerKind: OwnerKind::CLASS_,
        ));
        $cachedSources->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\OldChanged',
            ownerKind: OwnerKind::CLASS_,
        ));
    }

    /**
     * Adds cached declarations.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot         the cached declaration snapshot
     * @param string                         $reusableFilePath the reusable physical file path
     * @param string                         $changedFilePath  the changed physical file path
     */
    private function addCachedDeclarations(
        MemberGraphDeclarationSnapshot $snapshot,
        string $reusableFilePath,
        string $changedFilePath,
    ): void {
        $parameters = new ParameterDeclarationSnapshotCollection();
        $parameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\Reusable::run',
            name: 'label',
            nativeType: 'string',
        ));
        $snapshot->owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Reusable',
            kind: OwnerKind::CLASS_,
            fullFilePath: $reusableFilePath,
            virtualFilePath: $reusableFilePath.'.virtual.0',
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Reusable',
            name: 'run',
            fullFilePath: $reusableFilePath,
            virtualFilePath: $reusableFilePath.'.virtual.0',
            nativeReturnType: 'App\\ReusableResult',
            parameters: $parameters,
        ));
        $snapshot->owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Changed',
            kind: OwnerKind::CLASS_,
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'deleted',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            nativeReturnType: 'string',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'deleted',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            nativeType: 'string',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'DELETED',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            scalarValue: 'old',
        ));
    }

    /**
     * Creates an empty member dependency graph.
     */
    private function emptyGraph(): MemberDependencyGraph
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

    /**
     * Removes a directory recursively.
     *
     * @param string $directory the directory to remove
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
