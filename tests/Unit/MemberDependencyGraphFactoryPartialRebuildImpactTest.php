<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactory;
use PhpNoobs\MemberGraph\Application\Build\Factory\MemberDependencyGraphFactoryOptions;
use PhpNoobs\MemberGraph\Domain\Graph\MemberId;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;

/**
 * Covers generic partial rebuild impact, deletion, and replacement scenarios for the member dependency graph factory.
 */
final class MemberDependencyGraphFactoryPartialRebuildImpactTest extends MemberDependencyGraphFactoryTestCase
{
    /**
     * Ensures opt-in partial factory builds rebuild files impacted by changed declarations.
     *
     * @return void
     */
    public function testPartialBuildExpandsToImpactedFilesAndMatchesFreshFullBuildFacts(): void
    {
        $srcDirectory = $this->workspace . '/src';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/partial-member-graph.cache';
        $fullCacheFilePath = $this->workspace . '/full-member-graph.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->changed();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function changed(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function changed(): void
    {
        $value = 1;
    }

    public function next(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($bFilePath) ?: $bFilePath,
        ));
        self::assertCount(2, $partialBuild->buildReport->partialRebuildWorkingSet->filesToRebuildGraph);
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
    }

    /**
     * Ensures opt-in partial factory builds close transitive impacted-file chains.
     *
     * @return void
     */
    public function testPartialBuildExpandsThroughTransitiveImpactedFilesAndMatchesFreshFullBuildFacts(): void
    {
        $srcDirectory = $this->workspace . '/src';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $cFilePath = $srcDirectory . '/C.php';
        $partialCacheFilePath = $this->workspace . '/partial-member-graph.cache';
        $fullCacheFilePath = $this->workspace . '/full-member-graph.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->run(new C());
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function run(C $c): void
    {
        $c->changed();
    }
}
PHP);
        file_put_contents($cFilePath, <<<'PHP'
<?php

namespace App;

final class C
{
    public function changed(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($cFilePath, <<<'PHP'
<?php

namespace App;

final class C
{
    public function changed(): void
    {
        $value = 1;
    }

    public function next(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($bFilePath) ?: $bFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($cFilePath) ?: $cFilePath,
        ));
        self::assertCount(3, $partialBuild->buildReport->partialRebuildWorkingSet->filesToRebuildGraph);
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
    }

    /**
     * Ensures opt-in partial factory builds drop removed members from impacted files.
     *
     * @return void
     */
    public function testPartialBuildRemovesDeletedMembersAndMatchesFreshFullBuildFacts(): void
    {
        $srcDirectory = $this->workspace . '/src';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/partial-member-graph.cache';
        $fullCacheFilePath = $this->workspace . '/full-member-graph.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->old();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function old(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function changed(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($bFilePath) ?: $bFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'old',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'changed',
            type: MemberType::METHOD,
        )));
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
    }

    /**
     * Ensures opt-in partial factory builds handle non-method removed declarations and parameters.
     *
     * @return void
     */
    public function testPartialBuildRemovesDeletedNonMethodMembersAndParameters(): void
    {
        $scenarios = [
            'property' => [
                'initialFiles' => [
                    'A.php' => <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $value = $b->old;
    }
}
PHP,
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public string $old = '';
}
PHP,
                ],
                'changedFiles' => [
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public string $changed = '';
}
PHP,
                ],
                'expectedRebuildFiles' => ['A.php', 'B.php'],
                'removedMembers' => [new MemberId('App\\B', 'old', MemberType::PROPERTY)],
                'removedParameters' => [],
            ],
            'class_constant' => [
                'initialFiles' => [
                    'A.php' => <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        $value = B::OLD;
    }
}
PHP,
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public const OLD = 'old';
}
PHP,
                ],
                'changedFiles' => [
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public const CHANGED = 'changed';
}
PHP,
                ],
                'expectedRebuildFiles' => ['A.php', 'B.php'],
                'removedMembers' => [new MemberId('App\\B', 'OLD', MemberType::CLASS_CONSTANT)],
                'removedParameters' => [],
            ],
            'function' => [
                'initialFiles' => [
                    'A.php' => <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        old_function();
    }
}
PHP,
                    'functions.php' => <<<'PHP'
<?php

namespace App;

