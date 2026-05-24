<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphExpressionTypeResolutionTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 8 keeps its member graph behavior stable.
     */
    public function testVariablePropagationResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase8.php' => <<<'PHP'
                <?php

                namespace TestCase8;

                class A {
                    public function foo(): void {}
                }

                class B {
                    public function run(): void
                    {
                        $a = new A();
                        $b = $a;
                        $b->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $usages = $memberDependencyGraph->usages->all();
        $found = false;

        foreach ($usages as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase8\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 9 keeps its member graph behavior stable.
     */
    public function testVariableResetRemovesResolvedType(): void
    {
        $sources = [
            'TestCase9.php' => <<<'PHP'
                <?php

                namespace TestCase9;

                class A {
                    public function foo(): void {}
                }

                class B {
                    public function run(mixed $value): void
                    {
                        $a = new A();
                        $a = $value;
                        $a->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $usages = $memberDependencyGraph->usages->all();
        $foundUnknown = false;

        foreach ($usages as $group) {
            foreach ($group as $usage) {
                if (
                    'unknown' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $foundUnknown = true;
                }
            }
        }

        $this->assertTrue($foundUnknown);
    }

    /**
     * Ensures legacy fixture 10 keeps its member graph behavior stable.
     */
    public function testMethodReturnTypeResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase10.php' => <<<'PHP'
                <?php

                namespace TestCase10;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function makeA(): A
                    {
                        return new A();
                    }

                    public function run(): void
                    {
                        $a = $this->makeA();
                        $a->foo();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase10\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 11 keeps its member graph behavior stable.
     */
    public function testTypedPropertyResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase11.php' => <<<'PHP'
                <?php

                namespace TestCase11;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public A $service;

                    public function run(): void
                    {
                        $this->service->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase11\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 12 keeps its member graph behavior stable.
     */
    public function testTypedPropertyAssignedToVariableResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase12.php' => <<<'PHP'
                <?php

                namespace TestCase12;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public A $service;

                    public function run(): void
                    {
                        $svc = $this->service;
                        $svc->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase12\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 13 keeps its member graph behavior stable.
     */
    public function testUntypedPropertyStaysUnknown(): void
    {
        $sources = [
            'TestCase13.php' => <<<'PHP'
                <?php

                namespace TestCase13;

                class B
                {
                    public $service;

                    public function run(): void
                    {
                        $this->service->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundUnknown = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'unknown' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $foundUnknown = true;
                }
            }
        }

        $this->assertTrue($foundUnknown);
    }

    /**
     * Ensures legacy fixture 17 keeps its member graph behavior stable.
     */
    public function testStaticFactoryResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase17.php' => <<<'PHP'
                <?php

                namespace TestCase17;

                class A
                {
                    public static function make(): A
                    {
                        return new A();
                    }

                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function run(): void
                    {
                        $a = A::make();
                        $a->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase17\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 18 keeps its member graph behavior stable.
     */
    public function testInheritedMethodReturnTypeResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase18.php' => <<<'PHP'
                <?php

                namespace TestCase18;

                class A
                {
                    public function makeA(): A
                    {
                        return new A();
                    }

                    public function foo(): void
                    {
                    }
                }

                class B extends A
                {
                }

                class C
                {
                    public function run(): void
                    {
                        $b = new B();
                        $a = $b->makeA();
                        $a->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase18\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 19 keeps its member graph behavior stable.
     */
    public function testInheritedPropertyTypeResolvesMethodCallOwner(): void
    {
        $sources = [
            'TestCase19.php' => <<<'PHP'
                <?php

                namespace TestCase19;

                class AService
                {
                    public function foo(): void
                    {
                    }
                }

                class A
                {
                    public AService $service;
                }

                class B extends A
                {
                }

                class C
                {
                    public function run(): void
                    {
                        $b = new B();
                        $b->service->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase19\AService' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 20 keeps its member graph behavior stable.
     */
    public function testInheritedClassResolvesParentMethodCallOwner(): void
    {
        $sources = [
            'TestCase20.php' => <<<'PHP'
                <?php

                namespace TestCase20;

                class A {
                    public function make(): A { return new A(); }
                    public function foo(): void {}
                }

                class B extends A {
                    public function run(): void {
                        $a = parent::make();
                        $a->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase20\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 48 keeps its member graph behavior stable.
     */
    public function testFunctionCallReturnTypeResolvesMethodCallTargets(): void
    {
        $sources = [
            'TestCase48.php' => <<<'PHP'
                <?php

                namespace TestCase48;

                class A
                {
                    public function send(): void
                    {
                    }
                }

                function makeService(): A
                {
                    return new A();
                }

                class Runner
                {
                    public function run(): void
                    {
                        $service = makeService();
                        $service->send();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase48\A' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 113 keeps its member graph behavior stable.
     */
    public function testGenericClassMethodReturnTypeResolvesFromAnnotatedVariable(): void
    {
        $sources = [
            'TestCase113.php' => <<<'PHP'
                <?php

                namespace TestCase113;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class Box
                {
                    /**
                     * @var T
                     */
                    private mixed $value;

                    /**
                     * @param T $value
                     */
                    public function __construct($value)
                    {
                        $this->value = $value;
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        /** @var Box<Mailer> $box */
                        $box = new Box(new Mailer());

                        $result = $box->get();

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase113\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 122 keeps its member graph behavior stable.
     */
    public function testGenericClassStaticFactoryInfersConcreteType(): void
    {
        $sources = [
            'TestCase122.php' => <<<'PHP'
                <?php

                namespace TestCase122;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class Box
                {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }

                    /**
                     * @template U
                     * @param U $value
                     * @return Box<U>
                     */
                    public static function make($value): Box
                    {
                        return new Box($value);
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = Box::make(new Mailer());
                        $result = $box->get();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase122\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 124 keeps its member graph behavior stable.
     */
    public function testGenericPropagationThroughProperty(): void
    {
        $sources = [
            'TestCase124.php' => <<<'PHP'
                <?php

                namespace TestCase124;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class Box
                {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }
                }

                class Service
                {
                    /**
                     * @var Box<Mailer>
                     */
                    private Box $box;

                    public function __construct()
                    {
                        $this->box = new Box(new Mailer());
                    }

                    public function getBox(): Box
                    {
                        return $this->box;
                    }
                }

                class Runner
                {
                    public function run(): void
                    {
                        $service = new Service();

                        $box = $service->getBox();
                        $value = $box->get();

                        $value->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase124\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 125 keeps its member graph behavior stable.
     */
    public function testChainedGenericMethodCallsResolve(): void
    {
        $sources = [
            'TestCase125.php' => <<<'PHP'
                <?php

                namespace TestCase125;

                class Mailer
                {
                    public function send(): void {}
                }

                /**
                 * @template T
                 */
                class Box
                {
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }
                }

                class Service
                {
                    /**
                     * @return Box<Mailer>
                     */
                    public function getBox(): Box
                    {
                        return new Box(new Mailer());
                    }
                }

                class Runner
                {
                    public function run(): void
                    {
                        $service = new Service();

                        $service->getBox()->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase125\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 127 keeps its member graph behavior stable.
     */
    public function testGenericClassStaticFactoryWithNewSelfInfersConcreteType(): void
    {
        $sources = [
            'TestCase127.php' => <<<'PHP'
                <?php

                namespace TestCase127;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class Box
                {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }

                    /**
                     * @template U
                     * @param U $value
                     * @return self<U>
                     */
                    public static function make($value): self
                    {
                        return new self($value);
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = Box::make(new Mailer());
                        $result = $box->get();

                        $result->send();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase127\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 128 keeps its member graph behavior stable.
     */
    public function testGenericClassStaticFactoryWithNewStaticInfersConcreteType(): void
    {
        $sources = [
            'TestCase128.php' => <<<'PHP'
                <?php

                namespace TestCase128;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class Box
                {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->value;
                    }

                    /**
                     * @template U
                     * @param U $value
                     * @return static<U>
                     */
                    public static function make($value): static
                    {
                        return new static($value);
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = Box::make(new Mailer());
                        $result = $box->get();

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase128\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 220 keeps its member graph behavior stable.
     */
    public function testNullsafeMethodCallResolvesReceiverOwner(): void
    {
        $sources = [
            'TestCase220.php' => <<<'PHP'
                <?php

                namespace TestCase220;

                class Mailer {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(?Mailer $mailer): void {
                        $mailer?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase220\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 221 keeps its member graph behavior stable.
     */
    public function testNullsafeMethodCallReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase221.php' => <<<'PHP'
                <?php

                namespace TestCase221;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    public function makeMailer(): Mailer {
                        return new Mailer();
                    }
                }

                class TestClass {
                    public function run(?Factory $factory): void {
                        $factory?->makeMailer()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase221\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 222 keeps its member graph behavior stable.
     */
    public function testNestedNullsafeMethodCallReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase222.php' => <<<'PHP'
                <?php

                namespace TestCase222;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    public function makeMailer(): ?Mailer {
                        return new Mailer();
                    }
                }

                class TestClass {
                    public function run(?Factory $factory): void {
                        $factory?->makeMailer()?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase222\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 223 keeps its member graph behavior stable.
     */
    public function testNullsafeGenericMethodCallReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase223.php' => <<<'PHP'
                <?php

                namespace TestCase223;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 */
                class Box {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get(): mixed {
                        return $this->value;
                    }
                }

                class Factory {
                    /**
                     * @template T
                     * @param T $value
                     * @return Box<T>
                     */
                    public function box(mixed $value): Box {
                        return new Box($value);
                    }
                }

                class TestClass {
                    public function run(?Factory $factory): void {
                        $factory?->box(new Mailer())?->get()?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase223\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 226 keeps its member graph behavior stable.
     */
    public function testNullsafePropertyFetchFeedsMethodCall(): void
    {
        $sources = [
            'TestCase226.php' => <<<'PHP'
                <?php

                namespace TestCase226;

                class Mailer {
                    public function send(): void {}
                }

                class Holder {
                    public ?Mailer $mailer;

                    public function __construct() {
                        $this->mailer = new Mailer();
                    }
                }

                class TestClass {
                    public function run(?Holder $holder): void {
                        $holder?->mailer?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase226\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 227 keeps its member graph behavior stable.
     */
    public function testNullsafeInheritedMethodReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase227.php' => <<<'PHP'
                <?php

                namespace TestCase227;

                class Mailer {
                    public function send(): void {}
                }

                class ParentFactory {
                    public function makeMailer(): ?Mailer {
                        return new Mailer();
                    }
                }

                class Factory extends ParentFactory {
                }

                class TestClass {
                    public function run(?Factory $factory): void {
                        $factory?->makeMailer()?->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase227\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 238 keeps its member graph behavior stable.
     */
    public function testSelfStaticGenericMethodFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase238.php' => <<<'PHP'
                <?php

                namespace TestCase238;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public static function identity(mixed $value): mixed {
                        return $value;
                    }

                    public function run(): void {
                        self::identity(new Mailer())->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase238\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 239 keeps its member graph behavior stable.
     */
    public function testStaticStaticGenericMethodFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase239.php' => <<<'PHP'
                <?php

                namespace TestCase239;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public static function identity(mixed $value): mixed {
                        return $value;
                    }

                    public function run(): void {
                        static::identity(new Mailer())->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase239\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 240 keeps its member graph behavior stable.
     */
    public function testParentStaticMethodReturnTypeFeedsChainedMethodCall(): void
    {
        $sources = [
            'TestCase240.php' => <<<'PHP'
                <?php

                namespace TestCase240;

                class Mailer {
                    public function send(): void {}
                }

                class ParentFactory {
                    public static function makeMailer(): Mailer {
                        return new Mailer();
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        parent::makeMailer()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase240\\Mailer', 'send');
    }
}
