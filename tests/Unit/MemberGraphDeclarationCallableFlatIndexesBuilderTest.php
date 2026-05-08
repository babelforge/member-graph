<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphDeclarationCallableFlatIndexesBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\FunctionDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshotCollection;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPUnit\Framework\TestCase;

/**
 * Covers callable flat index rebuilding from declaration snapshots.
 */
final class MemberGraphDeclarationCallableFlatIndexesBuilderTest extends TestCase
{
    /**
     * Ensures method and function flat indexes can be rebuilt from declaration snapshots.
     *
     * @return void
     */
    public function testItBuildsCallableFlatIndexesFromDeclarationSnapshots(): void
    {
        $methodParameters = new ParameterDeclarationSnapshotCollection();
        $functionParameters = new ParameterDeclarationSnapshotCollection();
        $snapshot = new MemberGraphDeclarationSnapshot();

        $methodParameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\Service::run',
            name: 'id',
            nativeType: 'int',
        ));
        $methodParameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\Service::run',
            name: 'item',
            nativeType: 'App\\Foo|App\\Bar',
        ));
        $functionParameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\build',
            name: 'name',
            nativeType: '?string',
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Service',
            name: 'run',
            fullFilePath: '/project/src/Service.php',
            virtualFilePath: '/project/src/Service.php.virtual.0',
            nativeReturnType: 'App\\Result',
            parameters: $methodParameters,
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Service',
            name: 'fromPhpDoc',
            fullFilePath: '/project/src/Service.php',
            virtualFilePath: '/project/src/Service.php.virtual.0',
            phpDocReturnType: 'App\\DocResult',
        ));
        $snapshot->functions->add(new FunctionDeclarationSnapshot(
            name: 'App\\build',
            fullFilePath: '/project/src/functions.php',
            virtualFilePath: '/project/src/functions.php.virtual.0',
            namespace: 'App',
            nativeReturnType: 'App\\Output|App\\Fallback',
            parameters: $functionParameters,
        ));

        $indexes = new MemberGraphDeclarationCallableFlatIndexesBuilder()->build($snapshot);
        $methodReturnDetails = $indexes->methodReturnTypeIndex->get('App\\Service', 'run');
        $functionReturnDetails = $indexes->functionReturnTypeIndex->get('App\\build');

        self::assertNotNull($methodReturnDetails);
        self::assertNotNull($functionReturnDetails);

        $methodParentNode = $methodReturnDetails->parentNode;
        $functionParentNode = $functionReturnDetails->parentNode;

        self::assertInstanceOf(ClassMethod::class, $methodParentNode);
        self::assertInstanceOf(Function_::class, $functionParentNode);

        self::assertSame(['App\\Result'], $indexes->methodReturnTypeIndex->getReturnType('App\\Service', 'run')->all());
        self::assertSame(['App\\DocResult'], $indexes->methodReturnTypeIndex->getReturnType('App\\Service', 'fromPhpDoc')->all());
        self::assertSame(['int'], $indexes->methodParameterTypeIndex->getType('App\\Service', 'run', 'id')->all());
        self::assertSame(['App\\Foo', 'App\\Bar'], $indexes->methodParameterTypeIndex->getType('App\\Service', 'run', 'item')->all());
        self::assertSame(['App\\Output', 'App\\Fallback'], $indexes->functionReturnTypeIndex->getReturnType('App\\build')->all());
        self::assertSame(['string'], $indexes->functionParameterTypeIndex->getType('App\\build', 'name')->all());
        self::assertInstanceOf(Variable::class, $methodParentNode->params[0]->var);
        self::assertInstanceOf(Variable::class, $methodParentNode->params[1]->var);
        self::assertSame('id', $methodParentNode->params[0]->var->name);
        self::assertSame('item', $methodParentNode->params[1]->var->name);
        self::assertNotNull($functionReturnDetails->getReturnType());
        self::assertInstanceOf(Variable::class, $functionParentNode->params[0]->var);
        self::assertSame('name', $functionParentNode->params[0]->var->name);
    }
}
