<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ClassConstantDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\FunctionDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\FunctionDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MethodDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\OwnerDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\ParameterDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\PropertyDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\TemplateDeclarationSnapshot;
use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\TemplateDeclarationSnapshotCollection;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PHPUnit\Framework\TestCase;

/**
 * Covers cacheable declaration snapshot DTOs.
 */
final class MemberGraphDeclarationSnapshotTest extends TestCase
{
    /**
     * Ensures owner declaration snapshots keep class-like metadata and templates.
     *
     * @return void
     */
    public function testItStoresOwnerDeclarationSnapshots(): void
    {
        $templates = new TemplateDeclarationSnapshotCollection();
        $owners = new OwnerDeclarationSnapshotCollection();

        $templates->add(new TemplateDeclarationSnapshot(
            scopeId: 'App\\Box',
            name: 'T',
            boundType: 'App\\Contract',
        ));
        $owners->add(new OwnerDeclarationSnapshot(
            fqcn: 'App\\Box',
            kind: OwnerKind::CLASS_,
            fullFilePath: '/project/src/Box.php',
            virtualFilePath: '/project/src/Box.php.virtual.0',
            namespace: 'App',
            parentFqcn: 'App\\BaseBox',
            isAbstract: true,
            traits: ['App\\Timestamped'],
            interfaces: ['App\\BoxContract'],
            templates: $templates,
        ));

        $snapshot = $owners->get('App\\Box');

        self::assertCount(1, $owners);
        self::assertSame(OwnerKind::CLASS_, $snapshot?->kind);
        self::assertSame('App\\Contract', $snapshot->templates->get('App\\Box', 'T')?->boundType);
        self::assertSame(['App\\Timestamped'], $snapshot->traits);
        self::assertSame(['App\\BoxContract'], $snapshot->interfaces);
    }

    /**
     * Ensures callable declaration snapshots keep parameters and templates.
     *
     * @return void
     */
    public function testItStoresCallableDeclarationSnapshots(): void
    {
        $methodParameters = new ParameterDeclarationSnapshotCollection();
        $methodTemplates = new TemplateDeclarationSnapshotCollection();
        $methods = new MethodDeclarationSnapshotCollection();
        $functions = new FunctionDeclarationSnapshotCollection();

        $methodParameters->add(new ParameterDeclarationSnapshot(
            callableId: 'App\\Box::get',
            name: 'id',
            nativeType: 'int',
            phpDocType: 'positive-int',
            hasDefault: true,
        ));
        $methodTemplates->add(new TemplateDeclarationSnapshot(
            scopeId: 'App\\Box::get',
            name: 'TValue',
            boundType: 'object',
        ));
        $methods->add(new MethodDeclarationSnapshot(
            ownerFqcn: 'App\\Box',
            name: 'get',
            fullFilePath: '/project/src/Box.php',
            virtualFilePath: '/project/src/Box.php.virtual.0',
            visibility: 'protected',
            nativeReturnType: 'object',
            phpDocReturnType: 'TValue',
            parameters: $methodParameters,
            templates: $methodTemplates,
        ));
        $functions->add(new FunctionDeclarationSnapshot(
            name: 'App\\make_box',
            fullFilePath: '/project/src/functions.php',
            virtualFilePath: '/project/src/functions.php.virtual.0',
            namespace: 'App',
            nativeReturnType: 'Box',
        ));

        $method = $methods->get('App\\Box', 'get');

        self::assertSame('App\\Box::get', $method?->callableId());
        self::assertSame('positive-int', $method->parameters->get('App\\Box::get', 'id')->phpDocType);
        self::assertSame('object', $method->templates->get('App\\Box::get', 'TValue')->boundType);
        self::assertSame('Box', $functions->get('App\\make_box')?->nativeReturnType);
    }

    /**
     * Ensures member declaration snapshots are indexed by owner and member name.
     *
     * @return void
     */
    public function testItStoresPropertyAndClassConstantDeclarationSnapshots(): void
    {
        $properties = new PropertyDeclarationSnapshotCollection();
        $constants = new ClassConstantDeclarationSnapshotCollection();

        $properties->add(new PropertyDeclarationSnapshot(
            ownerFqcn: 'App\\Box',
            name: 'value',
            fullFilePath: '/project/src/Box.php',
            virtualFilePath: '/project/src/Box.php.virtual.0',
            visibility: 'private',
            nativeType: 'string',
            phpDocType: 'non-empty-string',
        ));
        $constants->add(new ClassConstantDeclarationSnapshot(
            ownerFqcn: 'App\\Box',
            name: 'LIMIT',
            fullFilePath: '/project/src/Box.php',
            virtualFilePath: '/project/src/Box.php.virtual.0',
            nativeType: 'int',
            scalarValue: 10,
        ));

        self::assertCount(1, $properties);
        self::assertSame('non-empty-string', $properties->get('App\\Box', 'value')?->phpDocType);
        self::assertSame(10, $constants->get('App\\Box', 'LIMIT')?->scalarValue);
    }

    /**
     * Ensures collections replace snapshots with the same stable key.
     *
     * @return void
     */
    public function testCollectionsReplaceSnapshotsWithTheSameKey(): void
    {
        $templates = new TemplateDeclarationSnapshotCollection();

        $templates->add(new TemplateDeclarationSnapshot('App\\Box', 'T', 'object'));
        $templates->add(new TemplateDeclarationSnapshot('App\\Box', 'T', 'App\\Contract'));

        self::assertCount(1, $templates);
        self::assertSame('App\\Contract', $templates->get('App\\Box', 'T')?->boundType);
    }
}
