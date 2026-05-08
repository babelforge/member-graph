<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphDeclarationFlatMemberIndexesBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * Covers flat member index rebuilding from declaration snapshots.
 */
final class MemberGraphDeclarationFlatMemberIndexesBuilderTest extends TestCase
{
    /**
     * Ensures property and class constant indexes can be rebuilt without PHPParser nodes.
     */
    public function testItBuildsFlatMemberIndexesFromDeclarationSnapshots(): void
    {
        $snapshot = new MemberGraphDeclarationSnapshot();
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'id',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
            nativeType: 'int',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'name',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
            nativeType: '?string',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'related',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
            nativeType: 'App\\Foo|App\\Bar',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'untyped',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'STATUS',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
            scalarValue: 'active',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Entity',
            name: 'LIMIT',
            fullFilePath: '/project/src/Entity.php',
            virtualFilePath: '/project/src/Entity.php.virtual.0',
            scalarValue: 12,
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Status',
            name: 'ACTIVE',
            fullFilePath: '/project/src/Status.php',
            virtualFilePath: '/project/src/Status.php.virtual.0',
            isEnumCase: true,
        ));

        $indexes = new MemberGraphDeclarationFlatMemberIndexesBuilder()->build($snapshot);

        self::assertSame(['int'], $indexes->propertyTypeIndex->get('App\\Entity', 'id')->all());
        self::assertSame(['string'], $indexes->propertyTypeIndex->get('App\\Entity', 'name')->all());
        self::assertSame(['App\\Foo', 'App\\Bar'], $indexes->propertyTypeIndex->get('App\\Entity', 'related')->all());
        self::assertTrue($indexes->propertyTypeIndex->get('App\\Entity', 'untyped')->isEmpty());
        self::assertSame('App\\Entity', $indexes->classConstantTypeIndex->get('App\\Entity', 'STATUS'));
        self::assertSame('active', $indexes->classConstantValueIndex->get('App\\Entity', 'STATUS'));
        self::assertSame(12, $indexes->classConstantValueIndex->get('App\\Entity', 'LIMIT'));
        self::assertSame('App\\Status', $indexes->classConstantTypeIndex->get('App\\Status', 'ACTIVE'));
        self::assertNull($indexes->classConstantValueIndex->get('App\\Status', 'ACTIVE'));
    }
}
