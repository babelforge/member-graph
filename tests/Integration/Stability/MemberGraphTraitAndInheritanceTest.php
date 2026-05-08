<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssue;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;
use PhpNoobs\MemberGraph\Domain\Graph\MemberOriginType;
use PhpNoobs\MemberGraph\Domain\Graph\MemberType;
use PhpNoobs\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use PhpNoobs\MemberGraph\Domain\Type\TypeIndexContext;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Parser\PhpDocParserFactory;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTypeNodeResolver;
use PhpNoobs\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Modifiers;
use PHPStan\PhpDocParser\Parser\TokenIterator;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphTraitAndInheritanceTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 1 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitProjectionDoesNotLoop(): void
    {
        $sources = [
            'TestCase.php' => <<<'PHP'
<?php

namespace TestCase;

trait T1 {
    public function a() {
        return $this->b();
    }
}

trait T2 {
    public function b() {
        return $this->a();
    }
}

class A {
    use T1, T2;
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase\A');

        $this->assertNotEmpty($available);
    }

    /**
     * Ensures legacy fixture 3 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testNoDuplicateTraitUsages(): void
    {
        $sources = [
            'TestCase3.php' => <<<'PHP'
<?php

namespace TestCase3;

trait T {
    public function call() {
        $this->foo();
    }
}

class A {
    use T;

    public function foo() {}
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $usages = $memberDependencyGraph->usages->all();

        $count = 0;

        foreach ($usages as $group) {
            foreach ($group as $usage) {
                if ('TestCase3\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $count++;
                }
            }
        }

        $this->assertSame(1, $count);

    }

    /**
     * Ensures legacy fixture 4 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAndInheritanceProjection(): void
    {
        $sources = [
            'TestCase4.php' => <<<'PHP'
<?php

namespace TestCase4;

trait T {
    public function t() {}
}

class B {
    public function b() {}
}

class A extends B {
    use T;
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase4\A');

        $names = array_map(static fn ($m) => $m->member->name, $available);

        $this->assertContains('t', $names);
        $this->assertContains('b', $names);

    }

    /**
     * Ensures legacy fixture 7 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testSamePropertiesInTrait(): void
    {
        $sources = [
            'TestCase7.php' => <<<'PHP'
<?php

namespace TestCase7;

trait A { public string $x = 'a'; }
trait B { public string $x = 'a'; }

class C {
    use A, B;
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase7\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::PROPERTY === $member->member->type
                && 'x' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found, 'Property C::$x should exist');

        $declaredIns = array_keys($found->declaredIns);

        sort($declaredIns);

        $this->assertSame(
            ['TestCase7\A', 'TestCase7\B'],
            $declaredIns,
            'C::$x should originate from both traits A and B'
        );

    }

    /**
     * Ensures legacy fixture 49 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasCreatesNewAvailableMember(): void
    {
        $sources = [
            'TestCase49.php' => <<<'PHP'
<?php

namespace TestCase49;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as bar;
    }

    public function run(): void
    {
        $this->bar();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase49\C' === $usage->target->owner
                    && 'bar' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);


    }

    /**
     * Ensures legacy fixture 50 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitInsteadOfKeepsOnlyPreferredTraitMethod(): void
    {
        $sources = [
            'TestCase50.php' => <<<'PHP'
<?php

namespace TestCase50;

trait A
{
    public function foo(): void {}
}

trait B
{
    public function foo(): void {}
}

class C
{
    use A, B {
        A::foo insteadof B;
    }

    public function run(): void
    {
        $this->foo();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase50\C');

        $count = 0;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $count++;
            }
        }

        $this->assertSame(1, $count);


    }

    /**
     * Ensures legacy fixture 51 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitInsteadOfAndAliasCanBeCombined(): void
    {
        $sources = [
            'TestCase51.php' => <<<'PHP'
<?php

namespace TestCase51;

trait A
{
    public function foo(): void {}
}

trait B
{
    public function foo(): void {}
}

class C
{
    use A, B {
        A::foo insteadof B;
        A::foo as bar;
    }

    public function run(): void
    {
        $this->foo();
        $this->bar();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase51\C');

        $foundFoo = false;
        $foundBar = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $foundFoo = true;
            }

            if (
                MemberType::METHOD === $member->member->type
                && 'bar' === $member->member->name
            ) {
                $foundBar = true;
            }
        }

        $this->assertTrue($foundFoo);
        $this->assertTrue($foundBar);


    }

    /**
     * Ensures legacy fixture 52 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasVisibilityWithoutNewNameAppliesToOriginalMethod(): void
    {
        $sources = [
            'TestCase52.php' => <<<'PHP'
<?php

namespace TestCase52;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as private;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase52\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PRIVATE, $found->visibility);


    }

    /**
     * Ensures legacy fixture 53 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasVisibilityWithNewNameAppliesToAliasedMethod(): void
    {
        $sources = [
            'TestCase53.php' => <<<'PHP'
<?php

namespace TestCase53;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as protected bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase53\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'bar' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PROTECTED, $found->visibility);


    }

    /**
     * Ensures legacy fixture 54 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasKeepsOriginalMethodAndAddsAliasedMethodWithVisibility(): void
    {
        $sources = [
            'TestCase54.php' => <<<'PHP'
<?php

namespace TestCase54;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as private bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase54\C');

        $foundFoo = false;
        $foundBar = false;
        $barVisibility = null;

        foreach ($available as $member) {
            if (MemberType::METHOD !== $member->member->type) {
                continue;
            }

            if ('foo' === $member->member->name) {
                $foundFoo = true;
            }

            if ('bar' === $member->member->name) {
                $foundBar = true;
                $barVisibility = $member->visibility;
            }
        }

        $this->assertTrue($foundFoo);
        $this->assertTrue($foundBar);
        $this->assertSame(Modifiers::PRIVATE, $barVisibility);


    }

    /**
     * Ensures legacy fixture 55 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasVisibilityWithoutNewNameCanBeProtected(): void
    {
        $sources = [
            'TestCase55.php' => <<<'PHP'
<?php

namespace TestCase55;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as protected;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase55\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PROTECTED, $found->visibility);


    }

    /**
     * Ensures legacy fixture 56 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasVisibilityWithoutNewNameCanBePublic(): void
    {
        $sources = [
            'TestCase56.php' => <<<'PHP'
<?php

namespace TestCase56;

trait T
{
    protected function foo(): void {}
}

class C
{
    use T {
        foo as public;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase56\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'foo' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PUBLIC, $found->visibility);


    }

    /**
     * Ensures legacy fixture 57 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasWithNewNameCanBePrivate(): void
    {
        $sources = [
            'TestCase57.php' => <<<'PHP'
<?php

namespace TestCase57;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as private bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase57\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'bar' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PRIVATE, $found->visibility);


    }

    /**
     * Ensures legacy fixture 58 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasWithNewNameCanBeProtected(): void
    {
        $sources = [
            'TestCase58.php' => <<<'PHP'
<?php

namespace TestCase58;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as protected bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase58\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'bar' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PROTECTED, $found->visibility);


    }

    /**
     * Ensures legacy fixture 59 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasWithNewNameCanBePublic(): void
    {
        $sources = [
            'TestCase59.php' => <<<'PHP'
<?php

namespace TestCase59;

trait T
{
    protected function foo(): void {}
}

class C
{
    use T {
        foo as public bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase59\C');

        $found = null;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'bar' === $member->member->name
            ) {
                $found = $member;
                break;
            }
        }

        $this->assertNotNull($found);
        $this->assertSame(Modifiers::PUBLIC, $found->visibility);


    }

    /**
     * Ensures legacy fixture 60 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasVisibilityKeepsOriginalAndAliasWhenNewNameExists(): void
    {
        $sources = [
            'TestCase60.php' => <<<'PHP'
<?php

namespace TestCase60;

trait T
{
    public function foo(): void {}
}

class C
{
    use T {
        foo as protected bar;
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase60\C');

        $foundFoo = false;
        $foundBar = false;
        $barVisibility = null;

        foreach ($available as $member) {
            if (MemberType::METHOD !== $member->member->type) {
                continue;
            }

            if ('foo' === $member->member->name) {
                $foundFoo = true;
            }

            if ('bar' === $member->member->name) {
                $foundBar = true;
                $barVisibility = $member->visibility;
            }
        }

        $this->assertTrue($foundFoo);
        $this->assertTrue($foundBar);
        $this->assertSame(Modifiers::PROTECTED, $barVisibility);


    }

    /**
     * Ensures legacy fixture 249 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitClassConstantFetchTargetsTraitOwner(): void
    {
        $sources = [
            'TestCase249.php' => <<<'PHP'
<?php

namespace TestCase249;

trait HasToken {
    public const TOKEN = 'token';
}

class Registry {
    use HasToken;
}

class TestClass {
    public function run(): string {
        return Registry::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase249\\HasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 250 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testNestedTraitClassConstantFetchTargetsNestedTraitOwner(): void
    {
        $sources = [
            'TestCase250.php' => <<<'PHP'
<?php

namespace TestCase250;

trait BaseHasToken {
    public const TOKEN = 'token';
}

trait HasToken {
    use BaseHasToken;
}

class Registry {
    use HasToken;
}

class TestClass {
    public function run(): string {
        return Registry::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase250\\BaseHasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 253 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testOwnClassConstantOverridesTraitClassConstantOwner(): void
    {
        $sources = [
            'TestCase253.php' => <<<'PHP'
<?php

namespace TestCase253;

trait HasToken {
    public const TOKEN = 'trait';
}

class Registry {
    use HasToken;

    public const TOKEN = 'class';
}

class TestClass {
    public function run(): string {
        return Registry::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase253\\Registry', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 271 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testEnumTraitConstantFetchTargetsTraitOwner(): void
    {
        $sources = [
            'TestCase271.php' => <<<'PHP'
<?php

namespace TestCase271;

trait HasToken {
    public const TOKEN = 'token';
}

enum Status {
    use HasToken;

    case Open;
}

class TestClass {
    public function run(): string {
        return Status::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase271\\HasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 274 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testClassTraitUseKeepsImplementedInterfaceConstantOwner(): void
    {
        $sources = [
            'TestCase274.php' => <<<'PHP'
<?php

namespace TestCase274;

interface HasToken {
    public const TOKEN = 'token';
}

trait HasHelper {
    public function helper(): void {}
}

class Service implements HasToken {
    use HasHelper;
}

class TestClass {
    public function run(): string {
        return Service::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase274\\HasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 275 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testEnumTraitUseKeepsImplementedInterfaceConstantOwner(): void
    {
        $sources = [
            'TestCase275.php' => <<<'PHP'
<?php

namespace TestCase275;

interface HasToken {
    public const TOKEN = 'token';
}

trait HasHelper {
    public function helper(): void {}
}

enum Status implements HasToken {
    use HasHelper;

    case Open;
}

class TestClass {
    public function run(): string {
        return Status::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase275\\HasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 276 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testClassTraitUseKeepsExtendedInterfaceConstantOwner(): void
    {
        $sources = [
            'TestCase276.php' => <<<'PHP'
<?php

namespace TestCase276;

interface BaseHasToken {
    public const TOKEN = 'token';
}

interface HasToken extends BaseHasToken {
}

trait HasHelper {
    public function helper(): void {}
}

class Service implements HasToken {
    use HasHelper;
}

class TestClass {
    public function run(): string {
        return Service::TOKEN;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertClassConstantUsageExists($memberDependencyGraph, 'TestCase276\\BaseHasToken', 'TOKEN');
    }

    /**
     * Ensures legacy fixture 294 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfacePolymorphismTargetsClassUsingTraitImplementation(): void
    {
        $sources = [
            'TestCase294.php' => <<<'PHP'
<?php

namespace TestCase294;

interface Contract {
    public function send(): void;
}

trait Sends {
    public function send(): void {}
}

class Service implements Contract {
    use Sends;
}

class TestClass {
    public function run(Contract $service): void {
        $service->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase294\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 295 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testExtendedInterfacePolymorphismTargetsClassUsingTraitImplementation(): void
    {
        $sources = [
            'TestCase295.php' => <<<'PHP'
<?php

namespace TestCase295;

interface RootContract {
    public function send(): void;
}

interface LeafContract extends RootContract {
}

trait Sends {
    public function send(): void {}
}

class Service implements LeafContract {
    use Sends;
}

class TestClass {
    public function run(RootContract $service): void {
        $service->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase295\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 296 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfacePolymorphismTargetsEnumUsingTraitImplementation(): void
    {
        $sources = [
            'TestCase296.php' => <<<'PHP'
<?php

namespace TestCase296;

interface Contract {
    public function send(): void;
}

trait Sends {
    public function send(): void {}
}

enum Status implements Contract {
    use Sends;

    case Open;
}

class TestClass {
    public function run(Contract $status): void {
        $status->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase296\\Status', 'send');
    }

    /**
     * Ensures legacy fixture 297 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfaceNamedArgumentUsageTargetsClassUsingTraitImplementation(): void
    {
        $sources = [
            'TestCase297.php' => <<<'PHP'
<?php

namespace TestCase297;

interface Contract {
    public function send(string $message): void;
}

trait Sends {
    public function send(string $message): void {}
}

class Service implements Contract {
    use Sends;
}

class TestClass {
    public function run(Contract $service): void {
        $service->send(message: 'hello');
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->parameterUsages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase297\\Service' === $usage->target->owner
                    && 'send' === $usage->target->functionLikeName
                    && 'message' === $usage->target->parameterName
                ) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 298 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfacePolymorphismTargetsChildClassInheritingTraitImplementation(): void
    {
        $sources = [
            'TestCase298.php' => <<<'PHP'
<?php

namespace TestCase298;

interface Contract {
    public function send(): void;
}

trait Sends {
    public function send(): void {}
}

class ParentService implements Contract {
    use Sends;
}

class Service extends ParentService {
}

class TestClass {
    public function run(Contract $service): void {
        $service->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase298\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 299 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfacePolymorphismTargetsClassUsingTraitAliasImplementation(): void
    {
        $sources = [
            'TestCase299.php' => <<<'PHP'
<?php

namespace TestCase299;

interface Contract {
    public function send(): void;
}

trait Notifies {
    public function notify(): void {}
}

class Service implements Contract {
    use Notifies {
        notify as send;
    }
}

class TestClass {
    public function run(Contract $service): void {
        $service->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase299\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 300 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testExtendedInterfacePolymorphismTargetsClassUsingTraitAliasImplementation(): void
    {
        $sources = [
            'TestCase300.php' => <<<'PHP'
<?php

namespace TestCase300;

interface RootContract {
    public function send(): void;
}

interface LeafContract extends RootContract {
}

trait Notifies {
    public function notify(): void {}
}

class Service implements LeafContract {
    use Notifies {
        notify as send;
    }
}

class TestClass {
    public function run(RootContract $service): void {
        $service->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase300\\Service', 'send');
    }

    /**
     * Ensures legacy fixture 301 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfacePolymorphismTargetsEnumUsingTraitAliasImplementation(): void
    {
        $sources = [
            'TestCase301.php' => <<<'PHP'
<?php

namespace TestCase301;

interface Contract {
    public function send(): void;
}

trait Notifies {
    public function notify(): void {}
}

enum Status implements Contract {
    use Notifies {
        notify as send;
    }

    case Open;
}

class TestClass {
    public function run(Contract $status): void {
        $status->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase301\\Status', 'send');
    }

    /**
     * Ensures legacy fixture 302 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInterfaceNamedArgumentUsageTargetsClassUsingTraitAliasImplementation(): void
    {
        $sources = [
            'TestCase302.php' => <<<'PHP'
<?php

namespace TestCase302;

interface Contract {
    public function send(string $message): void;
}

trait Notifies {
    public function notify(string $message): void {}
}

class Service implements Contract {
    use Notifies {
        notify as send;
    }
}

class TestClass {
    public function run(Contract $service): void {
        $service->send(message: 'hello');
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->parameterUsages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase302\\Service' === $usage->target->owner
                    && 'send' === $usage->target->functionLikeName
                    && 'message' === $usage->target->parameterName
                ) {
                    $found = true;
                    break 2;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 303 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTraitAliasImplementationIsAvailableOnClassOwner(): void
    {
        $sources = [
            'TestCase303.php' => <<<'PHP'
<?php

namespace TestCase303;

interface Contract {
    public function send(): void;
}

trait Notifies {
    public function notify(): void {}
}

class Service implements Contract {
    use Notifies {
        notify as send;
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase303\\Service');
        $found = false;

        foreach ($available as $member) {
            if (
                MemberType::METHOD === $member->member->type
                && 'send' === $member->member->name
            ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found);
    }
}