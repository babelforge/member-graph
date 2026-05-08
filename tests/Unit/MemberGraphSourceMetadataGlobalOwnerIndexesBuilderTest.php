<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Build\GlobalIndex\MemberGraphSourceMetadataGlobalOwnerIndexesBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PHPUnit\Framework\TestCase;

/**
 * Covers global owner index rebuilding from cacheable source metadata.
 */
final class MemberGraphSourceMetadataGlobalOwnerIndexesBuilderTest extends TestCase
{
    /**
     * Ensures source metadata can rebuild known owners and polymorphic implementations.
     */
    public function testItBuildsGlobalOwnerIndexesFromSourceMetadata(): void
    {
        $sourceMetadata = new MemberGraphVirtualSourceMetadataCollection();
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Contract.php',
            virtualFilePath: '/project/src/Contract.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Contract',
            ownerKind: OwnerKind::INTERFACE,
            extendsInterfaces: ['App\\RootContract'],
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/RootContract.php',
            virtualFilePath: '/project/src/RootContract.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\RootContract',
            ownerKind: OwnerKind::INTERFACE,
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/BaseService.php',
            virtualFilePath: '/project/src/BaseService.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\BaseService',
            ownerKind: OwnerKind::CLASS_,
            isAbstract: true,
            interfaces: ['App\\Contract'],
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/ConcreteService.php',
            virtualFilePath: '/project/src/ConcreteService.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\ConcreteService',
            ownerKind: OwnerKind::CLASS_,
            parentFqcn: 'App\\BaseService',
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/OtherService.php',
            virtualFilePath: '/project/src/OtherService.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\OtherService',
            ownerKind: OwnerKind::CLASS_,
            interfaces: ['App\\Contract'],
        ));

        $indexes = new MemberGraphSourceMetadataGlobalOwnerIndexesBuilder()->build($sourceMetadata);

        self::assertCount(5, $indexes->knownOwners);
        self::assertNotNull($indexes->knownOwners->get('App\\ConcreteService'));
        self::assertSame(
            ['App\\ConcreteService'],
            $indexes->polymorphicImplementationsIndex->getImplementations('App\\BaseService'),
        );
        self::assertSame(
            ['App\\ConcreteService', 'App\\OtherService'],
            $indexes->polymorphicImplementationsIndex->getImplementations('App\\Contract'),
        );
        self::assertSame(
            ['App\\ConcreteService', 'App\\OtherService'],
            $indexes->polymorphicImplementationsIndex->getImplementations('App\\RootContract'),
        );
    }
}
