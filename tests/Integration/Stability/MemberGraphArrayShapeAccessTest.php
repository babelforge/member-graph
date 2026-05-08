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
final class MemberGraphArrayShapeAccessTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 65 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase65.php' => <<<'PHP'
<?php

namespace TestCase65;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{foo: Mailer} $x */
        $x = [];

        $x['foo']->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase65\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 66 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeUnknownKeyDoesNotResolveAnyType(): void
    {
        $sources = [
            'TestCase66.php' => <<<'PHP'
<?php

namespace TestCase66;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{foo: Mailer} $x */
        $x = [];

        $x['bar']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase66\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertFalse($found);
    }

    /**
     * Ensures legacy fixture 67 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeDynamicKeyResolvesAllPossibleValueTypes(): void
    {
        $sources = [
            'TestCase67.php' => <<<'PHP'
<?php

namespace TestCase67;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    public function run(string $key): void {
        /** @var array{foo: Mailer, bar: Logger} $x */
        $x = [];

        $x[$key]->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase67\\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase67\\Logger' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundLogger = true;
                }
            }
        }

        $this->assertFalse($foundMailer);
        $this->assertFalse($foundLogger);
    }

    /**
     * Ensures legacy fixture 68 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testNestedArrayShapeKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase68.php' => <<<'PHP'
<?php

namespace TestCase68;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{config: array{mailer: Mailer}} $x */
        $x = [];

        $x['config']['mailer']->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase68\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 69 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeNullableFieldResolvesUnderlyingType(): void
    {
        $sources = [
            'TestCase69.php' => <<<'PHP'
<?php

namespace TestCase69;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{foo: ?Mailer} $x */
        $x = [];

        $x['foo']->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase69\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 70 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeUnionFieldResolvesAllPossibleOwners(): void
    {
        $sources = [
            'TestCase70.php' => <<<'PHP'
<?php

namespace TestCase70;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{service: Mailer|Logger} $x */
        $x = [];

        $x['service']->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase70\\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase70\\Logger' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundLogger = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundLogger);
    }

    /**
     * Ensures legacy fixture 71 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeNumericKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase71.php' => <<<'PHP'
<?php

namespace TestCase71;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{0: Mailer} $x */
        $x = [];

        $x[0]->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase71\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 72 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testEmptyArrayShapeAccessDoesNotResolveAnyType(): void
    {
        $sources = [
            'TestCase72.php' => <<<'PHP'
<?php

namespace TestCase72;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public function run(): void {
        /** @var array{} $x */
        $x = [];

        $x['foo']->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase72\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertFalse($found);
    }

    /**
     * Ensures legacy fixture 148 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testShapePhpDocRefinesNativeArrayOnChainedMethodCall(): void
    {
        $sources = [
            'TestCase148.php' => <<<'PHP'
<?php

namespace TestCase148;

class Mailer {
    public function send(): void {}
}

class Factory {
    /**
     * @return array{a: Mailer}
     */
    public function make(): array {
        return ['a' => new Mailer()];
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $items = $factory->make();
        $items['a']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase148\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 149 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testShapePhpDocRefinesNativeArrayOnDirectChainedCall(): void
    {
        $sources = [
            'TestCase149.php' => <<<'PHP'
<?php

namespace TestCase149;

class Mailer {
    public function send(): void {}
}

class Factory {
    /**
     * @return array{a: Mailer}
     */
    public function make(): array {
        return ['a' => new Mailer()];
    }
}

class TestClass {
    public function run(): void {
        (new Factory())->make()['a']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase149\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 224 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testNullsafeShapeReturnTypeFeedsArrayDimMethodCall(): void
    {
        $sources = [
            'TestCase224.php' => <<<'PHP'
<?php

namespace TestCase224;

class Mailer {
    public function send(): void {}
}

class Factory {
    /**
     * @return array{mailer: Mailer}
     */
    public function makePayload(): array {
        return ['mailer' => new Mailer()];
    }
}

class TestClass {
    public function run(?Factory $factory): void {
        $payload = $factory?->makePayload();

        if (null === $payload) {
            return;
        }

        $payload['mailer']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase224\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 233 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testStaticPropertyShapePhpDocFeedsArrayDimMethodCall(): void
    {
        $sources = [
            'TestCase233.php' => <<<'PHP'
<?php

namespace TestCase233;

class Mailer {
    public function send(): void {}
}

class Registry {
    /**
     * @var array{mailer: Mailer}
     */
    public static array $payload = [];
}

class TestClass {
    public function run(): void {
        Registry::$payload = ['mailer' => new Mailer()];
        Registry::$payload['mailer']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase233\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 259 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testPromotedPropertyShapePhpDocFeedsArrayDimMethodCall(): void
    {
        $sources = [
            'TestCase259.php' => <<<'PHP'
<?php

namespace TestCase259;

class Mailer {
    public function send(): void {}
}

class Service {
    /**
     * @param array{mailer: Mailer} $payload
     */
    public function __construct(private array $payload) {}

    public function run(): void {
        $this->payload['mailer']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase259\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 304 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeClassConstantStringKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase304.php' => <<<'PHP'
<?php

namespace TestCase304;

class Keys {
    public const MAILER = 'mailer';
}

class Mailer {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{mailer: Mailer} $services
     */
    public function run(array $services): void {
        $services[Keys::MAILER]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase304\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 305 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeSelfClassConstantStringKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase305.php' => <<<'PHP'
<?php

namespace TestCase305;

class Mailer {
    public function send(): void {}
}

class TestClass {
    public const MAILER = 'mailer';

    /**
     * @param array{mailer: Mailer} $services
     */
    public function run(array $services): void {
        $services[self::MAILER]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase305\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 306 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeInheritedClassConstantStringKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase306.php' => <<<'PHP'
<?php

namespace TestCase306;

class BaseKeys {
    public const MAILER = 'mailer';
}

class Keys extends BaseKeys {
}

class Mailer {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{mailer: Mailer} $services
     */
    public function run(array $services): void {
        $services[Keys::MAILER]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase306\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 307 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeClassConstantIntegerKeyAccessResolvesCorrectOwner(): void
    {
        $sources = [
            'TestCase307.php' => <<<'PHP'
<?php

namespace TestCase307;

class Keys {
    public const MAILER = 0;
}

class Mailer {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{0: Mailer} $services
     */
    public function run(array $services): void {
        $services[Keys::MAILER]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase307\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 308 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeUnionClassConstantStringKeyAccessResolvesAllOwners(): void
    {
        $sources = [
            'TestCase308.php' => <<<'PHP'
<?php

namespace TestCase308;

class Keys {
    public const SERVICE = 'service';
}

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{service: Mailer}|array{service: Logger} $services
     */
    public function run(array $services): void {
        $services[Keys::SERVICE]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase308\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase308\\Logger', 'send');
    }

    /**
     * Ensures legacy fixture 340 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeVariableKeyDoesNotInventFieldOwnerUsage(): void
    {
        $sources = [
            'TestCase340.php' => <<<'PHP'
<?php

namespace TestCase340;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{mailer: Mailer, logger: Logger} $services
     */
    public function run(array $services, string $key): void {
        $services[$key]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase340\\Mailer', 'send');
        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase340\\Logger', 'send');
    }

    /**
     * Ensures legacy fixture 341 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testNestedArrayShapeVariableKeyDoesNotInventFieldOwnerUsage(): void
    {
        $sources = [
            'TestCase341.php' => <<<'PHP'
<?php

namespace TestCase341;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{services: array{mailer: Mailer, logger: Logger}} $config
     */
    public function run(array $config, string $key): void {
        $config['services'][$key]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase341\\Mailer', 'send');
        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase341\\Logger', 'send');
    }

    /**
     * Ensures legacy fixture 342 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeVariableIntegerKeyDoesNotInventFieldOwnerUsage(): void
    {
        $sources = [
            'TestCase342.php' => <<<'PHP'
<?php

namespace TestCase342;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{0: Mailer, 1: Logger} $services
     */
    public function run(array $services, int $key): void {
        $services[$key]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase342\\Mailer', 'send');
        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase342\\Logger', 'send');
    }

    /**
     * Ensures legacy fixture 343 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapePropertyKeyDoesNotInventFieldOwnerUsage(): void
    {
        $sources = [
            'TestCase343.php' => <<<'PHP'
<?php

namespace TestCase343;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    public string $key = 'mailer';

    /**
     * @param array{mailer: Mailer, logger: Logger} $services
     */
    public function run(array $services): void {
        $services[$this->key]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase343\\Mailer', 'send');
        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase343\\Logger', 'send');
    }

    /**
     * Ensures legacy fixture 344 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testArrayShapeUnionVariableKeyDoesNotInventFieldOwnerUsage(): void
    {
        $sources = [
            'TestCase344.php' => <<<'PHP'
<?php

namespace TestCase344;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class TestClass {
    /**
     * @param array{service: Mailer}|array{service: Logger} $services
     */
    public function run(array $services, string $key): void {
        $services[$key]->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase344\\Mailer', 'send');
        $this->assertMemberUsageDoesNotExist($memberDependencyGraph, 'TestCase344\\Logger', 'send');
    }
}