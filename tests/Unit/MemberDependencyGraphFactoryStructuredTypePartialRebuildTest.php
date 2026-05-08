<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactoryOptions;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;

/**
 * Covers structured type partial rebuild scenarios for the member dependency graph factory.
 */
final class MemberDependencyGraphFactoryStructuredTypePartialRebuildTest extends MemberDependencyGraphFactoryTestCase
{
    /**
     * Ensures partial builds rebuild chained-call consumers when a factory return type changes.
     */
    public function testPartialBuildExpandsMethodReturnTypeChangeToChainedCallConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-return-type-chain-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-return-type-chain-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-return-type-chain-impact-full.cache';

        mkdir($srcDirectory, 0o777, true);
        file_put_contents($aFilePath, <<<'PHP'
            <?php

            namespace App;

            final class A
            {
                public function run(): void
                {
                    Factory::make()->send();
                }
            }
            PHP);
        file_put_contents($factoryFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Factory
            {
                public static function make(): OldService
                {
                    return new OldService();
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

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($factoryFilePath, <<<'PHP'
            <?php

            namespace App;

            final class Factory
            {
                public static function make(): NewService
                {
                    return new NewService();
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
            realpath($factoryFilePath) ?: $factoryFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($newServiceFilePath) ?: $newServiceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewService',
            name: 'send',
            type: MemberType::METHOD,
        )));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Ensures partial builds rebuild property consumers when a native property type changes.
     */
    public function testPartialBuildExpandsNativePropertyTypeChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-native-property-type-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $holderFilePath = $srcDirectory.'/Holder.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-native-property-type-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-native-property-type-impact-full.cache';

        $this->writePropertyTypeDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            holderFilePath: $holderFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            holderUsesNewService: false,
            usePhpDocPropertyType: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeHolderWithPropertyType($holderFilePath, holderUsesNewService: true, usePhpDocPropertyType: false);

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

        $this->assertPropertyTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $holderFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild chained-call consumers when a PHPDoc return type changes.
     */
    public function testPartialBuildExpandsPhpDocReturnTypeChangeToChainedCallConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-phpdoc-return-type-chain-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-phpdoc-return-type-chain-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-phpdoc-return-type-chain-impact-full.cache';

        $this->writeFactoryReturnDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            factoryFilePath: $factoryFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            factoryReturnsNewService: false,
            useGenericListReturn: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeFactoryWithPhpDocReturn(
            factoryFilePath: $factoryFilePath,
            factoryReturnsNewService: true,
            useGenericListReturn: false,
        );

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

        $this->assertReturnTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $factoryFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild property consumers when a PHPDoc property type changes.
     */
    public function testPartialBuildExpandsPhpDocPropertyTypeChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-phpdoc-property-type-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $holderFilePath = $srcDirectory.'/Holder.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-phpdoc-property-type-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-phpdoc-property-type-impact-full.cache';

        $this->writePropertyTypeDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            holderFilePath: $holderFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            holderUsesNewService: false,
            usePhpDocPropertyType: true,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeHolderWithPropertyType($holderFilePath, holderUsesNewService: true, usePhpDocPropertyType: true);

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

        $this->assertPropertyTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $holderFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild foreach consumers when a generic PHPDoc list return type changes.
     */
    public function testPartialBuildExpandsGenericListReturnTypeChangeToForeachConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-generic-list-return-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-generic-list-return-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-generic-list-return-impact-full.cache';

        $this->writeFactoryReturnDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            factoryFilePath: $factoryFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            factoryReturnsNewService: false,
            useGenericListReturn: true,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeFactoryWithPhpDocReturn(
            factoryFilePath: $factoryFilePath,
            factoryReturnsNewService: true,
            useGenericListReturn: true,
        );

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

        $this->assertReturnTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $factoryFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild nullsafe consumers when a nullable return type changes.
     */
    public function testPartialBuildExpandsNullableReturnTypeChangeToNullsafeConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-nullable-return-type-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-nullable-return-type-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-nullable-return-type-impact-full.cache';

        $this->writeNullableReturnDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            factoryFilePath: $factoryFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            factoryReturnsNewService: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeFactoryWithNullableReturn($factoryFilePath, factoryReturnsNewService: true);

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

        $this->assertReturnTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $factoryFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild array-shape consumers when a PHPDoc return field type changes.
     */
    public function testPartialBuildExpandsArrayShapeReturnTypeChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-array-shape-return-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-array-shape-return-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-array-shape-return-impact-full.cache';

        $this->writeArrayShapeReturnDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            factoryFilePath: $factoryFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            factoryReturnsNewService: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeFactoryWithArrayShapeReturn($factoryFilePath, factoryReturnsNewService: true);

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

        $this->assertReturnTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $factoryFilePath,
            impactedFilePath: $aFilePath,
        );
    }

    /**
     * Ensures partial builds rebuild callable consumers when a callable PHPDoc return type changes.
     */
    public function testPartialBuildExpandsCallableReturnTypeChangeToInvocationConsumer(): void
    {
        $srcDirectory = $this->workspace.'/src-partial-callable-return-impact';
        $aFilePath = $srcDirectory.'/A.php';
        $factoryFilePath = $srcDirectory.'/Factory.php';
        $oldServiceFilePath = $srcDirectory.'/OldService.php';
        $newServiceFilePath = $srcDirectory.'/NewService.php';
        $partialCacheFilePath = $this->workspace.'/partial-callable-return-impact.cache';
        $fullCacheFilePath = $this->workspace.'/partial-callable-return-impact-full.cache';

        $this->writeCallableReturnDispatchFiles(
            srcDirectory: $srcDirectory,
            aFilePath: $aFilePath,
            factoryFilePath: $factoryFilePath,
            oldServiceFilePath: $oldServiceFilePath,
            newServiceFilePath: $newServiceFilePath,
            factoryReturnsNewService: false,
        );

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        $this->writeFactoryWithCallableReturn($factoryFilePath, factoryReturnsNewService: true);

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

        $this->assertReturnTypePartialBuildMatchesFullBuild(
            partialBuild: $partialBuild,
            fastPathBuild: $fastPathBuild,
            fullBuild: $fullBuild,
            changedFilePath: $factoryFilePath,
            impactedFilePath: $aFilePath,
        );
    }
}
