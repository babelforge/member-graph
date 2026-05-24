<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexes;
use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInput;
use BabelForge\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnosticReason;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoadedInput;
use BabelForge\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSetResolver;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use BabelForge\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use BabelForge\MemberGraph\Domain\Availability\AvailableMemberCollection;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclaration;
use BabelForge\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use BabelForge\MemberGraph\Domain\Graph\MemberDependencyGraph;
use BabelForge\MemberGraph\Domain\Graph\MemberId;
use BabelForge\MemberGraph\Domain\Graph\MemberType;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use BabelForge\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use BabelForge\MemberGraph\Domain\Owner\KnownOwnerCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use BabelForge\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsage;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageCollection;
use BabelForge\MemberGraph\Domain\Usage\MemberUsageType;
use BabelForge\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild working set resolution.
 */
final class MemberDependencyGraphPartialRebuildWorkingSetResolverTest extends TestCase
{
    /**
     * Ensures changed files are parsed and rebuilt while reusable fragments stay outside the rebuild set.
     */
    public function testItBuildsInitialWorkingSetFromChangedFiles(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $reusableFilePath = '/project/src/Reusable.php';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $fragmentsToReuse = new MemberGraphFragmentCollection();
        $changedFragment = $this->emptyGraph();
        $reusableFragment = $this->emptyGraph();

        $filesToBuild->add($changedFilePath);
        $filesToBuild->add($changedFilePath);
        $fragmentsToReuse->add($changedFilePath, $changedFragment);
        $fragmentsToReuse->add($reusableFilePath, $reusableFragment);

        $workingSet = new MemberDependencyGraphPartialRebuildWorkingSetResolver()->resolve(
            $this->preparedInput($filesToBuild, $fragmentsToReuse),
        );

        self::assertTrue($workingSet->hasFileToParseForContext($changedFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($changedFilePath));
        self::assertCount(1, $workingSet->filesToParseForContext);
        self::assertCount(1, $workingSet->filesToRebuildGraph);
        self::assertSame(1, $workingSet->iterations);
        self::assertFalse($workingSet->hasDiagnostics());
        self::assertNull($workingSet->fragmentsToReuse->get($changedFilePath));
        self::assertSame($reusableFragment, $workingSet->fragmentsToReuse->get($reusableFilePath));
        self::assertCount(1, $workingSet->fragmentsToReuse);
    }

    /**
     * Ensures files impacted by loaded declarations are added to the graph rebuild set.
     */
    public function testItExpandsWorkingSetWithImpactedReusableFiles(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $runnerVirtualFilePath = $runnerFilePath.'.virtual.0';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $fragmentsToReuse = new MemberGraphFragmentCollection();
        $loadedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();
        $allSourceMetadata = new MemberGraphVirtualSourceMetadataCollection();

        $filesToBuild->add($changedFilePath);
        $fragmentsToReuse->add($runnerFilePath, $this->graphWithUsage(new MemberUsage(
            sourceSymbol: 'App\\Runner::run',
            target: new MemberId('App\\Changed', 'send', MemberType::METHOD),
            type: MemberUsageType::METHOD_CALL,
            file: $runnerVirtualFilePath,
        )));
        $loadedDeclarationSnapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'send',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
        ));
        $allSourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $runnerFilePath,
            virtualFilePath: $runnerVirtualFilePath,
            namespace: 'App',
            ownerName: 'App\\Runner',
            ownerKind: OwnerKind::CLASS_,
        ));

        $workingSet = new MemberDependencyGraphPartialRebuildWorkingSetResolver()->resolve(
            $this->preparedInput(
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $fragmentsToReuse,
                loadedDeclarationSnapshot: $loadedDeclarationSnapshot,
                allSourceMetadata: $allSourceMetadata,
            ),
        );

        self::assertTrue($workingSet->hasFileToParseForContext($changedFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($changedFilePath));
        self::assertTrue($workingSet->hasFileToParseForContext($runnerFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($runnerFilePath));
        self::assertCount(2, $workingSet->filesToParseForContext);
        self::assertCount(2, $workingSet->filesToRebuildGraph);
        self::assertSame(2, $workingSet->iterations);
        self::assertNull($workingSet->fragmentsToReuse->get($runnerFilePath));
    }

    /**
     * Ensures impact expansion continues until newly impacted files no longer reveal more impacted files.
     */
    public function testItExpandsWorkingSetUntilImpactsAreClosed(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $workerFilePath = '/project/src/Worker.php';
        $runnerVirtualFilePath = $runnerFilePath.'.virtual.0';
        $workerVirtualFilePath = $workerFilePath.'.virtual.0';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $fragmentsToReuse = new MemberGraphFragmentCollection();
        $loadedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();
        $allSourceMetadata = new MemberGraphVirtualSourceMetadataCollection();

        $filesToBuild->add($changedFilePath);
        $fragmentsToReuse->add($runnerFilePath, $this->graphWithDeclarationsAndUsages(
            declarations: [
                new MemberDeclaration(
                    id: new MemberId('App\\Runner', 'run', MemberType::METHOD),
                    file: $runnerVirtualFilePath,
                ),
            ],
            usages: [
                new MemberUsage(
                    sourceSymbol: 'App\\Runner::run',
                    target: new MemberId('App\\Changed', 'send', MemberType::METHOD),
                    type: MemberUsageType::METHOD_CALL,
                    file: $runnerVirtualFilePath,
                ),
            ],
        ));
        $fragmentsToReuse->add($workerFilePath, $this->graphWithUsage(new MemberUsage(
            sourceSymbol: 'App\\Worker::work',
            target: new MemberId('App\\Runner', 'run', MemberType::METHOD),
            type: MemberUsageType::METHOD_CALL,
            file: $workerVirtualFilePath,
        )));
        $loadedDeclarationSnapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'send',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
        ));
        $allSourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $runnerFilePath,
            virtualFilePath: $runnerVirtualFilePath,
            namespace: 'App',
            ownerName: 'App\\Runner',
            ownerKind: OwnerKind::CLASS_,
        ));
        $allSourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: $workerFilePath,
            virtualFilePath: $workerVirtualFilePath,
            namespace: 'App',
            ownerName: 'App\\Worker',
            ownerKind: OwnerKind::CLASS_,
        ));

        $workingSet = new MemberDependencyGraphPartialRebuildWorkingSetResolver()->resolve(
            $this->preparedInput(
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $fragmentsToReuse,
                loadedDeclarationSnapshot: $loadedDeclarationSnapshot,
                allSourceMetadata: $allSourceMetadata,
            ),
        );

        self::assertTrue($workingSet->hasFileToRebuildGraph($changedFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($runnerFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($workerFilePath));
        self::assertCount(3, $workingSet->filesToRebuildGraph);
        self::assertSame(3, $workingSet->iterations);
        self::assertCount(0, $workingSet->fragmentsToReuse);
    }

    /**
     * Ensures unresolved impacted graph file paths trigger an explicit conservative expansion.
     */
    public function testItExpandsConservativelyWhenImpactedGraphFileCannotBeMapped(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $workerFilePath = '/project/src/Worker.php';
        $runnerVirtualFilePath = $runnerFilePath.'.virtual.0';
        $workerVirtualFilePath = $workerFilePath.'.virtual.0';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $fragmentsToReuse = new MemberGraphFragmentCollection();
        $loadedDeclarationSnapshot = new MemberGraphDeclarationSnapshot();

        $filesToBuild->add($changedFilePath);
        $fragmentsToReuse->add($runnerFilePath, $this->graphWithUsage(new MemberUsage(
            sourceSymbol: 'App\\Runner::run',
            target: new MemberId('App\\Changed', 'send', MemberType::METHOD),
            type: MemberUsageType::METHOD_CALL,
            file: $runnerVirtualFilePath,
        )));
        $fragmentsToReuse->add($workerFilePath, $this->graphWithUsage(new MemberUsage(
            sourceSymbol: 'App\\Worker::work',
            target: new MemberId('App\\Changed', 'send', MemberType::METHOD),
            type: MemberUsageType::METHOD_CALL,
            file: $workerVirtualFilePath,
        )));
        $loadedDeclarationSnapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Changed',
            name: 'send',
            fullFilePath: $changedFilePath,
            virtualFilePath: $changedFilePath.'.virtual.0',
        ));

        $workingSet = new MemberDependencyGraphPartialRebuildWorkingSetResolver()->resolve(
            $this->preparedInput(
                filesToBuild: $filesToBuild,
                fragmentsToReuse: $fragmentsToReuse,
                loadedDeclarationSnapshot: $loadedDeclarationSnapshot,
            ),
        );

        self::assertTrue($workingSet->hasFileToRebuildGraph($changedFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($runnerFilePath));
        self::assertTrue($workingSet->hasFileToRebuildGraph($workerFilePath));
        self::assertCount(3, $workingSet->filesToRebuildGraph);
        self::assertCount(0, $workingSet->fragmentsToReuse);
        self::assertTrue($workingSet->hasDiagnostics());
        self::assertSame(
            [
                MemberDependencyGraphPartialRebuildClosureDiagnosticReason::UNRESOLVED_REFERENCE,
                MemberDependencyGraphPartialRebuildClosureDiagnosticReason::CONSERVATIVE_EXPANSION,
            ],
            array_map(
                static fn ($diagnostic): MemberDependencyGraphPartialRebuildClosureDiagnosticReason => $diagnostic->reason,
                $workingSet->diagnostics->all(),
            ),
        );
    }

    /**
     * Creates a prepared partial rebuild input.
     *
     * @param MemberGraphCacheFileCollection                  $filesToBuild              the physical files scheduled for rebuild
     * @param MemberGraphFragmentCollection                   $fragmentsToReuse          the cached fragments available for reuse
     * @param MemberGraphDeclarationSnapshot|null             $loadedDeclarationSnapshot the loaded declaration snapshot
     * @param MemberGraphVirtualSourceMetadataCollection|null $allSourceMetadata         the complete source metadata view
     */
    private function preparedInput(
        MemberGraphCacheFileCollection $filesToBuild,
        MemberGraphFragmentCollection $fragmentsToReuse,
        ?MemberGraphDeclarationSnapshot $loadedDeclarationSnapshot = null,
        ?MemberGraphVirtualSourceMetadataCollection $allSourceMetadata = null,
    ): MemberDependencyGraphPartialRebuildPreparedInput {
        $knownOwners = new KnownOwnerCollection();
        $virtualFileReferences = new MemberGraphVirtualFileReferenceCollection();
        $globalIndexInputSnapshot = new MemberGraphGlobalIndexInputSnapshot();
        $partialRebuildInput = new MemberDependencyGraphPartialRebuildInput(
            filesToBuild: $filesToBuild,
            fragmentsToReuse: $fragmentsToReuse,
            globalIndexInputSnapshot: $globalIndexInputSnapshot,
            virtualFileReferences: $virtualFileReferences,
            knownOwners: $knownOwners,
        );

        return new MemberDependencyGraphPartialRebuildPreparedInput(
            partialRebuildInput: $partialRebuildInput,
            sourceView: new MemberDependencyGraphPartialRebuildSourceView(
                globalIndexRebuildInput: new MemberGraphGlobalIndexRebuildInput(
                    reusableSources: new MemberGraphVirtualSourceMetadataCollection(),
                    filesToBuild: $filesToBuild,
                    fragmentsToReuse: $fragmentsToReuse,
                    knownOwners: $knownOwners,
                    virtualFileReferences: $virtualFileReferences,
                ),
                loadedInput: new MemberDependencyGraphPartialRebuildLoadedInput(
                    loadedVirtualFiles: new VirtualPhpSourceFileCollection(),
                    loadedDeclarationSnapshot: $loadedDeclarationSnapshot ?? new MemberGraphDeclarationSnapshot(),
                    loadedSourceMetadata: new MemberGraphLoadedSourceMetadata(),
                ),
                allSourceMetadata: $allSourceMetadata ?? new MemberGraphVirtualSourceMetadataCollection(),
            ),
            partialGlobalIndexes: $this->partialGlobalIndexes(),
            fragmentsToReuse: $fragmentsToReuse,
        );
    }

    /**
     * Creates empty partial-compatible global indexes.
     */
    private function partialGlobalIndexes(): MemberGraphPartialGlobalIndexes
    {
        return new MemberGraphPartialGlobalIndexes(
            knownOwners: new KnownOwnerCollection(),
            polymorphicImplementationsIndex: new PolymorphicImplementationsIndex(),
            propertyTypeIndex: new PropertyTypeIndex(),
            classConstantTypeIndex: new ClassConstantTypeIndex(),
            classConstantValueIndex: new ClassConstantValueIndex(),
            methodReturnTypeIndex: new MethodReturnTypeIndex(),
            methodParameterTypeIndex: new MethodParameterTypeIndex(),
            functionReturnTypeIndex: new FunctionReturnTypeIndex(),
            functionParameterTypeIndex: new FunctionParameterTypeIndex(),
            mergedDeclarationSnapshot: new MemberGraphDeclarationSnapshot(),
        );
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
     * Creates a graph containing one member usage.
     *
     * @param MemberUsage $usage the member usage
     */
    private function graphWithUsage(MemberUsage $usage): MemberDependencyGraph
    {
        return $this->graphWithDeclarationsAndUsages(usages: [$usage]);
    }

    /**
     * Creates a graph containing member declarations and usages.
     *
     * @param list<MemberDeclaration> $declarations the member declarations
     * @param list<MemberUsage>       $usages       the member usages
     */
    private function graphWithDeclarationsAndUsages(
        array $declarations = [],
        array $usages = [],
    ): MemberDependencyGraph {
        $memberDeclarations = new MemberDeclarationCollection();
        $memberUsages = new MemberUsageCollection();

        foreach ($declarations as $declaration) {
            $memberDeclarations->add($declaration);
        }

        foreach ($usages as $usage) {
            $memberUsages->add($usage);
        }

        return new MemberDependencyGraph(
            declarations: $memberDeclarations,
            usages: $memberUsages,
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }
}
