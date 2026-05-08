<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexesBuilder;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoadedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial-compatible global index assembly.
 */
final class MemberGraphPartialGlobalIndexesBuilderTest extends TestCase
{
    /**
     * Ensures available node-free global indexes are assembled from source metadata and declaration snapshots.
     */
    public function testItBuildsPartialGlobalIndexesFromSourceViewAndCachedDeclarations(): void
    {
        $reusableFilePath = '/project/src/Reusable.php';
        $changedFilePath = '/project/src/Changed.php';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $allSourceMetadata = new MemberGraphVirtualSourceMetadataCollection();
        $cachedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();
        $loadedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();

        $filesToBuild->add($changedFilePath);
        $this->addReusableSourceMetadata($allSourceMetadata, $reusableFilePath);
        $this->addChangedSourceMetadata($allSourceMetadata, $changedFilePath);
        $this->addCachedDeclarations($cachedDeclarationSnapshot, $reusableFilePath, $changedFilePath);
        $this->addLoadedDeclarations($loadedDeclarationSnapshot, $changedFilePath);

        $indexes = new MemberGraphPartialGlobalIndexesBuilder()->build(
            sourceView: new MemberDependencyGraphPartialRebuildSourceView(
                globalIndexRebuildInput: new MemberGraphGlobalIndexRebuildInput(
                    reusableSources: new MemberGraphVirtualSourceMetadataCollection(),
                    filesToBuild: $filesToBuild,
                    fragmentsToReuse: new MemberGraphFragmentCollection(),
                    knownOwners: new KnownOwnerCollection(),
                    virtualFileReferences: new MemberGraphVirtualFileReferenceCollection(),
                ),
                loadedInput: new MemberDependencyGraphPartialRebuildLoadedInput(
                    loadedVirtualFiles: new VirtualPhpSourceFileCollection(),
                    loadedDeclarationSnapshot: $loadedDeclarationSnapshot,
                    loadedSourceMetadata: new MemberGraphLoadedSourceMetadata(),
                ),
                allSourceMetadata: $allSourceMetadata,
            ),
            cachedDeclarationSnapshot: $cachedDeclarationSnapshot,
        );

        self::assertCount(4, $indexes->knownOwners);
        self::assertSame('App\\ChangedParentNew', $indexes->knownOwners->get('App\\Changed')?->parentFqcn);
        self::assertSame(
            ['App\\Changed'],
            $indexes->polymorphicImplementationsIndex->getImplementations('App\\ChangedParentNew'),
        );
        self::assertSame(['string'], $indexes->propertyTypeIndex->get('App\\Reusable', 'label')->all());
        self::assertSame(['int'], $indexes->propertyTypeIndex->get('App\\Changed', 'value')->all());
        self::assertTrue($indexes->propertyTypeIndex->get('App\\Changed', 'deleted')->isEmpty());
        self::assertSame('App\\Changed', $indexes->classConstantTypeIndex->get('App\\Changed', 'VALUE'));
        self::assertSame('new', $indexes->classConstantValueIndex->get('App\\Changed', 'VALUE'));
        self::assertNull($indexes->classConstantTypeIndex->get('App\\Changed', 'DELETED'));
        self::assertSame(['App\\ReusableResult'], $indexes->methodReturnTypeIndex->getReturnType('App\\Reusable', 'run')->all());
        self::assertSame(['string'], $indexes->methodParameterTypeIndex->getType('App\\Reusable', 'run', 'label')->all());
        self::assertSame(['App\\ChangedResult'], $indexes->methodReturnTypeIndex->getReturnType('App\\Changed', 'run')->all());
        self::assertSame(['int'], $indexes->methodParameterTypeIndex->getType('App\\Changed', 'run', 'value')->all());
        self::assertTrue($indexes->methodReturnTypeIndex->getReturnType('App\\Changed', 'deleted')->isEmpty());
        self::assertNotNull($indexes->mergedDeclarationSnapshot->methods->get('App\\Reusable', 'run'));
        self::assertNotNull($indexes->mergedDeclarationSnapshot->methods->get('App\\Changed', 'run'));
        self::assertNull($indexes->mergedDeclarationSnapshot->methods->get('App\\Changed', 'deleted'));
    }

    /**
     * Adds reusable source metadata.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata the source metadata collection
     * @param string                                     $filePath       the reusable file path
     */
    private function addReusableSourceMetadata(
        MemberGraphVirtualSourceMetadataCollection $sourceMetadata,
        string $filePath,
    ): void {
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $filePath,
            virtualFilePath: $filePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Reusable',
            ownerKind: OwnerKind::CLASS_,
        ));
    }

    /**
     * Adds changed source metadata.
     *
     * @param MemberGraphVirtualSourceMetadataCollection $sourceMetadata the source metadata collection
     * @param string                                     $filePath       the changed file path
     */
    private function addChangedSourceMetadata(
        MemberGraphVirtualSourceMetadataCollection $sourceMetadata,
        string $filePath,
    ): void {
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $filePath,
            virtualFilePath: $filePath.'.virtual.0',
            namespace: 'App',
            ownerName: 'App\\ChangedParentNew',
            ownerKind: OwnerKind::CLASS_,
            isAbstract: true,
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $filePath,
            virtualFilePath: $filePath.'.virtual.1',
            namespace: 'App',
            ownerName: 'App\\Changed',
            ownerKind: OwnerKind::CLASS_,
            parentFqcn: 'App\\ChangedParentNew',
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $filePath,
            virtualFilePath: $filePath.'.virtual.2',
            namespace: 'App',
            ownerName: 'App\\ChangedContract',
            ownerKind: OwnerKind::INTERFACE,
        ));
    }

    /**
     * Adds cached declarations.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot         the cached declaration snapshot
     * @param string                         $reusableFilePath the reusable file path
     * @param string                         $changedFilePath  the changed file path
     */
    private function addCachedDeclarations(
        MemberGraphDeclarationSnapshot $snapshot,
        string $reusableFilePath,
        string $changedFilePath,
    ): void {
        $reusableParameters = new ParameterDeclarationSnapshotCollection();
        $reusableParameters->add(new ParameterDeclarationSnapshot(
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
            parameters: $reusableParameters,
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Reusable',
            name: 'label',
            fullFilePath: $reusableFilePath,
            virtualFilePath: $reusableFilePath.'.virtual.0',
            nativeType: 'string',
        ));
        $snapshot->owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Changed',
            kind: OwnerKind::CLASS_,
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            parentFqcn: 'App\\ChangedParentOld',
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'deleted',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
            nativeReturnType: 'App\\DeletedResult',
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
     * Adds loaded declarations.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot        the loaded declaration snapshot
     * @param string                         $changedFilePath the changed file path
     */
    private function addLoadedDeclarations(MemberGraphDeclarationSnapshot $snapshot, string $changedFilePath): void
    {
        $changedParameters = new ParameterDeclarationSnapshotCollection();
        $changedParameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\Changed::run',
            name: 'value',
            nativeType: 'int',
        ));

        $snapshot->owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Changed',
            kind: OwnerKind::CLASS_,
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.1',
            parentFqcn: 'App\\ChangedParentNew',
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'run',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.1',
            nativeReturnType: 'App\\ChangedResult',
            parameters: $changedParameters,
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'value',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.1',
            nativeType: 'int',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'VALUE',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.1',
            scalarValue: 'new',
        ));
    }
}
