<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Plan\MemberGraphCacheFileCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\FunctionDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotMerger;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\TemplateDeclarationSnapshot;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PHPUnit\Framework\TestCase;

/**
 * Covers declaration snapshot merging for future partial rebuilds.
 */
final class MemberGraphDeclarationSnapshotMergerTest extends TestCase
{
    /**
     * Ensures rebuilt files replace all cached declarations from the same physical files.
     *
     * @return void
     */
    public function testItRemovesCachedDeclarationsForRebuiltFilesBeforeAddingLoadedDeclarations(): void
    {
        $reusableFilePath = '/project/src/Reusable.php';
        $changedFilePath = '/project/src/Changed.php';
        $filesToBuild = new MemberGraphCacheFileCollection();
        $cachedSnapshot = new MemberGraphDeclarationSnapshot();
        $loadedSnapshot = new MemberGraphDeclarationSnapshot();

        $filesToBuild->add($changedFilePath);
        $this->addOwnerFamily($cachedSnapshot, $reusableFilePath, 'App\\Reusable', 'reuse');
        $this->addOwnerFamily($cachedSnapshot, $changedFilePath, 'App\\Changed', 'old');
        $this->addOwnerFamily($loadedSnapshot, $changedFilePath, 'App\\Changed', 'new');

        $merged = new MemberGraphDeclarationSnapshotMerger()->merge(
            cachedSnapshot: $cachedSnapshot,
            loadedSnapshot: $loadedSnapshot,
            filesToBuild: $filesToBuild,
        );

        self::assertCount(2, $merged->owners);
        self::assertSame('App\\ReusableParent', $merged->owners->get('App\\Reusable')?->parentFqcn);
        self::assertSame('App\\ChangedParentNew', $merged->owners->get('App\\Changed')?->parentFqcn);
        self::assertSame('newReturn', $merged->methods->get('App\\Changed', 'run')?->nativeReturnType);
        self::assertNull($merged->methods->get('App\\Changed', 'deleted'));
        self::assertSame('newParam', $merged->parameters->get('App\\Changed::run', 'value')?->nativeType);
        self::assertNull($merged->parameters->get('App\\Changed::deleted', 'value'));
        self::assertSame('newFunctionReturn', $merged->functions->get('App\\changedFunction')?->nativeReturnType);
        self::assertNull($merged->functions->get('App\\deletedFunction'));
        self::assertSame('newProperty', $merged->properties->get('App\\Changed', 'value')?->nativeType);
        self::assertNull($merged->properties->get('App\\Changed', 'deleted'));
        self::assertSame('new', $merged->classConstants->get('App\\Changed', 'VALUE')?->scalarValue);
        self::assertNull($merged->classConstants->get('App\\Changed', 'DELETED'));
        self::assertSame('newBound', $merged->templates->get('App\\Changed', 'T')?->boundType);
        self::assertNull($merged->templates->get('App\\Changed::deleted', 'T'));
    }

    /**
     * Adds an owner and related member declarations.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot The snapshot to populate.
     * @param string $filePath The physical file path.
     * @param string $ownerFqcn The owner FQCN.
     * @param string $variant The declaration variant.
     *
     * @return void
     */
    private function addOwnerFamily(
        MemberGraphDeclarationSnapshot $snapshot,
        string $filePath,
        string $ownerFqcn,
        string $variant,
    ): void {
        $isNewVariant = 'new' === $variant;
        $ownerSuffix = $isNewVariant ? 'New' : '';
        $valuePrefix = $variant;

        $snapshot->owners->add(new OwnerDeclarationSnapshot(
            fqcn: $ownerFqcn,
            kind: OwnerKind::CLASS_,
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
            parentFqcn: $ownerFqcn . 'Parent' . $ownerSuffix,
        ));
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'run',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
            nativeReturnType: $valuePrefix . 'Return',
        ));
        $snapshot->parameters->add(new ParameterDeclarationSnapshot(
            callableId: $ownerFqcn . '::run',
            name: 'value',
            nativeType: $valuePrefix . 'Param',
        ));
        $snapshot->functions->add(new FunctionDeclarationSnapshot(
            name: str_replace('Changed', 'changedFunction', $ownerFqcn),
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
            nativeReturnType: $valuePrefix . 'FunctionReturn',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'value',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
            nativeType: $valuePrefix . 'Property',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'VALUE',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
            scalarValue: $valuePrefix,
        ));
        $snapshot->templates->add(new TemplateDeclarationSnapshot(
            scopeId: $ownerFqcn,
            name: 'T',
            boundType: $valuePrefix . 'Bound',
        ));

        if (!$isNewVariant) {
            $this->addDeletedDeclarations($snapshot, $filePath, $ownerFqcn);
        }
    }

    /**
     * Adds declarations that should disappear when their file is rebuilt.
     *
     * @param MemberGraphDeclarationSnapshot $snapshot The snapshot to populate.
     * @param string $filePath The physical file path.
     * @param string $ownerFqcn The owner FQCN.
     *
     * @return void
     */
    private function addDeletedDeclarations(
        MemberGraphDeclarationSnapshot $snapshot,
        string $filePath,
        string $ownerFqcn,
    ): void {
        $snapshot->methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'deleted',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
        ));
        $snapshot->parameters->add(new ParameterDeclarationSnapshot(
            callableId: $ownerFqcn . '::deleted',
            name: 'value',
        ));
        $snapshot->functions->add(new FunctionDeclarationSnapshot(
            name: str_replace('Changed', 'deletedFunction', $ownerFqcn),
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
        ));
        $snapshot->properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'deleted',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
        ));
        $snapshot->classConstants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: $ownerFqcn,
            name: 'DELETED',
            fullFilePath: $filePath,
            virtualFilePath: $filePath . '.virtual.0',
        ));
        $snapshot->templates->add(new TemplateDeclarationSnapshot(
            scopeId: $ownerFqcn . '::deleted',
            name: 'T',
        ));
    }
}
