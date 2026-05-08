<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactoryOptions;

/**
 * Covers polymorphism and owner metadata partial rebuild scenarios for the member dependency graph factory.
 */
final class MemberDependencyGraphFactoryPolymorphismPartialRebuildTest extends MemberDependencyGraphFactoryTestCase
{
    /**
     * Ensures partial builds drop stale polymorphic implementations when an owner changes interfaces.
     *
     * @return void
     */
    public function testPartialBuildDropsStaleInterfaceImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-interface-implementation-change';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $otherContractFilePath = $srcDirectory . '/OtherContract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-interface-implementation-change.cache';
        $fullCacheFilePath = $this->workspace . '/partial-interface-implementation-change-full.cache';

        mkdir($srcDirectory, 0777, true);
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
        file_put_contents($otherContractFilePath, <<<'PHP'
<?php

namespace App;

interface OtherContract
{
    public function send(): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements Contract
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements OtherContract
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
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
     * Ensures partial builds add polymorphic implementations when an owner starts implementing an interface.
     *
     * @return void
     */
    public function testPartialBuildAddsNewInterfaceImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-interface-implementation-add';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-interface-implementation-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-interface-implementation-add-full.cache';

        mkdir($srcDirectory, 0777, true);
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
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements Contract
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
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
     * Ensures partial builds expand consumers when an owner starts implementing an extended interface.
     *
     * @return void
     */
    public function testPartialBuildAddsNewExtendedInterfaceImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-extended-interface-implementation-add';
        $aFilePath = $srcDirectory . '/A.php';
        $rootContractFilePath = $srcDirectory . '/RootContract.php';
        $childContractFilePath = $srcDirectory . '/ChildContract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-extended-interface-implementation-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-extended-interface-implementation-add-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(RootContract $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($rootContractFilePath, <<<'PHP'
<?php

namespace App;

interface RootContract
{
    public function send(): void;
}
PHP);
        file_put_contents($childContractFilePath, <<<'PHP'
<?php

namespace App;

interface ChildContract extends RootContract
{
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements ChildContract
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
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
     * Ensures partial builds expand consumers when an owner stops implementing an extended interface.
     *
     * @return void
     */
    public function testPartialBuildRemovesExtendedInterfaceImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-extended-interface-implementation-remove';
        $aFilePath = $srcDirectory . '/A.php';
        $rootContractFilePath = $srcDirectory . '/RootContract.php';
        $childContractFilePath = $srcDirectory . '/ChildContract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-extended-interface-implementation-remove.cache';
        $fullCacheFilePath = $this->workspace . '/partial-extended-interface-implementation-remove-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(RootContract $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($rootContractFilePath, <<<'PHP'
<?php

namespace App;

interface RootContract
{
    public function send(): void;
}
PHP);
        file_put_contents($childContractFilePath, <<<'PHP'
<?php

namespace App;

interface ChildContract extends RootContract
{
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements ChildContract
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
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
     * Ensures partial builds expand consumers when an owner starts extending an abstract class.
     *
     * @return void
     */
    public function testPartialBuildAddsNewAbstractParentImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-abstract-parent-add';
        $aFilePath = $srcDirectory . '/A.php';
        $abstractServiceFilePath = $srcDirectory . '/AbstractService.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-abstract-parent-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-abstract-parent-add-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(AbstractService $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($abstractServiceFilePath, <<<'PHP'
<?php

namespace App;

abstract class AbstractService
{
    abstract public function send(): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service extends AbstractService
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertAbstractParentPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand consumers when an owner stops extending an abstract class.
     *
     * @return void
     */
    public function testPartialBuildRemovesAbstractParentImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-abstract-parent-remove';
        $aFilePath = $srcDirectory . '/A.php';
        $abstractServiceFilePath = $srcDirectory . '/AbstractService.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-abstract-parent-remove.cache';
        $fullCacheFilePath = $this->workspace . '/partial-abstract-parent-remove-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(AbstractService $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($abstractServiceFilePath, <<<'PHP'
<?php

namespace App;

abstract class AbstractService
{
    abstract public function send(): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service extends AbstractService
{
    public function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertAbstractParentPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand consumers when an owner starts extending an indirect abstract parent.
     *
     * @return void
     */
    public function testPartialBuildAddsIndirectAbstractParentImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-indirect-abstract-parent-add';
        $aFilePath = $srcDirectory . '/A.php';
        $abstractRootFilePath = $srcDirectory . '/AbstractRoot.php';
        $concreteBaseFilePath = $srcDirectory . '/ConcreteBase.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-indirect-abstract-parent-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-indirect-abstract-parent-add-full.cache';

        $this->writeIndirectAbstractParentFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            abstractRootFilePath: $abstractRootFilePath,
            concreteBaseFilePath: $concreteBaseFilePath,
            serviceFilePath: $serviceFilePath,
            serviceExtendsConcreteBase: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalConcreteBaseParent($serviceFilePath, true);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertAbstractParentPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand consumers when an owner stops extending an indirect abstract parent.
     *
     * @return void
     */
    public function testPartialBuildRemovesIndirectAbstractParentImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-indirect-abstract-parent-remove';
        $aFilePath = $srcDirectory . '/A.php';
        $abstractRootFilePath = $srcDirectory . '/AbstractRoot.php';
        $concreteBaseFilePath = $srcDirectory . '/ConcreteBase.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-indirect-abstract-parent-remove.cache';
        $fullCacheFilePath = $this->workspace . '/partial-indirect-abstract-parent-remove-full.cache';

        $this->writeIndirectAbstractParentFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            abstractRootFilePath: $abstractRootFilePath,
            concreteBaseFilePath: $concreteBaseFilePath,
            serviceFilePath: $serviceFilePath,
            serviceExtendsConcreteBase: true,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalConcreteBaseParent($serviceFilePath, false);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertAbstractParentPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand consumers when an owner starts using a trait implementation.
     *
     * @return void
     */
    public function testPartialBuildAddsTraitImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-trait-implementation-add';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $traitFilePath = $srcDirectory . '/SenderTrait.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-trait-implementation-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-trait-implementation-add-full.cache';

        $this->writeTraitImplementationFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            contractFilePath: $contractFilePath,
            traitFilePath: $traitFilePath,
            serviceFilePath: $serviceFilePath,
            serviceUsesTrait: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalTrait($serviceFilePath, true);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertTraitPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand consumers when an owner stops using a trait implementation.
     *
     * @return void
     */
    public function testPartialBuildRemovesTraitImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-trait-implementation-remove';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $traitFilePath = $srcDirectory . '/SenderTrait.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-trait-implementation-remove.cache';
        $fullCacheFilePath = $this->workspace . '/partial-trait-implementation-remove-full.cache';

        $this->writeTraitImplementationFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            contractFilePath: $contractFilePath,
            traitFilePath: $traitFilePath,
            serviceFilePath: $serviceFilePath,
            serviceUsesTrait: true,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalTrait($serviceFilePath, false);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertTraitPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds expand all consumers when an owner adds interface, parent, and trait metadata together.
     *
     * @return void
     */
    public function testPartialBuildAddsCombinedInterfaceParentAndTraitImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-combined-owner-add';
        $contractConsumerFilePath = $srcDirectory . '/ContractConsumer.php';
        $parentConsumerFilePath = $srcDirectory . '/ParentConsumer.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $abstractRootFilePath = $srcDirectory . '/AbstractRoot.php';
        $traitFilePath = $srcDirectory . '/SenderTrait.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-combined-owner-add.cache';
        $fullCacheFilePath = $this->workspace . '/partial-combined-owner-add-full.cache';

        $this->writeCombinedImplementationFiles(
            srcDirectory: $srcDirectory,
            contractConsumerFilePath: $contractConsumerFilePath,
            parentConsumerFilePath: $parentConsumerFilePath,
            contractFilePath: $contractFilePath,
            abstractRootFilePath: $abstractRootFilePath,
            traitFilePath: $traitFilePath,
            serviceFilePath: $serviceFilePath,
            serviceUsesCombinedMetadata: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalCombinedMetadata($serviceFilePath, true);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertCombinedPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePaths: [$contractConsumerFilePath, $parentConsumerFilePath],
        );
    }

    /**
     * Ensures partial builds expand all consumers when an owner removes interface, parent, and trait metadata together.
     *
     * @return void
     */
    public function testPartialBuildRemovesCombinedInterfaceParentAndTraitImplementation(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-combined-owner-remove';
        $contractConsumerFilePath = $srcDirectory . '/ContractConsumer.php';
        $parentConsumerFilePath = $srcDirectory . '/ParentConsumer.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $abstractRootFilePath = $srcDirectory . '/AbstractRoot.php';
        $traitFilePath = $srcDirectory . '/SenderTrait.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-combined-owner-remove.cache';
        $fullCacheFilePath = $this->workspace . '/partial-combined-owner-remove-full.cache';

        $this->writeCombinedImplementationFiles(
            srcDirectory: $srcDirectory,
            contractConsumerFilePath: $contractConsumerFilePath,
            parentConsumerFilePath: $parentConsumerFilePath,
            contractFilePath: $contractFilePath,
            abstractRootFilePath: $abstractRootFilePath,
            traitFilePath: $traitFilePath,
            serviceFilePath: $serviceFilePath,
            serviceUsesCombinedMetadata: true,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeServiceWithOptionalCombinedMetadata($serviceFilePath, false);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        $this->assertCombinedPartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $serviceFilePath,
            impactedFilePaths: [$contractConsumerFilePath, $parentConsumerFilePath],
        );
    }

}
