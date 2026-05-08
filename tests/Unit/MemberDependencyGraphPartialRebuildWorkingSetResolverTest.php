<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphPartialGlobalIndexes;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphGlobalIndexRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\GlobalIndexRebuild\MemberGraphLoadedSourceMetadata;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnosticReason;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Input\MemberDependencyGraphPartialRebuildPreparedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\Loading\MemberDependencyGraphPartialRebuildLoadedInput;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\SourceView\MemberDependencyGraphPartialRebuildSourceView;
use PhpNoobs\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSetResolver;
use PhpNoobs\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Application\Cache\VirtualFile\MemberGraphVirtualFileReferenceCollection;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Constant\ClassConstantValueIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodParameterTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Index\Property\PropertyTypeIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsage;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageType;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild working set resolution.
 */
final class MemberDependencyGraphPartialRebuildWorkingSetResolverTest extends TestCase
{
    /**
     * Ensures changed files are parsed and rebuilt while reusable fragments stay outside the rebuild set.
     *
     * @return void
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
     *
     * @return void
     */
    public function testItExpandsWorkingSetWithImpactedReusableFiles(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $runnerVirtualFilePath = $runnerFilePath . '.virtual.0';
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
            virtualFilePath: $changedFilePath . '.virtual.0',
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
     *
     * @return void
     */
    public function testItExpandsWorkingSetUntilImpactsAreClosed(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $workerFilePath = '/project/src/Worker.php';
        $runnerVirtualFilePath = $runnerFilePath . '.virtual.0';
        $workerVirtualFilePath = $workerFilePath . '.virtual.0';
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
            virtualFilePath: $changedFilePath . '.virtual.0',
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
     *
     * @return void
     */
    public function testItExpandsConservativelyWhenImpactedGraphFileCannotBeMapped(): void
    {
        $changedFilePath = '/project/src/Changed.php';
        $runnerFilePath = '/project/src/Runner.php';
        $workerFilePath = '/project/src/Worker.php';
        $runnerVirtualFilePath = $runnerFilePath . '.virtual.0';
        $workerVirtualFilePath = $workerFilePath . '.virtual.0';
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
            virtualFilePath: $changedFilePath . '.virtual.0',
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
     * @param MemberGraphCacheFileCollection $filesToBuild The physical files scheduled for rebuild.
     * @param MemberGraphFragmentCollection $fragmentsToReuse The cached fragments available for reuse.
     * @param MemberGraphDeclarationSnapshot|null $loadedDeclarationSnapshot The loaded declaration snapshot.
     * @param MemberGraphVirtualSourceMetadataCollection|null $allSourceMetadata The complete source metadata view.
     *
     * @return MemberDependencyGraphPartialRebuildPreparedInput
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
     *
     * @return MemberGraphPartialGlobalIndexes
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
     *
     * @return MemberDependencyGraph
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
     * @param MemberUsage $usage The member usage.
     *
     * @return MemberDependencyGraph
     */
    private function graphWithUsage(MemberUsage $usage): MemberDependencyGraph
    {
        return $this->graphWithDeclarationsAndUsages(usages: [$usage]);
    }

    /**
     * Creates a graph containing member declarations and usages.
     *
     * @param list<MemberDeclaration> $declarations The member declarations.
     * @param list<MemberUsage> $usages The member usages.
     *
     * @return MemberDependencyGraph
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
