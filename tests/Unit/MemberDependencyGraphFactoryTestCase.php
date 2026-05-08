<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphBuild;
use PhpNoobs\MemberGraph\Domain\Availability\AvailableMemberCollection;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclaration;
use PhpNoobs\MemberGraph\Domain\Declaration\MemberDeclarationCollection;
use PhpNoobs\MemberGraph\Domain\Graph\MemberDependencyGraph;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Polymorphism\PolymorphicImplementationsIndex;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Parameter\ParameterUsageCollection;
use PhpNoobs\MemberGraph\Domain\Usage\MemberUsageCollection;
use PHPUnit\Framework\TestCase;

/**
 * Provides shared fixtures and assertions for member dependency graph factory tests.
 */
abstract class MemberDependencyGraphFactoryTestCase extends TestCase
{
    protected string $workspace;

    /**
     * Prepares an isolated filesystem workspace.
     */
    protected function setUp(): void
    {
        $this->workspace = sys_get_temp_dir().'/member-graph-factory-'.bin2hex(random_bytes(6));
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
     * Creates a graph fragment for cache tests.
     *
     * @param MemberId $member   the declared member
     * @param string   $filePath the declaration file path
     */
    protected function createGraphFragment(MemberId $member, string $filePath): MemberDependencyGraph
    {
        $declarations = new MemberDeclarationCollection();
        $declarations->add(new MemberDeclaration($member, $filePath));

        return new MemberDependencyGraph(
            declarations: $declarations,
            usages: new MemberUsageCollection(),
            parameterUsages: new ParameterUsageCollection(),
            availableMembers: new AvailableMemberCollection(),
            knownOwners: new KnownOwnerCollection(),
            interfaceImplementationsIndex: new PolymorphicImplementationsIndex(),
            dependencyGraphIssues: null,
        );
    }

    /**
     * Returns sorted declaration hashes.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     *
     * @return list<string>
     */
    protected function declarationHashes(MemberDependencyGraph $graph): array
    {
        $hashes = array_map(
            static fn (MemberDeclaration $declaration): string => $declaration->id->hash(),
            $graph->declarations->all(),
        );

        sort($hashes);

        return $hashes;
    }

    /**
     * Returns sorted member usage signatures.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     *
     * @return list<string>
     */
    protected function usageSignatures(MemberDependencyGraph $graph): array
    {
        $signatures = [];

        foreach ($graph->usages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $signatures[] = implode('|', [
                    $usage->sourceSymbol,
                    $usage->target->hash(),
                    $usage->type->name,
                    basename($usage->file),
                ]);
            }
        }

        sort($signatures);

        return $signatures;
    }

    /**
     * Returns sorted parameter usage signatures.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     *
     * @return list<string>
     */
    protected function parameterUsageSignatures(MemberDependencyGraph $graph): array
    {
        $signatures = [];

        foreach ($graph->parameterUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                $signatures[] = implode('|', [
                    $usage->sourceSymbol,
                    $usage->target->hash(),
                    $usage->type->name,
                    basename($usage->file),
                ]);
            }
        }

        sort($signatures);

