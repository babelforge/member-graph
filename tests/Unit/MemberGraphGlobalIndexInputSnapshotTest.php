<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphGlobalIndexInputSnapshotBuilder;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadata;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\MemberGraphVirtualSourceMetadataCollection;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwner;
use PhpNoobs\MemberGraph\Domain\Owner\KnownOwnerCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PHPUnit\Framework\TestCase;

/**
 * Covers global-index input snapshots used by member graph cache planning.
 */
final class MemberGraphGlobalIndexInputSnapshotTest extends TestCase
{
    /**
     * Ensures virtual source metadata can be created from a known owner.
     *
     * @return void
     */
    public function testVirtualSourceMetadataCanBeCreatedFromKnownOwner(): void
    {
        $knownOwner = new KnownOwner(
            fqcn: 'App\\Service\\Mailer',
            parentFqcn: 'App\\Service\\AbstractMailer',
            kind: OwnerKind::CLASS_,
            isAbstract: false,
            traits: ['App\\Service\\LogsMessages'],
            interfaces: ['App\\Contract\\Sender'],
        );

        $metadata = MemberGraphVirtualSourceMetadata::fromKnownOwner(
            fullFilePath: '/project/src/Mailer.php',
            virtualFilePath: '/project/src/Mailer.php.virtual.0',
            knownOwner: $knownOwner,
            namespace: 'App\\Service',
        );

        self::assertTrue($metadata->hasOwner());
        self::assertSame('/project/src/Mailer.php', $metadata->fullFilePath);
        self::assertSame('/project/src/Mailer.php.virtual.0', $metadata->virtualFilePath);
        self::assertSame('App\\Service', $metadata->namespace);
        self::assertSame('App\\Service\\Mailer', $metadata->ownerName);
        self::assertSame(OwnerKind::CLASS_, $metadata->ownerKind);
        self::assertSame('App\\Service\\AbstractMailer', $metadata->parentFqcn);
        self::assertSame(['App\\Service\\LogsMessages'], $metadata->traits);
        self::assertSame(['App\\Contract\\Sender'], $metadata->interfaces);
    }

    /**
     * Ensures virtual source metadata collections are indexed by virtual file path.
     *
     * @return void
     */
    public function testVirtualSourceMetadataCollectionIndexesByVirtualFilePath(): void
    {
        $collection = new MemberGraphVirtualSourceMetadataCollection();
        $metadata = new MemberGraphVirtualSourceMetadata(
            fullFilePath: '/project/src/functions.php',
            virtualFilePath: '/project/src/functions.php.virtual.0',
        );

        $collection->add($metadata);

        self::assertCount(1, $collection);
        self::assertSame($metadata, $collection->get('/project/src/functions.php.virtual.0'));
        self::assertFalse($metadata->hasOwner());
    }

    /**
     * Ensures snapshots expose compatibility through schema and builder versions.
     *
     * @return void
     */
    public function testSnapshotCompatibilityDependsOnSchemaAndBuilderVersions(): void
    {
        self::assertTrue(new MemberGraphGlobalIndexInputSnapshot()->isCompatible());
        self::assertFalse(new MemberGraphGlobalIndexInputSnapshot(
            schemaVersion: MemberGraphGlobalIndexInputSnapshot::SCHEMA_VERSION + 1,
        )->isCompatible());
        self::assertFalse(new MemberGraphGlobalIndexInputSnapshot(
            builderVersion: 'member-graph-global-index-input-v2',
        )->isCompatible());
    }

    /**
     * Ensures the snapshot builder extracts owner metadata from loaded virtual files.
     *
     * @return void
     */
    public function testSnapshotBuilderExtractsOwnerMetadataFromVirtualFiles(): void
    {
        $knownOwners = new KnownOwnerCollection();
        $knownOwners->add(new KnownOwner(
            fqcn: 'App\\Service\\Mailer',
            parentFqcn: null,
            kind: OwnerKind::CLASS_,
        ));
        $virtualFile = new VirtualPhpSourceFile(
            fullFilePath: '/project/src/Mailer.php',
            virtualFilePath: '/project/src/Mailer.php.virtual.0',
            nodes: [
                new Namespace_(new Name('App\\Service'), [
                    new Class_('Mailer'),
                ]),
            ],
        );
        $virtualFiles = new VirtualPhpSourceFileCollection()->add($virtualFile);

        $snapshot = new MemberGraphGlobalIndexInputSnapshotBuilder()->build($virtualFiles, $knownOwners);
        $metadata = $snapshot->sources->get('/project/src/Mailer.php.virtual.0');

        self::assertNotNull($metadata);
        self::assertTrue($snapshot->isCompatible());
        self::assertTrue($metadata->hasOwner());
        self::assertSame('App\\Service', $metadata->namespace);
        self::assertSame('App\\Service\\Mailer', $metadata->ownerName);
        self::assertSame(OwnerKind::CLASS_, $metadata->ownerKind);
    }

    /**
     * Ensures the snapshot builder keeps source metadata for virtual files without owners.
     *
     * @return void
     */
    public function testSnapshotBuilderKeepsSourceMetadataWithoutOwner(): void
    {
        $knownOwners = new KnownOwnerCollection();
        $virtualFile = new VirtualPhpSourceFile(
            fullFilePath: '/project/src/functions.php',
            virtualFilePath: '/project/src/functions.php.virtual.0',
            nodes: [
                new Namespace_(new Name('App\\Support'), [
                    new Function_('helper'),
                ]),
            ],
        );
        $virtualFiles = new VirtualPhpSourceFileCollection()->add($virtualFile);

        $snapshot = new MemberGraphGlobalIndexInputSnapshotBuilder()->build($virtualFiles, $knownOwners);
        $metadata = $snapshot->sources->get('/project/src/functions.php.virtual.0');

        self::assertNotNull($metadata);
        self::assertFalse($metadata->hasOwner());
        self::assertSame('App\\Support', $metadata->namespace);
        self::assertNull($metadata->ownerName);
        self::assertNull($metadata->ownerKind);
    }
}
