<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphTemplateBoundTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 110 keeps its member graph behavior stable.
     */
    public function testTemplateBoundDoesNotBreakConcreteResolution(): void
    {
        $sources = [
            'TestCase110.php' => <<<'PHP'
                <?php

                namespace TestCase110;

                interface ServiceInterface
                {
                    public function send(): void;
                }

                class Mailer implements ServiceInterface
                {
                    public function send(): void {}
                }

                /**
                 * @template T of ServiceInterface
                 * @param T $value
                 * @return T
                 */
                function identity($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $mailer = new Mailer();

                        $result = identity($mailer);

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
                    'TestCase110\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 111 keeps its member graph behavior stable.
     */
    public function testTemplateBoundMethodCallDoesNotBreakConcreteResolution(): void
    {
        $sources = [
            'TestCase111.php' => <<<'PHP'
                <?php

                namespace TestCase111;

                interface ServiceInterface
                {
                    public function send(): void;
                }

                class Mailer implements ServiceInterface
                {
                    public function send(): void {}
                }

                class Service
                {
                    /**
                     * @template T of ServiceInterface
                     * @param T $value
                     * @return T
                     */
                    public function identity($value)
                    {
                        return $value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();
                        $mailer = new Mailer();

                        $result = $service->identity($mailer);

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
                    'TestCase111\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 112 keeps its member graph behavior stable.
     */
    public function testTemplateBoundWithShapeResolvesReturnType(): void
    {
        $sources = [
            'TestCase112.php' => <<<'PHP'
                <?php

                namespace TestCase112;

                interface ServiceInterface
                {
                    public function send(): void;
                }

                class Mailer implements ServiceInterface
                {
                    public function send(): void {}
                }

                /**
                 * @template T of ServiceInterface
                 * @param array{service: T} $config
                 * @return T
                 */
                function getService(array $config) {
                    return $config['service'];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array{service: Mailer} $config */
                        $config = [
                            'service' => new Mailer(),
                        ];

                        $result = getService($config);

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
                    'TestCase112\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 192 keeps its member graph behavior stable.
     */
    public function testTemplateBoundFunctionIdentityResolvesConcreteType(): void
    {
        $sources = [
            'TestCase192.php' => <<<'PHP'
                <?php

                namespace TestCase192;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return T
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        $result = identity(new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase192\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 193 keeps its member graph behavior stable.
     */
    public function testTemplateBoundMethodIdentityResolvesConcreteType(): void
    {
        $sources = [
            'TestCase193.php' => <<<'PHP'
                <?php

                namespace TestCase193;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                class Identity {
                    /**
                     * @template T of HasSend
                     * @param T $service
                     * @return T
                     */
                    public function identity($service) { return $service; }
                }

                class TestClass {
                    public function run(): void {
                        $identity = new Identity();
                        $result = $identity->identity(new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase193\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 194 keeps its member graph behavior stable.
     */
    public function testTemplateBoundStaticMethodIdentityResolvesConcreteType(): void
    {
        $sources = [
            'TestCase194.php' => <<<'PHP'
                <?php

                namespace TestCase194;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                class Identity {
                    /**
                     * @template T of HasSend
                     * @param T $service
                     * @return T
                     */
                    public static function identity($service) { return $service; }
                }

                class TestClass {
                    public function run(): void {
                        $result = Identity::identity(new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase194\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 195 keeps its member graph behavior stable.
     */
    public function testTemplateBoundGenericClassMethodReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase195.php' => <<<'PHP'
                <?php

                namespace TestCase195;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 */
                class Box {
                    /** @param T $value */
                    public function __construct(private mixed $value) {}

                    /** @return T */
                    public function get() { return $this->value; }
                }

                class TestClass {
                    public function run(): void {
                        $box = new Box(new Mailer());
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase195\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 196 keeps its member graph behavior stable.
     */
    public function testTemplateBoundShapeParameterResolvesConcreteFieldType(): void
    {
        $sources = [
            'TestCase196.php' => <<<'PHP'
                <?php

                namespace TestCase196;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param array{service: T} $payload
                 * @return T
                 */
                function serviceFromShape(array $payload) { return $payload['service']; }

                class TestClass {
                    public function run(): void {
                        $result = serviceFromShape(['service' => new Mailer()]);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase196\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 197 keeps its member graph behavior stable.
     */
    public function testTemplateBoundListShapeParameterResolvesConcreteFieldType(): void
    {
        $sources = [
            'TestCase197.php' => <<<'PHP'
                <?php

                namespace TestCase197;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param list<array{service: T}> $payloads
                 * @return T
                 */
                function firstService(array $payloads) { return $payloads[0]['service']; }

                class TestClass {
                    public function run(): void {
                        $result = firstService([['service' => new Mailer()]]);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase197\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 198 keeps its member graph behavior stable.
     */
    public function testTemplateBoundCallableReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase198.php' => <<<'PHP'
                <?php

                namespace TestCase198;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) { return $factory(); }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static fn () => new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase198\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 201 keeps its member graph behavior stable.
     */
    public function testTemplateBoundNestedGenericReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase201.php' => <<<'PHP'
                <?php

                namespace TestCase201;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /** @template T of HasSend */
                class Box {
                    /** @param T $value */
                    public function __construct(private mixed $value) {}

                    /** @return T */
                    public function get() { return $this->value; }
                }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return Box<T>
                 */
                function box($service): Box { return new Box($service); }

                class TestClass {
                    public function run(): void {
                        $box = box(new Mailer());
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase201\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 202 keeps its member graph behavior stable.
     */
    public function testTemplateBoundUnionArgumentResolvesAllConcreteTypes(): void
    {
        $sources = [
            'TestCase202.php' => <<<'PHP'
                <?php

                namespace TestCase202;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }
                class Notifier implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return T
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        /** @var Mailer|Notifier $service */
                        $service = loadMixed();
                        $result = identity($service);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase202\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase202\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 203 keeps its member graph behavior stable.
     */
    public function testTemplateBoundIntersectionReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase203.php' => <<<'PHP'
                <?php

                namespace TestCase203;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return T&HasSend
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        $result = identity(new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase203\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 205 keeps its member graph behavior stable.
     */
    public function testTemplateBoundImportedAliasResolvesConcreteType(): void
    {
        $sources = [
            'TestCase205.php' => <<<'PHP'
                <?php

                namespace TestCase205\Domain;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                namespace TestCase205\App;

                use TestCase205\Domain\HasSend as Sendable;
                use TestCase205\Domain\Mailer;

                /**
                 * @template T of Sendable
                 * @param T $service
                 * @return T
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        $result = identity(new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase205\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 206 keeps its member graph behavior stable.
     */
    public function testTemplateBoundCallableParameterResolvesConcreteType(): void
    {
        $sources = [
            'TestCase206.php' => <<<'PHP'
                <?php

                namespace TestCase206;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @param callable(T): T $handler
                 * @return T
                 */
                function apply($service, callable $handler) { return $handler($service); }

                class TestClass {
                    public function run(): void {
                        $result = apply(new Mailer(), static fn (Mailer $service) => $service);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase206\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 207 keeps its member graph behavior stable.
     */
    public function testTemplateBoundConstructorInferenceResolvesConcreteType(): void
    {
        $sources = [
            'TestCase207.php' => <<<'PHP'
                <?php

                namespace TestCase207;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /** @template T of HasSend */
                class Box {
                    /** @param T $value */
                    public function __construct(private mixed $value) {}

                    /** @return T */
                    public function get() { return $this->value; }
                }

                class TestClass {
                    public function run(): void {
                        $box = new Box(new Mailer());
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase207\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 208 keeps its member graph behavior stable.
     */
    public function testTemplateBoundStaticFactoryInferenceResolvesConcreteType(): void
    {
        $sources = [
            'TestCase208.php' => <<<'PHP'
                <?php

                namespace TestCase208;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /** @template T of HasSend */
                class Box {
                    /** @param T $value */
                    public function __construct(private mixed $value) {}

                    /** @return T */
                    public function get() { return $this->value; }

                    /**
                     * @template U of HasSend
                     * @param U $value
                     * @return self<U>
                     */
                    public static function make($value): self { return new self($value); }
                }

                class TestClass {
                    public function run(): void {
                        $box = Box::make(new Mailer());
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase208\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 209 keeps its member graph behavior stable.
     */
    public function testTemplateBoundCallableParameterAndReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase209.php' => <<<'PHP'
                <?php

                namespace TestCase209;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param callable(T): T $handler
                 * @return T
                 */
                function consume(callable $handler) { return $handler(new Mailer()); }

                class TestClass {
                    public function run(): void {
                        $result = consume(static fn (Mailer $service) => $service);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase209\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 210 keeps its member graph behavior stable.
     */
    public function testTemplateBoundShapeReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase210.php' => <<<'PHP'
                <?php

                namespace TestCase210;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return array{service: T}
                 */
                function wrap($service): array { return ['service' => $service]; }

                class TestClass {
                    public function run(): void {
                        $result = wrap(new Mailer());
                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase210\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 212 keeps its member graph behavior stable.
     */
    public function testTemplateBoundFunctionDoesNotOverrideConcreteType(): void
    {
        $sources = [
            'TestCase212.php' => <<<'PHP'
                <?php

                namespace TestCase212;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return T
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        $result = identity(new PlainService());
                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase212\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 213 keeps its member graph behavior stable.
     */
    public function testTemplateBoundMethodDoesNotOverrideConcreteType(): void
    {
        $sources = [
            'TestCase213.php' => <<<'PHP'
                <?php

                namespace TestCase213;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                class Identity {
                    /**
                     * @template T of HasSend
                     * @param T $service
                     * @return T
                     */
                    public function identity($service) { return $service; }
                }

                class TestClass {
                    public function run(): void {
                        $identity = new Identity();
                        $result = $identity->identity(new PlainService());
                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase213\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 214 keeps its member graph behavior stable.
     */
    public function testTemplateBoundStaticMethodDoesNotOverrideConcreteType(): void
    {
        $sources = [
            'TestCase214.php' => <<<'PHP'
                <?php

                namespace TestCase214;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                class Identity {
                    /**
                     * @template T of HasSend
                     * @param T $service
                     * @return T
                     */
                    public static function identity($service) { return $service; }
                }

                class TestClass {
                    public function run(): void {
                        $result = Identity::identity(new PlainService());
                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase214\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 215 keeps its member graph behavior stable.
     */
    public function testTemplateBoundShapeDoesNotOverrideConcreteFieldType(): void
    {
        $sources = [
            'TestCase215.php' => <<<'PHP'
                <?php

                namespace TestCase215;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                /**
                 * @template T of HasSend
                 * @param array{service: T} $payload
                 * @return T
                 */
                function serviceFromShape(array $payload) { return $payload['service']; }

                class TestClass {
                    public function run(): void {
                        $result = serviceFromShape(['service' => new PlainService()]);
                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase215\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 216 keeps its member graph behavior stable.
     */
    public function testTemplateBoundCallableDoesNotOverrideConcreteReturnType(): void
    {
        $sources = [
            'TestCase216.php' => <<<'PHP'
                <?php

                namespace TestCase216;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                /**
                 * @template T of HasSend
                 * @param callable(): T $factory
                 * @return T
                 */
                function consumeFactory(callable $factory) { return $factory(); }

                class TestClass {
                    public function run(): void {
                        $result = consumeFactory(static fn () => new PlainService());
                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase216\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 218 keeps its member graph behavior stable.
     */
    public function testTemplateBoundGenericClassDoesNotOverrideConcreteType(): void
    {
        $sources = [
            'TestCase218.php' => <<<'PHP'
                <?php

                namespace TestCase218;

                interface HasSend { public function send(): void; }
                class PlainService { public function plainOnly(): void {} }

                /** @template T of HasSend */
                class Box {
                    /** @param T $value */
                    public function __construct(private mixed $value) {}

                    /** @return T */
                    public function get() { return $this->value; }
                }

                class TestClass {
                    public function run(): void {
                        $box = new Box(new PlainService());
                        $box->get()->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase218\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 219 keeps its member graph behavior stable.
     */
    public function testTemplateBoundDoesNotInventBoundOwnerUsageForUnion(): void
    {
        $sources = [
            'TestCase219.php' => <<<'PHP'
                <?php

                namespace TestCase219;

                interface HasSend { public function send(): void; }
                class Mailer implements HasSend { public function send(): void {} }
                class PlainService { public function plainOnly(): void {} }

                /**
                 * @template T of HasSend
                 * @param T $service
                 * @return T
                 */
                function identity($service) { return $service; }

                class TestClass {
                    public function run(): void {
                        /** @var Mailer|PlainService $service */
                        $service = loadMixed();
                        $result = identity($service);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundBoundOwner = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase219\\HasSend' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundBoundOwner = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase219\\Mailer', 'send');
        $this->assertFalse($foundBoundOwner);
    }

    /**
     * Ensures legacy fixture 316 keeps its member graph behavior stable.
     */
    public function testTemplateBoundMultipleTemplatesResolveConcreteTypes(): void
    {
        $sources = [
            'TestCase316.php' => <<<'PHP'
                <?php

                namespace TestCase316;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                class Notifier implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 * @template U of HasSend
                 * @param T $left
                 * @param U $right
                 * @return array{left: T, right: U}
                 */
                function pair($left, $right): array {
                    return ['left' => $left, 'right' => $right];
                }

                class TestClass {
                    public function run(): void {
                        $pair = pair(new Mailer(), new Notifier());

                        $pair['left']->send();
                        $pair['right']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase316\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase316\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 317 keeps its member graph behavior stable.
     */
    public function testTemplateBoundGenericReturnPreservesConcreteArgument(): void
    {
        $sources = [
            'TestCase317.php' => <<<'PHP'
                <?php

                namespace TestCase317;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 */
                class Box {
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get() {
                        return $this->value;
                    }
                }

                class Factory {
                    /**
                     * @template T of HasSend
                     * @param T $value
                     * @return Box<T>
                     */
                    public function wrap($value): Box {
                        return new Box($value);
                    }
                }

                class TestClass {
                    public function run(): void {
                        $factory = new Factory();
                        $box = $factory->wrap(new Mailer());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase317\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 318 keeps its member graph behavior stable.
     */
    public function testTemplateBoundClassShapeReturnPreservesConcreteType(): void
    {
        $sources = [
            'TestCase318.php' => <<<'PHP'
                <?php

                namespace TestCase318;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 */
                class ServiceHolder {
                    /**
                     * @param T $service
                     */
                    public function __construct(private mixed $service) {}

                    /**
                     * @return array{service: T}
                     */
                    public function asShape(): array {
                        return ['service' => $this->service];
                    }
                }

                class TestClass {
                    public function run(): void {
                        $holder = new ServiceHolder(new Mailer());
                        $shape = $holder->asShape();

                        $shape['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase318\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 319 keeps its member graph behavior stable.
     */
    public function testTemplateBoundListParameterPreservesConcreteItemType(): void
    {
        $sources = [
            'TestCase319.php' => <<<'PHP'
                <?php

                namespace TestCase319;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 * @param list<T> $items
                 * @return T
                 */
                function first(array $items) {
                    return $items[0];
                }

                class TestClass {
                    public function run(): void {
                        /** @var list<Mailer> $items */
                        $items = [new Mailer()];

                        first($items)->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase319\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 320 keeps its member graph behavior stable.
     */
    public function testTemplateBoundNullableReturnPreservesConcreteType(): void
    {
        $sources = [
            'TestCase320.php' => <<<'PHP'
                <?php

                namespace TestCase320;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 * @param T $value
                 * @return T|null
                 */
                function maybe($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $result = maybe(new Mailer());

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase320\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 321 keeps its member graph behavior stable.
     */
    public function testTemplateBoundNestedShapeListPreservesConcreteType(): void
    {
        $sources = [
            'TestCase321.php' => <<<'PHP'
                <?php

                namespace TestCase321;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 * @param array{items: list<T>} $config
                 * @return T
                 */
                function firstConfigured(array $config) {
                    return $config['items'][0];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array{items: list<Mailer>} $config */
                        $config = ['items' => [new Mailer()]];

                        firstConfigured($config)->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase321\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 322 keeps its member graph behavior stable.
     */
    public function testTemplateBoundImportedAliasClassTemplatePreservesConcreteType(): void
    {
        $sources = [
            'TestCase322.php' => <<<'PHP'
                <?php

                namespace TestCase322;

                use TestCase322\Domain\HasSend as Sendable;

                /**
                 * @template T of Sendable
                 */
                class Box {
                    /**
                     * @param T $value
                     */
                    public function __construct(private mixed $value) {}

                    /**
                     * @return T
                     */
                    public function get() {
                        return $this->value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $box = new Box(new Domain\Mailer());

                        $box->get()->send();
                    }
                }

                namespace TestCase322\Domain;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase322\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 323 keeps its member graph behavior stable.
     */
    public function testTemplateBoundDoesNotOverrideConcreteTypeInsideNullableUnion(): void
    {
        $sources = [
            'TestCase323.php' => <<<'PHP'
                <?php

                namespace TestCase323;

                interface HasSend {
                    public function send(): void;
                }

                class PlainService {
                    public function plainOnly(): void {}
                }

                /**
                 * @template T of HasSend
                 * @param T $value
                 * @return T|null
                 */
                function maybe($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $result = maybe(new PlainService());

                        $result->plainOnly();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase323\\PlainService', 'plainOnly');
    }
}