function old_function(): void
{
}
PHP,
                ],
                'changedFiles' => [
                    'functions.php' => <<<'PHP'
<?php

namespace App;

function changed_function(): void
{
}
PHP,
                ],
                'expectedRebuildFiles' => ['A.php', 'functions.php'],
                'removedMembers' => [new MemberId('', 'App\\old_function', MemberType::FUNCTION_)],
                'removedParameters' => [],
            ],
            'parameter' => [
                'initialFiles' => [
                    'A.php' => <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->run(old: 'value');
    }
}
PHP,
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public function run(string $old): void
    {
    }
}
PHP,
                ],
                'changedFiles' => [
                    'B.php' => <<<'PHP'
<?php

namespace App;

final class B
{
    public function run(string $changed): void
    {
    }
}
PHP,
                ],
                'expectedRebuildFiles' => ['A.php', 'B.php'],
                'removedMembers' => [],
                'removedParameters' => [],
            ],
        ];

        foreach ($scenarios as $name => $scenario) {
            $srcDirectory = $this->workspace . '/src-' . $name;
            $partialCacheFilePath = $this->workspace . '/partial-' . $name . '.cache';
            $fullCacheFilePath = $this->workspace . '/full-' . $name . '.cache';

            mkdir($srcDirectory, 0777, true);

            foreach ($scenario['initialFiles'] as $relativePath => $code) {
                file_put_contents($srcDirectory . '/' . $relativePath, $code);
            }

            MemberDependencyGraphFactory::fromDirectory(
                directories: [$srcDirectory],
                cacheFilePath: $partialCacheFilePath,
            );

            sleep(1);

            foreach ($scenario['changedFiles'] as $relativePath => $code) {
                file_put_contents($srcDirectory . '/' . $relativePath, $code);
            }

            $partialBuild = MemberDependencyGraphFactory::fromDirectory(
                directories: [$srcDirectory],
                cacheFilePath: $partialCacheFilePath,
                options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
            );
            $fullBuild = MemberDependencyGraphFactory::fromDirectory(
                directories: [$srcDirectory],
                cacheFilePath: $fullCacheFilePath,
                clearCache: true,
            );

            self::assertTrue($partialBuild->usedPartialBuild(), $name);
            self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet, $name);

            foreach ($scenario['expectedRebuildFiles'] as $relativePath) {
                self::assertTrue(
                    $partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
                        realpath($srcDirectory . '/' . $relativePath) ?: $srcDirectory . '/' . $relativePath,
                    ),
                    $name . ':' . $relativePath,
                );
            }

            foreach ($scenario['removedMembers'] as $memberId) {
                self::assertNull($partialBuild->memberDependencyGraph->declarations->get($memberId), $name);
            }

            /** @phpstan-ignore-next-line Keeps the scenario shape explicit even when no current scenario removes parameters. */
            foreach ($scenario['removedParameters'] as $parameterId) {
                self::assertSame(
                    [],
                    $partialBuild->memberDependencyGraph->parameterUsages->getByTarget($parameterId),
                    $name,
                );
            }

            self::assertSame(
                $this->declarationHashes($fullBuild->memberDependencyGraph),
                $this->declarationHashes($partialBuild->memberDependencyGraph),
                $name,
            );
            self::assertSame(
                $this->usageSignatures($fullBuild->memberDependencyGraph),
                $this->usageSignatures($partialBuild->memberDependencyGraph),
                $name,
            );
            self::assertSame(
                $this->parameterUsageSignatures($fullBuild->memberDependencyGraph),
                $this->parameterUsageSignatures($partialBuild->memberDependencyGraph),
                $name,
            );
            self::assertSame(
                $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
                $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
                $name,
            );
        }
    }

    /**
     * Ensures opt-in partial factory builds remove deleted files and rebuild impacted consumers.
     *
     * @return void
     */
    public function testPartialBuildRemovesDeletedFilesAndMatchesFreshFullBuildFacts(): void
    {
        $srcDirectory = $this->workspace . '/src-deleted-file';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/partial-deleted-file.cache';
        $fullCacheFilePath = $this->workspace . '/full-deleted-file.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        B::run();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public static function run(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        $normalizedBFilePath = realpath($bFilePath) ?: $bFilePath;
        unlink($bFilePath);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedBFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertFalse($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            $normalizedBFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'run',
            type: MemberType::METHOD,
        )));
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
    }

    /**
     * Ensures opt-in partial factory builds persist cache data for the next fast path.
     *
     * @return void
     */
    public function testPartialBuildPersistsCacheForNextFastPath(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-cache';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $cacheFilePath = $this->workspace . '/partial-cache-followup.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->changed();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function changed(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function changed(): void
    {
        $value = 1;
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
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
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
            $this->availableMemberSignatures($fastPathBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures consecutive opt-in partial builds reuse the cache state persisted by the previous partial build.
     *
     * @return void
     */
    public function testConsecutivePartialBuildsReusePersistedDeclarationSnapshots(): void
    {
        $srcDirectory = $this->workspace . '/src-consecutive-partials';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/consecutive-partials.cache';
        $fullCacheFilePath = $this->workspace . '/consecutive-partials-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->first();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function first(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function first(): void
    {
    }

    public function second(): void
    {
    }
}
PHP);

        $firstPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->second();
    }
}
PHP);

        $secondPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($firstPartialBuild->usedPartialBuild());
        self::assertTrue($secondPartialBuild->usedPartialBuild());
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($secondPartialBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures consecutive opt-in partial builds remove declarations added by a previous partial build.
     *
     * @return void
     */
    public function testConsecutivePartialBuildsRemovePreviouslyAddedDeclarations(): void
    {
        $srcDirectory = $this->workspace . '/src-consecutive-partial-delete';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/consecutive-partial-delete.cache';
        $fullCacheFilePath = $this->workspace . '/consecutive-partial-delete-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->first();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function first(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function first(): void
    {
    }

    public function temporary(): void
    {
    }
}
PHP);

        $firstPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function first(): void
    {
    }
}
PHP);

        $secondPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($firstPartialBuild->usedPartialBuild());
        self::assertNotNull($firstPartialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'temporary',
            type: MemberType::METHOD,
        )));
        self::assertTrue($secondPartialBuild->usedPartialBuild());
        self::assertNull($secondPartialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'temporary',
            type: MemberType::METHOD,
        )));
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($secondPartialBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures consecutive opt-in partial builds remove files changed by a previous partial build.
     *
     * @return void
     */
    public function testConsecutivePartialBuildsRemovePreviouslyRebuiltFiles(): void
    {
        $srcDirectory = $this->workspace . '/src-consecutive-partial-file-delete';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $partialCacheFilePath = $this->workspace . '/consecutive-partial-file-delete.cache';
        $fullCacheFilePath = $this->workspace . '/consecutive-partial-file-delete-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        B::first();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public static function first(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public static function first(): void
    {
        $value = 1;
    }
}
PHP);

        $firstPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );

        $normalizedBFilePath = realpath($bFilePath) ?: $bFilePath;
        unlink($bFilePath);

        $secondPartialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($firstPartialBuild->usedPartialBuild());
        self::assertTrue($secondPartialBuild->usedPartialBuild());
        self::assertTrue($secondPartialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedBFilePath,
        ));
        self::assertNotNull($secondPartialBuild->buildReport->partialRebuildWorkingSet);
        self::assertFalse($secondPartialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            $normalizedBFilePath,
        ));
        self::assertNull($secondPartialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'first',
            type: MemberType::METHOD,
        )));
        self::assertSame(
            $this->declarationHashes($fullBuild->memberDependencyGraph),
            $this->declarationHashes($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->usageSignatures($fullBuild->memberDependencyGraph),
            $this->usageSignatures($secondPartialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($secondPartialBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures deleted files do not leave virtual-file references that can be reused by the next fast path.
     *
     * @return void
     */
    public function testPartialFileDeletionRemovesVirtualFileReferencesForNextFastPath(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-file-delete-fast-path';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $cacheFilePath = $this->workspace . '/partial-file-delete-fast-path.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function old(): void
    {
    }
}

final class C
{
    public function old(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
        );

        $normalizedBFilePath = realpath($bFilePath) ?: $bFilePath;
        unlink($bFilePath);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fastPathBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $cacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains($normalizedBFilePath));
        self::assertSame(0, $partialBuild->buildReport->loadedVirtualFileCount);
        self::assertSame(1, $partialBuild->buildReport->virtualFileReferenceCount);
        self::assertCount(0, $partialBuild->virtualFileReferences->getByFullFilePath($normalizedBFilePath));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'old',
            type: MemberType::METHOD,
        )));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\C',
            name: 'old',
            type: MemberType::METHOD,
        )));
        self::assertTrue($fastPathBuild->usedFastPath());
        self::assertSame(0, $fastPathBuild->buildReport->loadedVirtualFileCount);
        self::assertSame(1, $fastPathBuild->buildReport->virtualFileReferenceCount);
        self::assertCount(0, $fastPathBuild->virtualFileReferences->getByFullFilePath($normalizedBFilePath));
        self::assertNull($fastPathBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\B',
            name: 'old',
            type: MemberType::METHOD,
        )));
        self::assertNull($fastPathBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\C',
            name: 'old',
            type: MemberType::METHOD,
        )));
    }

    /**
     * Ensures opt-in partial builds handle a deleted file and a newly added replacement file together.
     *
     * @return void
     */
    public function testPartialBuildHandlesFileRenameLikeReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-file-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $oldServiceFilePath = $srcDirectory . '/OldService.php';
        $newServiceFilePath = $srcDirectory . '/NewService.php';
        $partialCacheFilePath = $this->workspace . '/partial-file-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-file-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        OldService::send();
    }
}
PHP);
        file_put_contents($oldServiceFilePath, <<<'PHP'
<?php

namespace App;

final class OldService
{
    public static function send(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): void
    {
        NewService::send();
    }
}
PHP);
        $normalizedOldServiceFilePath = realpath($oldServiceFilePath) ?: $oldServiceFilePath;
        unlink($oldServiceFilePath);
        file_put_contents($newServiceFilePath, <<<'PHP'
<?php

namespace App;

final class NewService
{
    public static function send(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedOldServiceFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($newServiceFilePath) ?: $newServiceFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\OldService',
            name: 'send',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewService',
            name: 'send',
            type: MemberType::METHOD,
        )));
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
    }

    /**
     * Ensures opt-in partial builds keep transitive consumers coherent during replacement.
     *
     * @return void
     */
    public function testPartialBuildHandlesTransitiveFileReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-transitive-file-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $bFilePath = $srcDirectory . '/B.php';
        $oldServiceFilePath = $srcDirectory . '/OldService.php';
        $newServiceFilePath = $srcDirectory . '/NewService.php';
        $partialCacheFilePath = $this->workspace . '/partial-transitive-file-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-transitive-file-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(B $b): void
    {
        $b->dispatch();
    }
}
PHP);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function dispatch(): void
    {
        OldService::run();
    }
}
PHP);
        file_put_contents($oldServiceFilePath, <<<'PHP'
<?php

namespace App;

final class OldService
{
    public static function run(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($bFilePath, <<<'PHP'
<?php

namespace App;

final class B
{
    public function dispatch(): void
    {
        NewService::run();
    }
}
PHP);
        $normalizedOldServiceFilePath = realpath($oldServiceFilePath) ?: $oldServiceFilePath;
        unlink($oldServiceFilePath);
        file_put_contents($newServiceFilePath, <<<'PHP'
<?php

namespace App;

final class NewService
{
    public static function run(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedOldServiceFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($bFilePath) ?: $bFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($newServiceFilePath) ?: $newServiceFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\OldService',
            name: 'run',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewService',
            name: 'run',
            type: MemberType::METHOD,
        )));
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
    }

    /**
     * Ensures opt-in partial builds handle a valid trait file replacement.
     *
     * @return void
     */
    public function testPartialBuildHandlesTraitFileReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-trait-file-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $oldTraitFilePath = $srcDirectory . '/OldSenderTrait.php';
        $newTraitFilePath = $srcDirectory . '/NewSenderTrait.php';
        $partialCacheFilePath = $this->workspace . '/partial-trait-file-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-trait-file-replacement-full.cache';

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

final class Service implements Contract
{
    use OldSenderTrait;
}
PHP);
        file_put_contents($oldTraitFilePath, <<<'PHP'
<?php

namespace App;

trait OldSenderTrait
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
    use NewSenderTrait;
}
PHP);
        $normalizedOldTraitFilePath = realpath($oldTraitFilePath) ?: $oldTraitFilePath;
        unlink($oldTraitFilePath);
        file_put_contents($newTraitFilePath, <<<'PHP'
<?php

namespace App;

trait NewSenderTrait
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
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedOldTraitFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($newTraitFilePath) ?: $newTraitFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\OldSenderTrait',
            name: 'send',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewSenderTrait',
            name: 'send',
            type: MemberType::METHOD,
        )));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Ensures opt-in partial builds handle a valid abstract-parent file replacement.
     *
     * @return void
     */
    public function testPartialBuildHandlesAbstractParentFileReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-parent-file-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $oldParentFilePath = $srcDirectory . '/OldAbstractRoot.php';
        $newParentFilePath = $srcDirectory . '/NewAbstractRoot.php';
        $partialCacheFilePath = $this->workspace . '/partial-parent-file-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-parent-file-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(OldAbstractRoot $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service extends OldAbstractRoot
{
    public function send(): void
    {
    }
}
PHP);
        file_put_contents($oldParentFilePath, <<<'PHP'
<?php

namespace App;

abstract class OldAbstractRoot
{
    abstract public function send(): void;
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(NewAbstractRoot $service): void
    {
        $service->send();
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service extends NewAbstractRoot
{
    public function send(): void
    {
    }
}
PHP);
        $normalizedOldParentFilePath = realpath($oldParentFilePath) ?: $oldParentFilePath;
        unlink($oldParentFilePath);
        file_put_contents($newParentFilePath, <<<'PHP'
<?php

namespace App;

abstract class NewAbstractRoot
{
    abstract public function send(): void;
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
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToDelete->contains(
            $normalizedOldParentFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($newParentFilePath) ?: $newParentFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\OldAbstractRoot',
            name: 'send',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\NewAbstractRoot',
            name: 'send',
            type: MemberType::METHOD,
        )));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Ensures opt-in partial builds replace methods inside the same owner without stale usages.
     *
     * @return void
     */
    public function testPartialBuildHandlesMethodReplacementInsideSameOwner(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-method-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-method-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-method-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): void
    {
        $service->oldRun();
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function oldRun(): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): void
    {
        $service->newRun();
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function newRun(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'oldRun',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'newRun',
            type: MemberType::METHOD,
        )));
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
    }

    /**
     * Ensures opt-in partial builds replace named parameters without stale parameter usages.
     *
     * @return void
     */
    public function testPartialBuildHandlesNamedParameterReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-named-parameter-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-named-parameter-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-named-parameter-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): void
    {
        $service->send(old: 'value');
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(string $old): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): void
    {
        $service->send(new: 'value');
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(string $new): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertFalse($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'old',
        ));
        self::assertTrue($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'new',
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
            $this->parameterUsageSignatures($fullBuild->memberDependencyGraph),
            $this->parameterUsageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures partial builds rebuild stale named-argument consumers when only a parameter declaration changes.
     *
     * @return void
     */
    public function testPartialBuildExpandsNamedParameterDeclarationChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-named-parameter-impact';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-named-parameter-impact.cache';
        $fullCacheFilePath = $this->workspace . '/partial-named-parameter-impact-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): void
    {
        $service->send(old: 'value');
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public function send(string $old): void
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
    public function send(string $new): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
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
        self::assertTrue($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'old',
        ));
        self::assertFalse($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'new',
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
            $this->parameterUsageSignatures($fullBuild->memberDependencyGraph),
            $this->parameterUsageSignatures($partialBuild->memberDependencyGraph),
        );
        self::assertSame(
            $this->availableMemberSignatures($fullBuild->memberDependencyGraph),
            $this->availableMemberSignatures($partialBuild->memberDependencyGraph),
        );
    }

    /**
     * Ensures partial builds handle valid named-parameter changes through an interface implementation.
     *
     * @return void
     */
    public function testPartialBuildHandlesPolymorphicNamedParameterReplacement(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-polymorphic-named-parameter-replacement';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-polymorphic-named-parameter-replacement.cache';
        $fullCacheFilePath = $this->workspace . '/partial-polymorphic-named-parameter-replacement-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Contract $service): void
    {
        $service->send(old: 'value');
    }
}
PHP);
        file_put_contents($contractFilePath, <<<'PHP'
<?php

namespace App;

interface Contract
{
    public function send(string $old): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements Contract
{
    public function send(string $old): void
    {
    }
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Contract $service): void
    {
        $service->send(new: 'value');
    }
}
PHP);
        file_put_contents($contractFilePath, <<<'PHP'
<?php

namespace App;

interface Contract
{
    public function send(string $new): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements Contract
{
    public function send(string $new): void
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
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($contractFilePath) ?: $contractFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertFalse($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'old',
        ));
        self::assertTrue($this->parameterUsagesContainName(
            $partialBuild->memberDependencyGraph,
            'new',
        ));
        $this->assertPartialAndFastPathMatchFullBuild($partialBuild, $fastPathBuild, $fullBuild);
    }

    /**
     * Ensures partial builds rebuild stale property consumers when only a property declaration changes.
     *
     * @return void
     */
    public function testPartialBuildExpandsPropertyDeclarationChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-property-impact';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-property-impact.cache';
        $fullCacheFilePath = $this->workspace . '/partial-property-impact-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(Service $service): string
    {
        return $service->old;
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public string $old = 'value';
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
    public string $new = 'value';
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
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
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'old',
            type: MemberType::PROPERTY,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'new',
            type: MemberType::PROPERTY,
        )));
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
    }

    /**
     * Ensures partial builds rebuild stale class-constant consumers when only a constant declaration changes.
     *
     * @return void
     */
    public function testPartialBuildExpandsClassConstantDeclarationChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-class-constant-impact';
        $aFilePath = $srcDirectory . '/A.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-class-constant-impact.cache';
        $fullCacheFilePath = $this->workspace . '/partial-class-constant-impact-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): string
    {
        return Service::OLD_VALUE;
    }
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service
{
    public const string OLD_VALUE = 'value';
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
    public const string NEW_VALUE = 'value';
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
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
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'OLD_VALUE',
            type: MemberType::CLASS_CONSTANT,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Service',
            name: 'NEW_VALUE',
            type: MemberType::CLASS_CONSTANT,
        )));
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
    }

    /**
     * Ensures partial builds rebuild stale function-call consumers when only a function declaration changes.
     *
     * @return void
     */
    public function testPartialBuildExpandsFunctionDeclarationChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-function-impact';
        $aFilePath = $srcDirectory . '/A.php';
        $functionsFilePath = $srcDirectory . '/functions.php';
        $partialCacheFilePath = $this->workspace . '/partial-function-impact.cache';
        $fullCacheFilePath = $this->workspace . '/partial-function-impact-full.cache';

        mkdir($srcDirectory, 0777, true);
        file_put_contents($aFilePath, <<<'PHP'
<?php

namespace App;

final class A
{
    public function run(): string
    {
        return old_helper();
    }
}
PHP);
        file_put_contents($functionsFilePath, <<<'PHP'
<?php

namespace App;

function old_helper(): string
{
    return 'value';
}
PHP);

        MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
        );

        sleep(1);
        file_put_contents($functionsFilePath, <<<'PHP'
<?php

namespace App;

function new_helper(): string
{
    return 'value';
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($functionsFilePath) ?: $functionsFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: '',
            name: 'App\\old_helper',
            type: MemberType::FUNCTION_,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: '',
            name: 'App\\new_helper',
            type: MemberType::FUNCTION_,
        )));
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
    }

    /**
     * Ensures partial builds rebuild stale interface consumers when an interface method changes.
     *
     * @return void
     */
    public function testPartialBuildExpandsInterfaceMethodDeclarationChangeToConsumer(): void
    {
        $srcDirectory = $this->workspace . '/src-partial-interface-impact';
        $aFilePath = $srcDirectory . '/A.php';
        $contractFilePath = $srcDirectory . '/Contract.php';
        $serviceFilePath = $srcDirectory . '/Service.php';
        $partialCacheFilePath = $this->workspace . '/partial-interface-impact.cache';
        $fullCacheFilePath = $this->workspace . '/partial-interface-impact-full.cache';

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
        file_put_contents($contractFilePath, <<<'PHP'
<?php

namespace App;

interface Contract
{
    public function dispatch(): void;
}
PHP);
        file_put_contents($serviceFilePath, <<<'PHP'
<?php

namespace App;

final class Service implements Contract
{
    public function dispatch(): void
    {
    }
}
PHP);

        $partialBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $partialCacheFilePath,
            options: new MemberDependencyGraphFactoryOptions(enablePartialRebuild: true),
        );
        $fullBuild = MemberDependencyGraphFactory::fromDirectory(
            directories: [$srcDirectory],
            cacheFilePath: $fullCacheFilePath,
            clearCache: true,
        );

        self::assertTrue($partialBuild->usedPartialBuild());
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($contractFilePath) ?: $contractFilePath,
        ));
        self::assertTrue($partialBuild->buildReport->rebuildPlan->filesToBuild->contains(
            realpath($serviceFilePath) ?: $serviceFilePath,
        ));
        self::assertNotNull($partialBuild->buildReport->partialRebuildWorkingSet);
        self::assertTrue($partialBuild->buildReport->partialRebuildWorkingSet->hasFileToRebuildGraph(
            realpath($aFilePath) ?: $aFilePath,
        ));
        self::assertNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Contract',
            name: 'send',
            type: MemberType::METHOD,
        )));
        self::assertNotNull($partialBuild->memberDependencyGraph->declarations->get(new MemberId(
            owner: 'App\\Contract',
            name: 'dispatch',
            type: MemberType::METHOD,
        )));
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
    }

}