        return $signatures;
    }

    /**
     * Indicates whether parameter usages contain a parameter name.
     *
     * @param MemberDependencyGraph $graph         the graph to inspect
     * @param string                $parameterName the parameter name to find
     */
    protected function parameterUsagesContainName(MemberDependencyGraph $graph, string $parameterName): bool
    {
        foreach ($graph->parameterUsages->all() as $usagesByTarget) {
            foreach ($usagesByTarget as $usage) {
                if ($parameterName === $usage->target->parameterName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Writes the fixture files used by property type dispatch partial-build tests.
     *
     * @param string $srcDirectory          the source directory
     * @param string $aFilePath             the consumer file path
     * @param string $holderFilePath        the holder file path
     * @param string $oldServiceFilePath    the old service file path
     * @param string $newServiceFilePath    the new service file path
     * @param bool   $holderUsesNewService  whether the holder uses the new service type
     * @param bool   $usePhpDocPropertyType whether the holder uses PHPDoc instead of native property type
     */
    protected function writePropertyTypeDispatchFiles(
        string $srcDirectory,
        string $aFilePath,
        string $holderFilePath,
        string $oldServiceFilePath,
        string $newServiceFilePath,
        bool $holderUsesNewService,
        bool $usePhpDocPropertyType,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(Holder $holder): void
                {
                    $holder->service->send();
                }
            }
            PHP);
        file_put_contents($oldServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class OldService
            {
                public function send(): void
                {
                }
            }
            PHP);
        file_put_contents($newServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class NewService
            {
                public function send(): void
                {
                }
            }
            PHP);
        $this->writeHolderWithPropertyType($holderFilePath, $holderUsesNewService, $usePhpDocPropertyType);
    }

    /**
     * Writes the holder fixture with either an old or a new property type.
     *
     * @param string $holderFilePath        the holder file path
     * @param bool   $holderUsesNewService  whether the holder uses the new service type
     * @param bool   $usePhpDocPropertyType whether the holder uses PHPDoc instead of native property type
     */
    protected function writeHolderWithPropertyType(
        string $holderFilePath,
        bool $holderUsesNewService,
        bool $usePhpDocPropertyType,
    ): void {
        $serviceClass = $holderUsesNewService ? 'NewService' : 'OldService';
        $propertyDeclaration = $usePhpDocPropertyType
            ? <<<PHP
                    /** @var {$serviceClass} */
                    public object \$service;
                PHP
            : <<<PHP
                    public {$serviceClass} \$service;
                PHP;

        file_put_contents($holderFilePath, <<<PHP
            <?php

            namespace App;

            final class Holder
            {
            {$propertyDeclaration}

                public function __construct()
                {
                    \$this->service = new {$serviceClass}();
                }
            }
            PHP);
    }

    /**
     * Writes the fixture files used by factory return dispatch partial-build tests.
     *
     * @param string $srcDirectory             the source directory
     * @param string $aFilePath                the consumer file path
     * @param string $factoryFilePath          the factory file path
     * @param string $oldServiceFilePath       the old service file path
     * @param string $newServiceFilePath       the new service file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     * @param bool   $useGenericListReturn     whether the factory returns a generic list
     */
    protected function writeFactoryReturnDispatchFiles(
        string $srcDirectory,
        string $aFilePath,
        string $factoryFilePath,
        string $oldServiceFilePath,
        string $newServiceFilePath,
        bool $factoryReturnsNewService,
        bool $useGenericListReturn,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        $consumerCode = $useGenericListReturn
            ? <<<'PHP'
                <?php

                namespace App;

                final class A
                {
                    public function run(): void
                    {
                        foreach (Factory::all() as $service) {
                            $service->send();
                        }
                    }
                }
                PHP
            : <<<'PHP'
                <?php

                namespace App;

                final class A
                {
                    public function run(): void
                    {
                        Factory::make()->send();
                    }
                }
                PHP;

        file_put_contents($aFilePath, $consumerCode);
        file_put_contents($oldServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class OldService
            {
                public function send(): void
                {
                }
            }
            PHP);
        file_put_contents($newServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class NewService
            {
                public function send(): void
                {
                }
            }
            PHP);
        $this->writeFactoryWithPhpDocReturn($factoryFilePath, $factoryReturnsNewService, $useGenericListReturn);
    }

    /**
     * Writes the factory fixture with either an old or a new PHPDoc return type.
     *
     * @param string $factoryFilePath          the factory file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     * @param bool   $useGenericListReturn     whether the factory returns a generic list
     */
    protected function writeFactoryWithPhpDocReturn(
        string $factoryFilePath,
        bool $factoryReturnsNewService,
        bool $useGenericListReturn,
    ): void {
        $serviceClass = $factoryReturnsNewService ? 'NewService' : 'OldService';

        if ($useGenericListReturn) {
            file_put_contents($factoryFilePath, <<<PHP
                <?php

                namespace App;

                final class Factory
                {
                    /**
                     * @return list<{$serviceClass}>
                     */
                    public static function all(): array
                    {
                        return [new {$serviceClass}()];
                    }
                }
                PHP);

            return;
        }

        file_put_contents($factoryFilePath, <<<PHP
            <?php

            namespace App;

            final class Factory
            {
                /**
                 * @return {$serviceClass}
                 */
                public static function make(): object
                {
                    return new {$serviceClass}();
                }
            }
            PHP);
    }

    /**
     * Writes the fixture files used by nullable return dispatch partial-build tests.
     *
     * @param string $srcDirectory             the source directory
     * @param string $aFilePath                the consumer file path
     * @param string $factoryFilePath          the factory file path
     * @param string $oldServiceFilePath       the old service file path
     * @param string $newServiceFilePath       the new service file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeNullableReturnDispatchFiles(
        string $srcDirectory,
        string $aFilePath,
        string $factoryFilePath,
        string $oldServiceFilePath,
        string $newServiceFilePath,
        bool $factoryReturnsNewService,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    Factory::make()?->send();
                }
            }
            PHP);
        $this->writeServiceFiles($oldServiceFilePath, $newServiceFilePath);
        $this->writeFactoryWithNullableReturn($factoryFilePath, $factoryReturnsNewService);
    }

    /**
     * Writes the factory fixture with either an old or a new nullable return type.
     *
     * @param string $factoryFilePath          the factory file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeFactoryWithNullableReturn(string $factoryFilePath, bool $factoryReturnsNewService): void
    {
        $serviceClass = $factoryReturnsNewService ? 'NewService' : 'OldService';

        file_put_contents($factoryFilePath, <<<PHP
            <?php

            namespace App;

            final class Factory
            {
                public static function make(): ?{$serviceClass}
                {
                    return new {$serviceClass}();
                }
            }
            PHP);
    }

    /**
     * Writes the fixture files used by array-shape return dispatch partial-build tests.
     *
     * @param string $srcDirectory             the source directory
     * @param string $aFilePath                the consumer file path
     * @param string $factoryFilePath          the factory file path
     * @param string $oldServiceFilePath       the old service file path
     * @param string $newServiceFilePath       the new service file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeArrayShapeReturnDispatchFiles(
        string $srcDirectory,
        string $aFilePath,
        string $factoryFilePath,
        string $oldServiceFilePath,
        string $newServiceFilePath,
        bool $factoryReturnsNewService,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    Factory::config()['service']->send();
                }
            }
            PHP);
        $this->writeServiceFiles($oldServiceFilePath, $newServiceFilePath);
        $this->writeFactoryWithArrayShapeReturn($factoryFilePath, $factoryReturnsNewService);
    }

    /**
     * Writes the factory fixture with either an old or a new array-shape PHPDoc return type.
     *
     * @param string $factoryFilePath          the factory file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeFactoryWithArrayShapeReturn(string $factoryFilePath, bool $factoryReturnsNewService): void
    {
        $serviceClass = $factoryReturnsNewService ? 'NewService' : 'OldService';

        file_put_contents($factoryFilePath, <<<PHP
            <?php

            namespace App;

            final class Factory
            {
                /**
                 * @return array{service: {$serviceClass}}
                 */
                public static function config(): array
                {
                    return ['service' => new {$serviceClass}()];
                }
            }
            PHP);
    }

    /**
     * Writes the fixture files used by callable return dispatch partial-build tests.
     *
     * @param string $srcDirectory             the source directory
     * @param string $aFilePath                the consumer file path
     * @param string $factoryFilePath          the factory file path
     * @param string $oldServiceFilePath       the old service file path
     * @param string $newServiceFilePath       the new service file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeCallableReturnDispatchFiles(
        string $srcDirectory,
        string $aFilePath,
        string $factoryFilePath,
        string $oldServiceFilePath,
        string $newServiceFilePath,
        bool $factoryReturnsNewService,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    $callback = Factory::callback();
                    $callback()->send();
                }
            }
            PHP);
        $this->writeServiceFiles($oldServiceFilePath, $newServiceFilePath);
        $this->writeFactoryWithCallableReturn($factoryFilePath, $factoryReturnsNewService);
    }

    /**
     * Writes the factory fixture with either an old or a new callable PHPDoc return type.
     *
     * @param string $factoryFilePath          the factory file path
     * @param bool   $factoryReturnsNewService whether the factory returns the new service type
     */
    protected function writeFactoryWithCallableReturn(string $factoryFilePath, bool $factoryReturnsNewService): void
    {
        $serviceClass = $factoryReturnsNewService ? 'NewService' : 'OldService';

        file_put_contents($factoryFilePath, <<<PHP
            <?php

            namespace App;

            final class Factory
            {
                /**
                 * @return callable(): {$serviceClass}
                 */
                public static function callback(): callable
                {
                    return static fn (): {$serviceClass} => new {$serviceClass}();
                }
            }
            PHP);
    }

    /**
     * Writes old and new service fixtures.
     *
     * @param string $oldServiceFilePath the old service file path
     * @param string $newServiceFilePath the new service file path
     */
    protected function writeServiceFiles(string $oldServiceFilePath, string $newServiceFilePath): void
    {
        file_put_contents($oldServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class OldService
            {
                public function send(): void
                {
                }
            }
            PHP);
        file_put_contents($newServiceFilePath, <<<'PHP'
            <?php

            namespace App;

            final class NewService
            {
                public function send(): void
                {
                }
            }
            PHP);
    }

    /**
     * Asserts a partial build involving a property type change matches the expected full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild     the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild    the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild        the fresh full build
     * @param string                     $changedFilePath  the changed physical file path
     * @param string                     $impactedFilePath the impacted physical file path
     */
    protected function assertPropertyTypePartialBuildMatchesFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
        string $changedFilePath,
        string $impactedFilePath,
    ): void {
        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($changedFilePath) ?: $changedFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($impactedFilePath) ?: $impactedFilePath,
        ));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewService',
            name: 'send',
            type: MemberType::METHOD,
        )));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Asserts a partial build involving a return type change matches the expected full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild     the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild    the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild        the fresh full build
     * @param string                     $changedFilePath  the changed physical file path
     * @param string                     $impactedFilePath the impacted physical file path
     */
    protected function assertReturnTypePartialBuildMatchesFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
        string $changedFilePath,
        string $impactedFilePath,
    ): void {
        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($changedFilePath) ?: $changedFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($impactedFilePath) ?: $impactedFilePath,
        ));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewService',
            name: 'send',
            type: MemberType::METHOD,
        )));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Asserts a partial build involving an abstract-parent metadata change matches the expected full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild     the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild    the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild        the fresh full build
     * @param string                     $changedFilePath  the changed physical file path
     * @param string                     $impactedFilePath the impacted physical file path
     */
    protected function assertAbstractParentPartialBuildMatchesFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
        string $changedFilePath,
        string $impactedFilePath,
    ): void {
        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($changedFilePath) ?: $changedFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($impactedFilePath) ?: $impactedFilePath,
        ));
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $fullBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertSame(
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
            $this->availableMemberSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $fastPathBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
    }

    /**
     * Writes the fixture files used by indirect abstract-parent partial-build tests.
     *
     * @param string $srcDirectory               the source directory
     * @param string $aFilePath                  the consumer file path
     * @param string $abstractRootFilePath       the abstract root file path
     * @param string $concreteBaseFilePath       the concrete base file path
     * @param string $serviceFilePath            the service file path
     * @param bool   $serviceExtendsConcreteBase whether the service extends the concrete base
     */
    protected function writeIndirectAbstractParentFiles(
        string $srcDirectory,
        string $aFilePath,
        string $abstractRootFilePath,
        string $concreteBaseFilePath,
        string $serviceFilePath,
        bool $serviceExtendsConcreteBase,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(AbstractRoot $service): void
                {
                    $service->send();
                }
            }
            PHP);
        file_put_contents($abstractRootFilePath, <<<'PHP'
            <?php

            namespace App;

            abstract class AbstractRoot
            {
                abstract public function send(): void;
            }
            PHP);
        file_put_contents($concreteBaseFilePath, <<<'PHP'
            <?php

            namespace App;

            abstract class ConcreteBase extends AbstractRoot
            {
            }
            PHP);
        $this->writeServiceWithOptionalConcreteBaseParent($serviceFilePath, $serviceExtendsConcreteBase);
    }

    /**
     * Writes the service fixture with or without the concrete base parent.
     *
     * @param string $serviceFilePath            the service file path
     * @param bool   $serviceExtendsConcreteBase whether the service extends the concrete base
     */
    protected function writeServiceWithOptionalConcreteBaseParent(
        string $serviceFilePath,
        bool $serviceExtendsConcreteBase,
    ): void {
        $extendsClause = $serviceExtendsConcreteBase ? ' extends ConcreteBase' : '';

        file_put_contents($serviceFilePath, <<<PHP
            <?php

            namespace App;

            final class Service{$extendsClause}
            {
                public function send(): void
                {
                }
            }
            PHP);
    }

    /**
     * Writes the fixture files used by trait partial-build tests.
     *
     * @param string $srcDirectory     the source directory
     * @param string $aFilePath        the consumer file path
     * @param string $contractFilePath the contract file path
     * @param string $traitFilePath    the trait file path
     * @param string $serviceFilePath  the service file path
     * @param bool   $serviceUsesTrait whether the service uses the trait
     */
    protected function writeTraitImplementationFiles(
        string $srcDirectory,
        string $aFilePath,
        string $contractFilePath,
        string $traitFilePath,
        string $serviceFilePath,
        bool $serviceUsesTrait,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(Contract $service): void
                {
                    $service->send();
                }
            }
            PHP);
        file_put_contents($contractFilePath, <<<'PHP'
            <?php

            namespace App;

            interface Contract
            {
                public function send(): void;
            }
            PHP);
        file_put_contents($traitFilePath, <<<'PHP'
            <?php

            namespace App;

            trait SenderTrait
            {
                public function send(): void
                {
                }
            }
            PHP);
        $this->writeServiceWithOptionalTrait($serviceFilePath, $serviceUsesTrait);
    }

    /**
     * Writes the service fixture with a local method or a trait-provided method.
     *
     * @param string $serviceFilePath  the service file path
     * @param bool   $serviceUsesTrait whether the service uses the trait
     */
    protected function writeServiceWithOptionalTrait(string $serviceFilePath, bool $serviceUsesTrait): void
    {
        $body = $serviceUsesTrait
            ? <<<'PHP'
                    use SenderTrait;
                PHP
            : <<<'PHP'
                    public function send(): void
                    {
                    }
                PHP;

        file_put_contents($serviceFilePath, <<<PHP
            <?php

            namespace App;

            final class Service implements Contract
            {
            {$body}
            }
            PHP);
    }

    /**
     * Writes the fixture files used by combined owner-metadata partial-build tests.
     *
     * @param string $srcDirectory                the source directory
     * @param string $contractConsumerFilePath    the contract consumer file path
     * @param string $parentConsumerFilePath      the parent consumer file path
     * @param string $contractFilePath            the contract file path
     * @param string $abstractRootFilePath        the abstract root file path
     * @param string $traitFilePath               the trait file path
     * @param string $serviceFilePath             the service file path
     * @param bool   $serviceUsesCombinedMetadata whether the service uses the combined metadata
     */
    protected function writeCombinedImplementationFiles(
        string $srcDirectory,
        string $contractConsumerFilePath,
        string $parentConsumerFilePath,
        string $contractFilePath,
        string $abstractRootFilePath,
        string $traitFilePath,
        string $serviceFilePath,
        bool $serviceUsesCombinedMetadata,
    ): void {
        mkdir($srcDirectory, 0o777, true);
        file_put_contents($contractConsumerFilePath, <<<'PHP'
            <?php

            namespace App;

            final class ContractConsumer
            {
                public function run(Contract $service): void
                {
                    $service->send();
                }
            }
            PHP);
        file_put_contents($parentConsumerFilePath, <<<'PHP'
            <?php

            namespace App;

            final class ParentConsumer
            {
                public function run(AbstractRoot $service): void
                {
                    $service->send();
                }
            }
            PHP);
        file_put_contents($contractFilePath, <<<'PHP'
            <?php

            namespace App;

            interface Contract
            {
                public function send(): void;
            }
            PHP);
        file_put_contents($abstractRootFilePath, <<<'PHP'
            <?php

            namespace App;

            abstract class AbstractRoot
            {
                abstract public function send(): void;
            }
            PHP);
        file_put_contents($traitFilePath, <<<'PHP'
            <?php

            namespace App;

            trait SenderTrait
            {
                public function send(): void
                {
                }
            }
            PHP);
        $this->writeServiceWithOptionalCombinedMetadata($serviceFilePath, $serviceUsesCombinedMetadata);
    }

    /**
     * Writes the service fixture with or without combined owner metadata.
     *
     * @param string $serviceFilePath             the service file path
     * @param bool   $serviceUsesCombinedMetadata whether the service uses the combined metadata
     */
    protected function writeServiceWithOptionalCombinedMetadata(
        string $serviceFilePath,
        bool $serviceUsesCombinedMetadata,
    ): void {
        $signature = $serviceUsesCombinedMetadata
            ? ' extends AbstractRoot implements Contract'
            : '';
        $body = $serviceUsesCombinedMetadata
            ? <<<'PHP'
                    use SenderTrait;
                PHP
            : <<<'PHP'
                    public function send(): void
                    {
                    }
                PHP;

        file_put_contents($serviceFilePath, <<<PHP
            <?php

            namespace App;

            final class Service{$signature}
            {
            {$body}
            }
            PHP);
    }

    /**
     * Asserts a partial build involving combined owner metadata changes matches the expected full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild      the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild     the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild         the fresh full build
     * @param string                     $changedFilePath   the changed physical file path
     * @param list<string>               $impactedFilePaths the impacted physical file paths
     */
    protected function assertCombinedPartialBuildMatchesFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
        string $changedFilePath,
        array $impactedFilePaths,
    ): void {
        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($changedFilePath) ?: $changedFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);

        foreach ($impactedFilePaths as $impactedFilePath) {
            self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
                realpath($impactedFilePath) ?: $impactedFilePath,
            ));
        }

        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $fullBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertSame(
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
            $this->availableMemberSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $fastPathBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
    }

    /**
     * Asserts a partial build and its following fast path match a fresh full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild  the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild     the fresh full build
     */
    protected function assertPartialAndFastPathMatchFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
    ): void {
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->parameterUsageSignatures($fullBuild->memberDependencyGraph),
            $this->parameterUsageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $fullBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertSame(
            $this->declarationHashes($partialBuild->memberDependencyGraph),
            $this->declarationHashes($fastPathBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($partialBuild->memberDependencyGraph),
            $this->usageSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->parameterUsageSignatures($partialBuild->memberDependencyGraph),
            $this->parameterUsageSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
            $this->availableMemberSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $fastPathBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
    }

    /**
     * Asserts a partial build involving a trait metadata change matches the expected full build.
     *
     * @param MemberDependencyGraphBuild $partialBuild     the partial build
     * @param MemberDependencyGraphBuild $fastPathBuild    the following fast-path build
     * @param MemberDependencyGraphBuild $fullBuild        the fresh full build
     * @param string                     $changedFilePath  the changed physical file path
     * @param string                     $impactedFilePath the impacted physical file path
     */
    protected function assertTraitPartialBuildMatchesFullBuild(
        MemberDependencyGraphBuild $partialBuild,
        MemberDependencyGraphBuild $fastPathBuild,
        MemberDependencyGraphBuild $fullBuild,
        string $changedFilePath,
        string $impactedFilePath,
    ): void {
        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($changedFilePath) ?: $changedFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($impactedFilePath) ?: $impactedFilePath,
        ));
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $fullBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertSame(
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
            $this->availableMemberSignatures($fastPathBuild->memberDependencyGraph),
        );
        self::assertEquals(
            $partialBuild->memberDependencyGraph->interfaceImplementationsIndex,
            $fastPathBuild->memberDependencyGraph->interfaceImplementationsIndex,
        );
    }

    /**
     * Returns sorted available member signatures.
     *
     * @param MemberDependencyGraph $graph the graph to inspect
     *
     * @return list<string>
     */
    protected function availableMemberSignatures(MemberDependencyGraph $graph): array
    {
        $signatures = [];

        foreach ($graph->availableMembers->iterateMembers() as $availableMember) {
            $declaredIns = array_keys($availableMember->declaredIns);
            sort($declaredIns);

            $signatures[] = implode('|', [
                $availableMember->member->hash(),
                $availableMember->origin->name,
                implode(',', $declaredIns),
                (string) $availableMember->visibility,
            ]);
        }

        sort($signatures);

        return $signatures;
    }

    /**
     * Removes a directory recursively.
     *
     * @param string $directory the directory to remove
     */
    protected function removeDirectory(string $directory): void
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
