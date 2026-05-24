<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnostic;
use BabelForge\MemberGraph\Application\Build\PartialGraph\Diagnostics\MemberDependencyGraphPartialRebuildClosureDiagnosticReason;
use BabelForge\MemberGraph\Application\Build\PartialGraph\WorkingSet\MemberDependencyGraphPartialRebuildWorkingSet;
use BabelForge\MemberGraph\Application\Cache\Fragment\MemberGraphFragmentCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers partial rebuild working set DTO behavior.
 */
final class MemberDependencyGraphPartialRebuildWorkingSetTest extends TestCase
{
    /**
     * Ensures working set files, fragments, diagnostics, and iterations are tracked.
     */
    public function testItTracksWorkingSetState(): void
    {
        $workingSet = new MemberDependencyGraphPartialRebuildWorkingSet();
        $fragmentsToReuse = new MemberGraphFragmentCollection();

        $workingSet
            ->addFileToParseForContext('/project/src/Context.php')
            ->addFileToParseForContext('/project/src/Context.php')
            ->addFileToRebuildGraph('/project/src/Changed.php')
            ->addFileToRebuildGraph('/project/src/Changed.php')
            ->setFragmentsToReuse($fragmentsToReuse)
            ->setIterations(3)
            ->addDiagnostic(new MemberDependencyGraphPartialRebuildClosureDiagnostic(
                reason: MemberDependencyGraphPartialRebuildClosureDiagnosticReason::UNRESOLVED_REFERENCE,
                message: 'Unable to map reference to a file.',
                sourceFilePath: '/project/src/Changed.php',
                reference: 'App\\Missing',
            ));

        self::assertTrue($workingSet->hasFileToParseForContext('/project/src/Context.php'));
        self::assertTrue($workingSet->hasFileToRebuildGraph('/project/src/Changed.php'));
        self::assertFalse($workingSet->hasFileToRebuildGraph('/project/src/Context.php'));
        self::assertCount(1, $workingSet->filesToParseForContext);
        self::assertCount(1, $workingSet->filesToRebuildGraph);
        self::assertSame($fragmentsToReuse, $workingSet->fragmentsToReuse);
        self::assertSame(3, $workingSet->iterations);
        self::assertTrue($workingSet->hasDiagnostics());
        self::assertCount(1, $workingSet->diagnostics);
        self::assertSame(
            MemberDependencyGraphPartialRebuildClosureDiagnosticReason::UNRESOLVED_REFERENCE,
            $workingSet->diagnostics->all()[0]->reason,
        );
    }
}
