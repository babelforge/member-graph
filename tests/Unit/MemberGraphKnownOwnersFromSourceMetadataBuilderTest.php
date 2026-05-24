<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Unit;

use BabelForge\MemberGraph\Application\Build\GlobalIndex\MemberGraphKnownOwnersFromSourceMetadataBuilder;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use BabelForge\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use BabelForge\MemberGraph\Domain\Owner\OwnerKind;
use PHPUnit\Framework\TestCase;

/**
 * Covers known owner rebuilding from cacheable source metadata.
 */
final class MemberGraphKnownOwnersFromSourceMetadataBuilderTest extends TestCase
{
    /**
     * Ensures source metadata can rebuild known owners without PHPParser nodes.
     */
    public function testItBuildsKnownOwnersFromSourceMetadata(): void
    {
        $sourceMetadata = new MemberGraphVirtualSourceMetadataCollection();
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Reusable.php',
            virtualFilePath: '/project/src/Reusable.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Reusable',
            ownerKind: OwnerKind::CLASS_,
            parentFqcn: 'App\\BaseReusable',
            isAbstract: true,
            traits: ['App\\ReusableTrait'],
            interfaces: ['App\\ReusableContract'],
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Changed.php',
            virtualFilePath: '/project/src/Changed.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Changed',
            ownerKind: OwnerKind::CLASS_,
            parentFqcn: 'App\\OldChangedParent',
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/functions.php',
            virtualFilePath: '/project/src/functions.php.virtual.0',
            namespace: 'App',
        ));
        $sourceMetadata->add(new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/Changed.php',
            virtualFilePath: '/project/src/Changed.php.virtual.0',
            namespace: 'App',
            ownerName: 'App\\Changed',
            ownerKind: OwnerKind::CLASS_,
            parentFqcn: 'App\\ChangedParent',
            interfaces: ['App\\ChangedContract'],
        ));

        $knownOwners = new MemberGraphKnownOwnersFromSourceMetadataBuilder()->build($sourceMetadata);
        $reusableOwner = $knownOwners->get('App\\Reusable');
        $changedOwner = $knownOwners->get('App\\Changed');

        self::assertCount(2, $knownOwners);
        self::assertNotNull($reusableOwner);
        self::assertSame(OwnerKind::CLASS_, $reusableOwner->kind);
        self::assertSame('App\\BaseReusable', $reusableOwner->parentFqcn);
        self::assertTrue($reusableOwner->isAbstract);
        self::assertSame(['App\\ReusableTrait'], $reusableOwner->traits);
        self::assertSame(['App\\ReusableContract'], $reusableOwner->interfaces);
        self::assertNotNull($changedOwner);
        self::assertSame('App\\ChangedParent', $changedOwner->parentFqcn);
        self::assertSame(['App\\ChangedContract'], $changedOwner->interfaces);
        self::assertSame(['App\\Reusable', 'App\\Changed'], array_keys($knownOwners->all()));
    }
}
