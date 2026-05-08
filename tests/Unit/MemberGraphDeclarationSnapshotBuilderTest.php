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

        self::assertSame(OwnerKind::CLASS_, $snapshot->owners->get('App\\Box')->kind);
        self::assertSame('App\\BaseBox', $snapshot->owners->get('App\\Box')->parentFqcn);
        self::assertSame(['App\\Timestamped'], $snapshot->owners->get('App\\Box')->traits);
        self::assertSame(['App\\BoxContract'], $snapshot->owners->get('App\\Box')->interfaces);
        self::assertSame('object', $snapshot->templates->get('App\\Box', 'T')->boundType);

        self::assertSame('protected', $snapshot->methods->get('App\\Box', 'get')->visibility);
        self::assertSame('object', $snapshot->methods->get('App\\Box', 'get')->nativeReturnType);
        self::assertSame('TValue', $snapshot->methods->get('App\\Box', 'get')->phpDocReturnType);
        self::assertSame('object', $snapshot->templates->get('App\\Box::get', 'TValue')->boundType);
        self::assertSame('int', $snapshot->parameters->get('App\\Box::get', 'id')->nativeType);
        self::assertSame('positive-int', $snapshot->parameters->get('App\\Box::get', 'id')->phpDocType);

        self::assertSame('string', $snapshot->properties->get('App\\Box', 'name')->nativeType);
        self::assertSame('non-empty-string', $snapshot->properties->get('App\\Box', 'name')->phpDocType);
        self::assertSame('int', $snapshot->properties->get('App\\Box', 'id')->nativeType);
        self::assertTrue($snapshot->properties->get('App\\Box', 'id')->isPromoted);
        self::assertSame(10, $snapshot->classConstants->get('App\\Box', 'LIMIT')->scalarValue);

        self::assertSame('App\\Box', $snapshot->functions->get('App\\make_box')->nativeReturnType);
        self::assertSame('Box', $snapshot->functions->get('App\\make_box')->phpDocReturnType);
        self::assertSame('string', $snapshot->parameters->get('App\\make_box', 'name')->nativeType);
        self::assertSame('string', $snapshot->parameters->get('App\\make_box', 'name')->phpDocType);
    }
}
