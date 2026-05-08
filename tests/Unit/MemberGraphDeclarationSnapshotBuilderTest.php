<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Unit;

use PhpNoobs\MemberGraph\Application\Cache\Snapshot\Declaration\MemberGraphDeclarationSnapshotBuilder;
use PhpNoobs\MemberGraph\Domain\Owner\OwnerKind;
use PhpNoobs\PhpSource\Parser\UserLandParser;
use PhpNoobs\PhpSource\VirtualPhpSourceFile;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Covers declaration snapshot building from loaded virtual files.
 */
final class MemberGraphDeclarationSnapshotBuilderTest extends TestCase
{
    /**
     * Ensures declaration snapshots are extracted from class-like and function declarations.
     *
     * @return void
     */
    public function testItBuildsDeclarationSnapshotsFromVirtualFiles(): void
    {
        $code = <<<'PHP'
<?php

namespace App;

/**
 * @template T of object
 */
final class Box extends BaseBox implements BoxContract
{
    use Timestamped;

    /**
     * @var non-empty-string
     */
    private string $name;

    public const LIMIT = 10;

    public function __construct(private int $id)
    {
    }

    /**
     * @template TValue of object
     * @param positive-int $id
     * @return TValue
     */
    protected function get(int $id): object
    {
    }
}

/**
 * @param string $name
 * @return Box
 */
function make_box(string $name): Box
{
}
PHP;
        $virtualFile = new VirtualPhpSourceFile(
            fullFilePath: '/project/src/Box.php',
            virtualFilePath: '/project/src/Box.php.virtual.0',
            nodes: new UserLandParser()->parseCode($code, '/project/src/Box.php.virtual.0'),
        );

        $snapshot = new MemberGraphDeclarationSnapshotBuilder()->build(
            new VirtualPhpSourceFileCollection()->add($virtualFile),
        );

        $owner = $snapshot->owners->get('App\\Box');
        $ownerTemplate = $snapshot->templates->get('App\\Box', 'T');
        $method = $snapshot->methods->get('App\\Box', 'get');
        $methodTemplate = $snapshot->templates->get('App\\Box::get', 'TValue');
        $methodParameter = $snapshot->parameters->get('App\\Box::get', 'id');
        $nameProperty = $snapshot->properties->get('App\\Box', 'name');
        $idProperty = $snapshot->properties->get('App\\Box', 'id');
        $limitConstant = $snapshot->classConstants->get('App\\Box', 'LIMIT');
        $function = $snapshot->functions->get('App\\make_box');
        $functionParameter = $snapshot->parameters->get('App\\make_box', 'name');

        self::assertNotNull($owner);
        self::assertNotNull($ownerTemplate);
        self::assertNotNull($method);
        self::assertNotNull($methodTemplate);
        self::assertNotNull($methodParameter);
        self::assertNotNull($nameProperty);
        self::assertNotNull($idProperty);
        self::assertNotNull($limitConstant);
        self::assertNotNull($function);
        self::assertNotNull($functionParameter);

        self::assertSame(OwnerKind::CLASS_, $owner->kind);
        self::assertSame('App\\BaseBox', $owner->parentFqcn);
        self::assertSame(['App\\Timestamped'], $owner->traits);
        self::assertSame(['App\\BoxContract'], $owner->interfaces);
        self::assertSame('object', $ownerTemplate->boundType);

        self::assertSame('protected', $method->visibility);
        self::assertSame('object', $method->nativeReturnType);
        self::assertSame('TValue', $method->phpDocReturnType);
        self::assertSame('object', $methodTemplate->boundType);
        self::assertSame('int', $methodParameter->nativeType);
        self::assertSame('positive-int', $methodParameter->phpDocType);

        self::assertSame('string', $nameProperty->nativeType);
        self::assertSame('non-empty-string', $nameProperty->phpDocType);
        self::assertSame('int', $idProperty->nativeType);
        self::assertTrue($idProperty->isPromoted);
        self::assertSame(10, $limitConstant->scalarValue);

        self::assertSame('App\\Box', $function->nativeReturnType);
        self::assertSame('Box', $function->phpDocReturnType);
        self::assertSame('string', $functionParameter->nativeType);
        self::assertSame('string', $functionParameter->phpDocType);
    }
}
