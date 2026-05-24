<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphCallableClosureTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 136 keeps its member graph behavior stable.
     */
    public function testFunctionReturningCallableWithConcreteReturnTypeResolvesInvocationResult(): void
    {
        $sources = [
            'TestCase136.php' => <<<'PHP'
                <?php

                namespace TestCase136;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @return callable(): Mailer
                 */
                function makeCallable(): callable
                {
                    return static fn (): Mailer => new Mailer();
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $callable = makeCallable();
                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase136\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 137 keeps its member graph behavior stable.
     */
    public function testMethodReturningCallableWithConcreteReturnTypeResolvesInvocationResult(): void
    {
        $sources = [
            'TestCase137.php' => <<<'PHP'
                <?php

                namespace TestCase137;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class Factory
                {
                    /**
                     * @return callable(): Mailer
                     */
                    public function makeCallable(): callable
                    {
                        return static fn (): Mailer => new Mailer();
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $callable = $factory->makeCallable();
                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase137\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 140 keeps its member graph behavior stable.
     */
    public function testVariablePhpDocCallableWithConcreteReturnTypeResolvesInvocationResult(): void
    {
        $sources = [
            'TestCase140.php' => <<<'PHP'
                <?php

                namespace TestCase140;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        /** @var callable(): Mailer $callable */
                        $callable = static fn (): Mailer => new Mailer();

                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase140\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 141 keeps its member graph behavior stable.
     */
    public function testUnionContainingCallableReturnTypeResolvesInvocationResult(): void
    {
        $sources = [
            'TestCase141.php' => <<<'PHP'
                <?php

                namespace TestCase141;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @return (callable(): Mailer)|null
                 */
                function maybeMakeCallable(): ?callable
                {
                    return static fn (): Mailer => new Mailer();
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $callable = maybeMakeCallable();

                        if (null === $callable) {
                            return;
                        }

                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase141\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 142 keeps its member graph behavior stable.
     */
    public function testCallableReturnTypeKeepsPhpDocCallableReturn(): void
    {
        $sources = [
            'TestCase142.php' => <<<'PHP'
                <?php

                namespace TestCase142;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return callable(): Mailer
                     */
                    public function make(): callable {
                        return fn() => new Mailer();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $f = (new Factory())->make();
                        $result = $f();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase142\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 150 keeps its member graph behavior stable.
     */
    public function testCallablePhpDocRefinesNativeCallableReturnType(): void
    {
        $sources = [
            'TestCase150.php' => <<<'PHP'
                <?php

                namespace TestCase150;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return callable(): Mailer
                     */
                    public function make(): callable {
                        return static fn (): Mailer => new Mailer();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $callable = (new Factory())->make();
                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase150\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 151 keeps its member graph behavior stable.
     */
    public function testShapeContainingCallableResolvesInvocationReturnType(): void
    {
        $sources = [
            'TestCase151.php' => <<<'PHP'
                <?php

                namespace TestCase151;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return array{factory: callable(): Mailer}
                     */
                    public function make(): array {
                        return ['factory' => static fn (): Mailer => new Mailer()];
                    }
                }

                class TestClass {
                    public function run(): void {
                        $items = (new Factory())->make();
                        $result = $items['factory']();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase151\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 163 keeps its member graph behavior stable.
     */
    public function testUntypedClosureWithMultipleReturnsInfersUnionReturnTypes(): void
    {
        $sources = [
            'TestCase163.php' => <<<'PHP'
                <?php

                namespace TestCase163;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(bool $flag): void {
                        $result = consumeFactory(static function () use ($flag) {
                            if ($flag) {
                                return new Mailer();
                            }

                            return new Notifier();
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase163\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase163\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 166 keeps its member graph behavior stable.
     */
    public function testCallableListShapeUnionFieldReturnResolvesAllOwners(): void
    {
        $sources = [
            'TestCase166.php' => <<<'PHP'
                <?php

                namespace TestCase166;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @param callable(): list<array{service: Mailer|Notifier}> $factory
                 * @return list<array{service: Mailer|Notifier}>
                 */
                function consumeUnionListFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(bool $flag): void {
                        $result = consumeUnionListFactory(static function () use ($flag) {
                            if ($flag) {
                                return [['service' => new Mailer()]];
                            }

                            return [['service' => new Notifier()]];
                        });
                        $result[0]['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase166\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase166\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 167 keeps its member graph behavior stable.
     */
    public function testUntypedClosureCapturedPhpDocVariableInfersReturnType(): void
    {
        $sources = [
            'TestCase167.php' => <<<'PHP'
                <?php

                namespace TestCase167;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        /** @var Mailer $service */
                        $service = new Mailer();

                        $result = consumeFactory(static function () use ($service) {
                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase167\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 168 keeps its member graph behavior stable.
     */
    public function testUntypedClosureLocalAssignmentInfersReturnType(): void
    {
        $sources = [
            'TestCase168.php' => <<<'PHP'
                <?php

                namespace TestCase168;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static function () {
                            $service = new Mailer();

                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase168\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 169 keeps its member graph behavior stable.
     */
    public function testUntypedClosureLocalPhpDocVariableInfersReturnType(): void
    {
        $sources = [
            'TestCase169.php' => <<<'PHP'
                <?php

                namespace TestCase169;

                class Mailer {
                    public function send(): void {}
                }

                function makeMixed(): mixed {
                    return new Mailer();
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static function () {
                            /** @var Mailer $service */
                            $service = makeMixed();

                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase169\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 171 keeps its member graph behavior stable.
     */
    public function testCallableParameterAndReturnSubstituteFromValueParameter(): void
    {
        $sources = [
            'TestCase171.php' => <<<'PHP'
                <?php

                namespace TestCase171;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param T $service
                 * @param callable(T): T $factory
                 * @return T
                 */
                function applyFactory($service, callable $factory) {
                    return $factory($service);
                }

                class TestClass {
                    public function run(): void {
                        $service = new Mailer();
                        $result = applyFactory($service, static function (Mailer $value) {
                            return $value;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase171\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 172 keeps its member graph behavior stable.
     */
    public function testUntypedClosureLocalReassignmentResetsReturnType(): void
    {
        $sources = [
            'TestCase172.php' => <<<'PHP'
                <?php

                namespace TestCase172;

                class Mailer {
                    public function send(): void {}
                }

                function getMixed(): mixed {
                    return null;
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static function () {
                            $service = new Mailer();
                            $service = getMixed();

                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase172\\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertFalse($foundMailer);
    }

    /**
     * Ensures legacy fixture 173 keeps its member graph behavior stable.
     */
    public function testUntypedClosureLocalKnownReassignmentOverridesPhpDocType(): void
    {
        $sources = [
            'TestCase173.php' => <<<'PHP'
                <?php

                namespace TestCase173;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                function getMixed(): mixed {
                    return new Mailer();
                }

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static function () {
                            /** @var Mailer $service */
                            $service = getMixed();
                            $service = new Notifier();

                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase173\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 174 keeps its member graph behavior stable.
     */
    public function testNestedClosureReturnKeepsCallableInvocationReturnType(): void
    {
        $sources = [
            'TestCase174.php' => <<<'PHP'
                <?php

                namespace TestCase174;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @return callable(): Mailer
                 */
                function makeFactory(): callable {
                    return static function () {
                        return new Mailer();
                    };
                }

                class TestClass {
                    public function run(): void {
                        $factory = makeFactory();
                        $factory()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase174\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 176 keeps its member graph behavior stable.
     */
    public function testImportedAliasClosureReturnTypeResolvesOwner(): void
    {
        $sources = [
            'TestCase176.php' => <<<'PHP'
                <?php

                namespace TestCase176\Domain;

                class Mailer {
                    public function send(): void {}
                }

                namespace TestCase176\App;

                use TestCase176\Domain\Mailer as MailerAlias;

                /**
                 * @template T
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static fn (): MailerAlias => new MailerAlias());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase176\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 177 keeps its member graph behavior stable.
     */
    public function testUnionCallableReturnResolvesAllCallableBranches(): void
    {
        $sources = [
            'TestCase177.php' => <<<'PHP'
                <?php

                namespace TestCase177;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @return (callable(): Mailer)|(callable(): Notifier)
                 */
                function makeFactory(bool $flag): callable {
                    if ($flag) {
                        return static fn () => new Mailer();
                    }

                    return static fn () => new Notifier();
                }

                class TestClass {
                    public function run(bool $flag): void {
                        $factory = makeFactory($flag);
                        $result = $factory();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase177\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase177\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 178 keeps its member graph behavior stable.
     */
    public function testClosureParameterTypeAllowsInnerMethodUsage(): void
    {
        $sources = [
            'TestCase178.php' => <<<'PHP'
                <?php

                namespace TestCase178;

                class Mailer {
                    public function send(): void {}
                }

                function consume(callable $handler): void {
                    $handler(new Mailer());
                }

                class TestClass {
                    public function run(): void {
                        consume(static function (Mailer $service): void {
                            $service->send();
                        });
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase178\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 180 keeps its member graph behavior stable.
     */
    public function testCallableReturnSelfNormalizesToDeclaringOwner(): void
    {
        $sources = [
            'TestCase180.php' => <<<'PHP'
                <?php

                namespace TestCase180;

                class Factory {
                    public function send(): void {}

                    /**
                     * @return callable(): self
                     */
                    public function make(): callable {
                        return fn () => $this;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $factory = new Factory();
                        $callable = $factory->make();
                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase180\\Factory', 'send');
    }

    /**
     * Ensures legacy fixture 181 keeps its member graph behavior stable.
     */
    public function testClosureReturnsThisPropertyType(): void
    {
        $sources = [
            'TestCase181.php' => <<<'PHP'
                <?php

                namespace TestCase181;

                class Mailer {
                    public function send(): void {}
                }

                class TestClass {
                    private Mailer $mailer;

                    public function __construct(Mailer $mailer) {
                        $this->mailer = $mailer;
                    }

                    public function run(): void {
                        $factory = function () {
                            return $this->mailer;
                        };

                        $result = $factory();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase181\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 182 keeps its member graph behavior stable.
     */
    public function testArrowFunctionReturnsThisPropertyType(): void
    {
        $sources = [
            'TestCase182.php' => <<<'PHP'
                <?php

                namespace TestCase182;

                class Mailer {
                    public function send(): void {}
                }

                class TestClass {
                    private Mailer $mailer;

                    public function __construct(Mailer $mailer) {
                        $this->mailer = $mailer;
                    }

                    public function run(): void {
                        $factory = fn () => $this->mailer;
                        $result = $factory();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase182\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 183 keeps its member graph behavior stable.
     */
    public function testCallableReturningShapeAllowsInvocationThenFieldAccess(): void
    {
        $sources = [
            'TestCase183.php' => <<<'PHP'
                <?php

                namespace TestCase183;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @return callable(): array{service: Mailer}
                 */
                function makeShapeFactory(): callable {
                    return static fn () => ['service' => new Mailer()];
                }

                class TestClass {
                    public function run(): void {
                        $factory = makeShapeFactory();
                        $result = $factory();
                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase183\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 185 keeps its member graph behavior stable.
     */
    public function testCallableVariableUnionParameterInfersAllReturnOwners(): void
    {
        $sources = [
            'TestCase185.php' => <<<'PHP'
                <?php

                namespace TestCase185;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(T): T $handler
                 * @return T
                 */
                function applyHandler($handler) {
                    return $handler(new Mailer());
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(Mailer|Notifier): Mailer|Notifier $handler */
                        $handler = static function ($service) {
                            return $service;
                        };

                        $result = applyHandler($handler);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase185\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase185\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 225 keeps its member graph behavior stable.
     */
    public function testNullsafeCallableReturnTypeFeedsInvocationResult(): void
    {
        $sources = [
            'TestCase225.php' => <<<'PHP'
                <?php

                namespace TestCase225;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return callable(): Mailer
                     */
                    public function makeFactory(): callable {
                        return static fn (): Mailer => new Mailer();
                    }
                }

                class TestClass {
                    public function run(?Factory $factory): void {
                        $callable = $factory?->makeFactory();

                        if (null === $callable) {
                            return;
                        }

                        $result = $callable();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase225\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 237 keeps its member graph behavior stable.
     */
    public function testStaticPropertyCallablePhpDocFeedsInvocationResult(): void
    {
        $sources = [
            'TestCase237.php' => <<<'PHP'
                <?php

                namespace TestCase237;

                class Mailer {
                    public function send(): void {}
                }

                class Registry {
                    /**
                     * @var callable(): Mailer
                     */
                    public static $factory;
                }

                class TestClass {
                    public function run(): void {
                        Registry::$factory = static fn (): Mailer => new Mailer();
                        $result = (Registry::$factory)();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase237\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 261 keeps its member graph behavior stable.
     */
    public function testPromotedPropertyCallablePhpDocFeedsInvocationResult(): void
    {
        $sources = [
            'TestCase261.php' => <<<'PHP'
                <?php

                namespace TestCase261;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @param callable(): Mailer $factory
                     */
                    public function __construct(private $factory) {}

                    public function run(): void {
                        $result = ($this->factory)();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase261\\Mailer', 'send');
    }
}
