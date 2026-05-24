<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssue;
use BabelForge\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphTemplateResolutionTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 73 keeps its member graph behavior stable.
     */
    public function testTemplateIdentityFunctionResolvesReturnType(): void
    {
        $sources = [
            'TestCase73.php' => <<<'PHP'
                <?php

                namespace TestCase73;

                /**
                 * @template T
                 * @param T $value
                 * @return T
                 */
                function identity($value) {
                    return $value;
                }

                class Mailer {
                    public function send(): void {}
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

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase73\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 74 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesReturnTypeFromGenericParameter(): void
    {
        $sources = [
            'TestCase74.php' => <<<'PHP'
                <?php

                namespace TestCase74;

                /**
                 * @template T
                 * @param array<string, T> $items
                 * @return T
                 */
                function firstItem(array $items) {
                    return reset($items);
                }

                class Mailer {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, Mailer> $mailers */
                        $mailers = ['main' => new Mailer()];

                        $result = firstItem($mailers);

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
                    'TestCase74\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 75 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesReturnTypeFromShapeParameter(): void
    {
        $sources = [
            'TestCase75.php' => <<<'PHP'
                <?php

                namespace TestCase75;

                /**
                 * @template T
                 * @param array{service: T} $config
                 * @return T
                 */
                function getService(array $config) {
                    return $config['service'];
                }

                class Mailer {
                    public function send(): void {}
                }

                class TestClass {
                    public function run(): void {
                        /** @var array{service: Mailer} $config */
                        $config = ['service' => new Mailer()];

                        $result = getService($config);

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
                    'TestCase75\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 76 keeps its member graph behavior stable.
     */
    public function testTemplateMethodCallResolvesReturnType(): void
    {
        $sources = [
            'TestCase76.php' => <<<'PHP'
                <?php

                namespace TestCase76;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public function identity($value) {
                        return $value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        $result = $service->identity(new Mailer());

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
                    'TestCase76\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 77 keeps its member graph behavior stable.
     */
    public function testTemplateMethodCallResolvesReturnTypeFromGenericParameter(): void
    {
        $sources = [
            'TestCase77.php' => <<<'PHP'
                <?php

                namespace TestCase77;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param array<string, T> $items
                     * @return T
                     */
                    public function firstItem(array $items) {
                        return reset($items);
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        /** @var array<string, Mailer> $mailers */
                        $mailers = ['main' => new Mailer()];

                        $result = $service->firstItem($mailers);

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
                    'TestCase77\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 78 keeps its member graph behavior stable.
     */
    public function testTemplateMethodCallResolvesReturnTypeFromShapeParameter(): void
    {
        $sources = [
            'TestCase78.php' => <<<'PHP'
                <?php

                namespace TestCase78;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param array{service: T} $config
                     * @return T
                     */
                    public function getService(array $config) {
                        return $config['service'];
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        /** @var array{service: Mailer} $config */
                        $config = ['service' => new Mailer()];

                        $result = $service->getService($config);

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
                    'TestCase78\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 79 keeps its member graph behavior stable.
     */
    public function testTemplateStaticCallResolvesReturnType(): void
    {
        $sources = [
            'TestCase79.php' => <<<'PHP'
                <?php

                namespace TestCase79;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public static function identity($value) {
                        return $value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $result = Service::identity(new Mailer());

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
                    'TestCase79\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 80 keeps its member graph behavior stable.
     */
    public function testTemplateStaticCallResolvesReturnTypeFromGenericParameter(): void
    {
        $sources = [
            'TestCase80.php' => <<<'PHP'
                <?php

                namespace TestCase80;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param array<string, T> $items
                     * @return T
                     */
                    public static function firstItem(array $items) {
                        return reset($items);
                    }
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, Mailer> $mailers */
                        $mailers = ['main' => new Mailer()];

                        $result = Service::firstItem($mailers);

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
                    'TestCase80\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 81 keeps its member graph behavior stable.
     */
    public function testTemplateStaticCallResolvesReturnTypeFromShapeParameter(): void
    {
        $sources = [
            'TestCase81.php' => <<<'PHP'
                <?php

                namespace TestCase81;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param array{service: T} $config
                     * @return T
                     */
                    public static function getService(array $config) {
                        return $config['service'];
                    }
                }

                class TestClass {
                    public function run(): void {
                        /** @var array{service: Mailer} $config */
                        $config = ['service' => new Mailer()];

                        $result = Service::getService($config);

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
                    'TestCase81\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 82 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesNullableReturnType(): void
    {
        $sources = [
            'TestCase82.php' => <<<'PHP'
                <?php

                namespace TestCase82;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param ?T $value
                 * @return ?T
                 */
                function identityOrNull($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $result = identityOrNull(new Mailer());

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
                    'TestCase82\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 83 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesUnionReturnTypeWithFalse(): void
    {
        $sources = [
            'TestCase83.php' => <<<'PHP'
                <?php

                namespace TestCase83;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param T|false $value
                 * @return T|false
                 */
                function maybeIdentity($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $result = maybeIdentity(new Mailer());

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
                    'TestCase83\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 84 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesSecondTemplateReturnType(): void
    {
        $sources = [
            'TestCase84.php' => <<<'PHP'
                <?php

                namespace TestCase84;

                class Mailer {
                    public function send(): void {}
                }

                class Logger {
                    public function log(): void {}
                }

                /**
                 * @template TKey
                 * @template TValue
                 * @param array<TKey, TValue> $items
                 * @param TKey $key
                 * @return TValue
                 */
                function getByKey(array $items, $key) {
                    return $items[$key];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, Mailer> $items */
                        $items = [
                            'main' => new Mailer(),
                        ];

                        $result = getByKey($items, 'main');

                        $result->send();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase84\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }

                if (
                    'TestCase84\\Logger' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundLogger = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundLogger);
    }

    /**
     * Ensures legacy fixture 85 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesNestedGenericShapeReturnType(): void
    {
        $sources = [
            'TestCase85.php' => <<<'PHP'
                <?php

                namespace TestCase85;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param array<string, array{service: T}> $items
                 * @return T
                 */
                function firstService(array $items) {
                    return reset($items)['service'];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, array{service: Mailer}> $items */
                        $items = [
                            'main' => ['service' => new Mailer()],
                        ];

                        $result = firstService($items);

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
                    'TestCase85\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 86 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesReturnTypeWithNamedArgument(): void
    {
        $sources = [
            'TestCase86.php' => <<<'PHP'
                <?php

                namespace TestCase86;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param T $value
                 * @return T
                 */
                function identity($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $result = identity(value: new Mailer());

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
                    'TestCase86\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 87 keeps its member graph behavior stable.
     */
    public function testTemplateMethodResolvesReturnTypeWithNamedArgument(): void
    {
        $sources = [
            'TestCase87.php' => <<<'PHP'
                <?php

                namespace TestCase87;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public function identity($value) {
                        return $value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        $result = $service->identity(value: new Mailer());

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
                    'TestCase87\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 88 keeps its member graph behavior stable.
     */
    public function testTemplateStaticMethodResolvesReturnTypeWithNamedArgument(): void
    {
        $sources = [
            'TestCase88.php' => <<<'PHP'
                <?php

                namespace TestCase88;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    public static function identity($value) {
                        return $value;
                    }
                }

                class TestClass {
                    public function run(): void {
                        $result = Service::identity(value: new Mailer());

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
                    'TestCase88\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 100 keeps its member graph behavior stable.
     */
    public function testUnresolvedTemplateReferenceRaisesIssue(): void
    {
        $sources = [
            'TestCase100.php' => <<<'PHP'
                <?php

                namespace TestCase100;

                class ParentService
                {
                    /**
                     * @return T
                     */
                    public function make()
                    {
                        return getService();
                    }
                }

                class ChildService extends ParentService
                {
                    /**
                     * @inheritDoc
                     */
                    public function make()
                    {
                        return parent::make();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundIssue = false;

        /** @var PhpDocResolutionIssue $issue */
        foreach ($memberDependencyGraph->dependencyGraphIssues ?? [] as $issue) {
            $foundIssue = $foundIssue
                || ((PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE === $issue->type)
                    && ('TestCase100\\ChildService' === $issue->owner)
                    && ('make' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 103 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesSecondTemplateReturnTypeTwo(): void
    {
        $sources = [
            'TestCase103.php' => <<<'PHP'
                <?php

                namespace TestCase103;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template TKey
                 * @template TValue
                 * @param array<TKey, TValue> $items
                 * @param TKey $key
                 * @return TValue
                 */
                function getByKey(array $items, $key) {
                    return $items[$key];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, Mailer> $items */
                        $items = [
                            'main' => new Mailer(),
                        ];

                        $result = getByKey($items, 'main');

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
                    'TestCase103\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 104 keeps its member graph behavior stable.
     */
    public function testTemplateMethodResolvesSecondTemplateReturnType(): void
    {
        $sources = [
            'TestCase104.php' => <<<'PHP'
                <?php

                namespace TestCase104;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template TKey
                     * @template TValue
                     * @param array<TKey, TValue> $items
                     * @param TKey $key
                     * @return TValue
                     */
                    public function getByKey(array $items, $key) {
                        return $items[$key];
                    }
                }

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        /** @var array<string, Mailer> $items */
                        $items = [
                            'main' => new Mailer(),
                        ];

                        $result = $service->getByKey($items, 'main');

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
                    'TestCase104\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 105 keeps its member graph behavior stable.
     */
    public function testTemplateStaticMethodResolvesSecondTemplateReturnType(): void
    {
        $sources = [
            'TestCase105.php' => <<<'PHP'
                <?php

                namespace TestCase105;

                class Mailer {
                    public function send(): void {}
                }

                class Service {
                    /**
                     * @template TKey
                     * @template TValue
                     * @param array<TKey, TValue> $items
                     * @param TKey $key
                     * @return TValue
                     */
                    public static function getByKey(array $items, $key) {
                        return $items[$key];
                    }
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, Mailer> $items */
                        $items = [
                            'main' => new Mailer(),
                        ];

                        $result = Service::getByKey($items, 'main');

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
                    'TestCase105\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 106 keeps its member graph behavior stable.
     */
    public function testTemplateNestedShapeResolvesReturnType(): void
    {
        $sources = [
            'TestCase106.php' => <<<'PHP'
                <?php

                namespace TestCase106;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param array<string, array{service: T}> $items
                 * @return T
                 */
                function extract(array $items) {
                    return $items['main']['service'];
                }

                class TestClass {
                    public function run(): void {
                        /** @var array<string, array{service: Mailer}> $items */
                        $items = [
                            'main' => ['service' => new Mailer()],
                        ];

                        $result = extract($items);

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
                    'TestCase106\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 107 keeps its member graph behavior stable.
     */
    public function testTemplateUnionReturnResolvesConcreteType(): void
    {
        $sources = [
            'TestCase107.php' => <<<'PHP'
                <?php

                namespace TestCase107;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param T $value
                 * @return T|false
                 */
                function maybe($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $mailer = new Mailer();

                        $result = maybe($mailer);

                        if ($result !== false) {
                            $result->send();
                        }
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase107\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 108 keeps its member graph behavior stable.
     */
    public function testTemplateReturnResolvesUnionArgumentTypes(): void
    {
        $sources = [
            'TestCase108.php' => <<<'PHP'
                <?php

                namespace TestCase108;

                class Mailer {
                    public function send(): void {}
                }

                class Logger {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param T $value
                 * @return T
                 */
                function identity($value) {
                    return $value;
                }

                class TestClass {
                    public function run(bool $flag): void {
                        /** @var Mailer|Logger $service */
                        $service = $flag ? new Mailer() : new Logger();

                        $result = identity($service);

                        $result->send();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase108\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }

                if (
                    'TestCase108\\Logger' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundLogger = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundLogger);
    }

    /**
     * Ensures legacy fixture 109 keeps its member graph behavior stable.
     */
    public function testTemplateUnionSignatureResolvesKnownPart(): void
    {
        $sources = [
            'TestCase109.php' => <<<'PHP'
                <?php

                namespace TestCase109;

                class Mailer {
                    public function send(): void {}
                }

                class Unknown {
                }

                /**
                 * @template T
                 * @template U
                 * @param T $value
                 * @return T|U
                 */
                function maybe($value) {
                    return $value;
                }

                class TestClass {
                    public function run(): void {
                        $mailer = new Mailer();

                        $result = maybe($mailer);

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
                    'TestCase109\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 115 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersMultipleTemplateArguments(): void
    {
        $sources = [
            'TestCase115.php' => <<<'PHP'
                <?php

                namespace TestCase115;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template TKey
                 * @template TValue
                 */
                class PairBox
                {
                    /**
                     * @param TKey $key
                     * @param TValue $value
                     */
                    public function __construct(
                        private mixed $key,
                        private mixed $value,
                    ) {
                    }

                    /**
                     * @return TValue
                     */
                    public function getValue()
                    {
                        return $this->value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = new PairBox('main', new Mailer());
                        $result = $box->getValue();
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
                    'TestCase115\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 116 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersTemplateFromArrayShapeConstructorArgument(): void
    {
        $sources = [
            'TestCase116.php' => <<<'PHP'
                <?php

                namespace TestCase116;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class ServiceBox
                {
                    /**
                     * @param array{service: T} $config
                     */
                    public function __construct(private array $config)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->config['service'];
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = new ServiceBox(['service' => new Mailer()]);
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
                    'TestCase116\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 117 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersTemplateFromNestedArrayShapeConstructorArgument(): void
    {
        $sources = [
            'TestCase117.php' => <<<'PHP'
                <?php

                namespace TestCase117;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 */
                class ServiceBox
                {
                    /**
                     * @param array{service: array{inner: T}} $config
                     */
                    public function __construct(private array $config)
                    {
                    }

                    /**
                     * @return T
                     */
                    public function get()
                    {
                        return $this->config['service']['inner'];
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $box = new ServiceBox([
                            'service' => [
                                'inner' => new Mailer(),
                            ],
                        ]);

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
                    'TestCase117\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 118 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersTemplateFromVariableArgument(): void
    {
        $sources = [
            'TestCase118.php' => <<<'PHP'
                <?php

                namespace TestCase118;

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

                class TestClass
                {
                    public function run(): void
                    {
                        $mailer = new Mailer();

                        $box = new Box($mailer);

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
                    'TestCase118\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 119 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersTemplateFromUnionVariableArgument(): void
    {
        $sources = [
            'TestCase119.php' => <<<'PHP'
                <?php

                namespace TestCase119;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class Logger
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

                class TestClass
                {
                    public function run(bool $flag): void
                    {
                        /** @var Mailer|Logger $value */
                        $value = $flag ? new Mailer() : new Logger();

                        $box = new Box($value);

                        $result = $box->get();
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase119\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }

                if (
                    'TestCase119\\Logger' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundLogger = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundLogger);
    }

    /**
     * Ensures legacy fixture 120 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersMultipleTemplatesFromVariableArguments(): void
    {
        $sources = [
            'TestCase120.php' => <<<'PHP'
                <?php

                namespace TestCase120;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template TKey
                 * @template TValue
                 */
                class PairBox
                {
                    /**
                     * @param TKey $key
                     * @param TValue $value
                     */
                    public function __construct(
                        private mixed $key,
                        private mixed $value,
                    ) {
                    }

                    /**
                     * @return TValue
                     */
                    public function getValue()
                    {
                        return $this->value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $key = 'main';
                        $value = new Mailer();

                        $box = new PairBox($key, $value);

                        $result = $box->getValue();
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
                    'TestCase120\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 129 keeps its member graph behavior stable.
     */
    public function testNestedGenericReturnSubstitutesInnerTemplateType(): void
    {
        $sources = [
            'TestCase129.php' => <<<'PHP'
                <?php

                namespace TestCase129;

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

                class Factory
                {
                    /**
                     * @template U
                     * @param U $value
                     * @return Box<Box<U>>
                     */
                    public function make($value): Box
                    {
                        return new Box(new Box($value));
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $box = $factory->make(new Mailer());
                        $result = $box->get()->get();

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
                    'TestCase129\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 130 keeps its member graph behavior stable.
     */
    public function testArrayShapeReturnSubstitutesTemplateFieldType(): void
    {
        $sources = [
            'TestCase130.php' => <<<'PHP'
                <?php

                namespace TestCase130;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class ServiceHolder
                {
                    public function __construct(private mixed $service)
                    {
                    }

                    public function getService(): mixed
                    {
                        return $this->service;
                    }
                }

                class Factory
                {
                    /**
                     * @template T
                     * @param T $value
                     * @return array{service: T}
                     */
                    public function make($value): array
                    {
                        return ['service' => $value];
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $result = $factory->make(new Mailer());

                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase130\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 131 keeps its member graph behavior stable.
     */
    public function testNestedArrayShapeReturnSubstitutesTemplateInsideGenericField(): void
    {
        $sources = [
            'TestCase131.php' => <<<'PHP'
                <?php

                namespace TestCase131;

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

                class Factory
                {
                    /**
                     * @template U
                     * @param U $value
                     * @return array{box: Box<U>}
                     */
                    public function make($value): array
                    {
                        return ['box' => new Box($value)];
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $result = $factory->make(new Mailer());

                        $result['box']->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase131\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 132 keeps its member graph behavior stable.
     */
    public function testGenericReturnSubstitutesTemplateInsideUnionArgument(): void
    {
        $sources = [
            'TestCase132.php' => <<<'PHP'
                <?php

                namespace TestCase132;

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

                class Factory
                {
                    /**
                     * @template U
                     * @param U $value
                     * @return Box<U|false>
                     */
                    public function make($value): Box
                    {
                        return new Box($value);
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $box = $factory->make(new Mailer());
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
                    'TestCase132\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 133 keeps its member graph behavior stable.
     */
    public function testRootUnionReturnSubstitutesTemplatesAcrossUnionBranches(): void
    {
        $sources = [
            'TestCase133.php' => <<<'PHP'
                <?php

                namespace TestCase133;

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

                class Factory
                {
                    /**
                     * @template U
                     * @param U $value
                     * @return U|Box<U>
                     */
                    public function make($value)
                    {
                        return new Box($value);
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $result = $factory->make(new Mailer());

                        $result->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase133\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 134 keeps its member graph behavior stable.
     */
    public function testIntersectionReturnSubstitutesTemplateType(): void
    {
        $sources = [
            'TestCase134.php' => <<<'PHP'
                <?php

                namespace TestCase134;

                interface HasSend
                {
                    public function send(): void;
                }

                class Mailer implements HasSend
                {
                    public function send(): void
                    {
                    }
                }

                class Factory
                {
                    /**
                     * @template T of HasSend
                     * @param T $value
                     * @return T&HasSend
                     */
                    public function make($value)
                    {
                        return $value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $result = $factory->make(new Mailer());

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
                    'TestCase134\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 135 keeps its member graph behavior stable.
     */
    public function testCallableReturnTypeSubstitutesTemplateReturn(): void
    {
        $sources = [
            'TestCase135.php' => <<<'PHP'
                <?php

                namespace TestCase135;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class Factory
                {
                    /**
                     * @template T
                     * @param T $value
                     * @return callable(): T
                     */
                    public function make($value): callable
                    {
                        return fn () => $value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $callable = $factory->make(new Mailer());
                        $result = $callable();

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
                    'TestCase135\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 138 keeps its member graph behavior stable.
     */
    public function testFunctionReturningTemplateCallableSubstitutesInvocationReturnType(): void
    {
        $sources = [
            'TestCase138.php' => <<<'PHP'
                <?php

                namespace TestCase138;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T
                 * @param T $value
                 * @return callable(): T
                 */
                function makeCallable($value): callable
                {
                    return static fn () => $value;
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $callable = makeCallable(new Mailer());
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
                    'TestCase138\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 139 keeps its member graph behavior stable.
     */
    public function testMethodReturningTemplateCallableSubstitutesInvocationReturnType(): void
    {
        $sources = [
            'TestCase139.php' => <<<'PHP'
                <?php

                namespace TestCase139;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                class Factory
                {
                    /**
                     * @template T
                     * @param T $value
                     * @return callable(): T
                     */
                    public function makeCallable($value): callable
                    {
                        return static fn () => $value;
                    }
                }

                class TestClass
                {
                    public function run(): void
                    {
                        $factory = new Factory();
                        $callable = $factory->makeCallable(new Mailer());
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
                    'TestCase139\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 152 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesNestedListGenericReturnType(): void
    {
        $sources = [
            'TestCase152.php' => <<<'PHP'
                <?php

                namespace TestCase152;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param list<array{service: T}> $items
                 * @return T
                 */
                function firstService(array $items) {
                    return $items[0]['service'];
                }

                class TestClass {
                    public function run(): void {
                        /** @var list<array{service: Mailer}> $items */
                        $items = [['service' => new Mailer()]];

                        $result = firstService($items);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase152\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 153 keeps its member graph behavior stable.
     */
    public function testTemplateFunctionResolvesShapeUnionWithFalseReturnType(): void
    {
        $sources = [
            'TestCase153.php' => <<<'PHP'
                <?php

                namespace TestCase153;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param array{service: T}|false $item
                 * @return T|false
                 */
                function maybeService($item) {
                    return $item === false ? false : $item['service'];
                }

                class TestClass {
                    public function run(): void {
                        $result = maybeService(['service' => new Mailer()]);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase153\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 155 keeps its member graph behavior stable.
     */
    public function testGenericCallableReturnTypeFromTemplateParameter(): void
    {
        $sources = [
            'TestCase155.php' => <<<'PHP'
                <?php

                namespace TestCase155;

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
                        $result = consumeFactory(static fn (): Mailer => new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase155\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 156 keeps its member graph behavior stable.
     */
    public function testMethodGenericCallableReturnTypeFromTemplateParameter(): void
    {
        $sources = [
            'TestCase156.php' => <<<'PHP'
                <?php

                namespace TestCase156;

                class Mailer {
                    public function send(): void {}
                }

                class Consumer {
                    /**
                     * @template T
                     * @param callable(): T $factory
                     * @return T
                     */
                    public function consumeFactory(callable $factory) {
                        return $factory();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $consumer = new Consumer();
                        $result = $consumer->consumeFactory(static fn (): Mailer => new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase156\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 157 keeps its member graph behavior stable.
     */
    public function testUntypedArrowFunctionCallableArgumentInfersTemplateReturnType(): void
    {
        $sources = [
            'TestCase157.php' => <<<'PHP'
                <?php

                namespace TestCase157;

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
                        $result = consumeFactory(static fn () => new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase157\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 158 keeps its member graph behavior stable.
     */
    public function testTypedClosureCallableArgumentInfersTemplateReturnType(): void
    {
        $sources = [
            'TestCase158.php' => <<<'PHP'
                <?php

                namespace TestCase158;

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
                        $result = consumeFactory(static function (): Mailer {
                            return new Mailer();
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase158\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 159 keeps its member graph behavior stable.
     */
    public function testUntypedClosureCallableArgumentInfersTemplateReturnType(): void
    {
        $sources = [
            'TestCase159.php' => <<<'PHP'
                <?php

                namespace TestCase159;

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
                            return new Mailer();
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase159\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 160 keeps its member graph behavior stable.
     */
    public function testCallableUnionReturnTypeSubstitutesTemplateBranch(): void
    {
        $sources = [
            'TestCase160.php' => <<<'PHP'
                <?php

                namespace TestCase160;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): T|false $factory
                 * @return T|false
                 */
                function consumeMaybeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeMaybeFactory(static fn (): Mailer => new Mailer());
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase160\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 161 keeps its member graph behavior stable.
     */
    public function testCallableShapeReturnTypeSubstitutesTemplateField(): void
    {
        $sources = [
            'TestCase161.php' => <<<'PHP'
                <?php

                namespace TestCase161;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): array{service: T} $factory
                 * @return array{service: T}
                 */
                function consumeShapeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeShapeFactory(static fn () => ['service' => new Mailer()]);
                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase161\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 162 keeps its member graph behavior stable.
     */
    public function testCallableNestedListShapeReturnTypeSubstitutesTemplateField(): void
    {
        $sources = [
            'TestCase162.php' => <<<'PHP'
                <?php

                namespace TestCase162;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): list<array{service: T}> $factory
                 * @return list<array{service: T}>
                 */
                function consumeNestedShapeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeNestedShapeFactory(static fn () => [['service' => new Mailer()]]);
                        $result[0]['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase162\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 164 keeps its member graph behavior stable.
     */
    public function testUntypedClosureShapeUnionReturnSubstitutesTemplateField(): void
    {
        $sources = [
            'TestCase164.php' => <<<'PHP'
                <?php

                namespace TestCase164;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(): array{service: T}|false $factory
                 * @return array{service: T}|false
                 */
                function consumeMaybeShapeFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(bool $flag): void {
                        $result = consumeMaybeShapeFactory(static function () use ($flag) {
                            if ($flag) {
                                return ['service' => new Mailer()];
                            }

                            return false;
                        });
                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase164\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 165 keeps its member graph behavior stable.
     */
    public function testCallableGenericObjectReturnSubstitutesTemplateArgument(): void
    {
        $sources = [
            'TestCase165.php' => <<<'PHP'
                <?php

                namespace TestCase165;

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
                    public function get() {
                        return $this->value;
                    }
                }

                /**
                 * @template T
                 * @param callable(): Box<T> $factory
                 * @return Box<T>
                 */
                function consumeBoxFactory(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $box = consumeBoxFactory(static fn () => new Box(new Mailer()));
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase165\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 170 keeps its member graph behavior stable.
     */
    public function testCallableTypedParameterSubstitutesReturnTemplate(): void
    {
        $sources = [
            'TestCase170.php' => <<<'PHP'
                <?php

                namespace TestCase170;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(T): T $factory
                 * @return T
                 */
                function consumeTypedFactory(callable $factory) {
                    return $factory(new Mailer());
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeTypedFactory(static function (Mailer $service) {
                            return $service;
                        });
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase170\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 175 keeps its member graph behavior stable.
     */
    public function testCallableShapeParameterSubstitutesReturnTemplate(): void
    {
        $sources = [
            'TestCase175.php' => <<<'PHP'
                <?php

                namespace TestCase175;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(array{service: T}): T $factory
                 * @return T
                 */
                function consumeShapeHandler(callable $factory) {
                    return $factory(['service' => new Mailer()]);
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(array{service: Mailer}): Mailer $handler */
                        $handler = static function (array $payload) {
                            return $payload['service'];
                        };

                        $result = consumeShapeHandler($handler);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase175\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 179 keeps its member graph behavior stable.
     */
    public function testCallableShapeParameterFromLocalVariableSubstitutesReturnTemplate(): void
    {
        $sources = [
            'TestCase179.php' => <<<'PHP'
                <?php

                namespace TestCase179;

                class Mailer {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @param callable(array{service: T}): T $handler
                 * @return T
                 */
                function consumeShapeHandler(callable $handler) {
                    return $handler(['service' => new Mailer()]);
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(array{service: Mailer}): Mailer $handler */
                        $handler = static function (array $payload) {
                            return $payload['service'];
                        };

                        $result = consumeShapeHandler($handler);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase179\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 184 keeps its member graph behavior stable.
     */
    public function testCallableReturningGenericShapeKeepsNestedTemplate(): void
    {
        $sources = [
            'TestCase184.php' => <<<'PHP'
                <?php

                namespace TestCase184;

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
                    public function get() {
                        return $this->value;
                    }
                }

                /**
                 * @template T
                 * @param callable(): array{box: Box<T>} $factory
                 * @return array{box: Box<T>}
                 */
                function consumeBoxShapeFactory(callable $factory): array {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        $result = consumeBoxShapeFactory(static fn () => ['box' => new Box(new Mailer())]);
                        $result['box']->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase184\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 204 keeps its member graph behavior stable.
     */
    public function testTemplateClassBoundResolvesConcreteSubclass(): void
    {
        $sources = [
            'TestCase204.php' => <<<'PHP'
                <?php

                namespace TestCase204;

                class BaseMailer { public function send(): void {} }
                class Mailer extends BaseMailer {}

                /**
                 * @template T of BaseMailer
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase204\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 324 keeps its member graph behavior stable.
     */
    public function testTemplateUnionReturnPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase324.php' => <<<'PHP'
                <?php

                namespace TestCase324;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return T|U
                 */
                function pick($left, $right) {
                    return $left;
                }

                class TestClass {
                    public function run(): void {
                        $result = pick(new Mailer(), new Notifier());

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase324\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase324\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 325 keeps its member graph behavior stable.
     */
    public function testTemplateUnionShapeFieldPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase325.php' => <<<'PHP'
                <?php

                namespace TestCase325;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return array{service: T|U}
                 */
                function shape($left, $right): array {
                    return ['service' => $left];
                }

                class TestClass {
                    public function run(): void {
                        $result = shape(new Mailer(), new Notifier());

                        $result['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase325\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase325\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 326 keeps its member graph behavior stable.
     */
    public function testTemplateUnionGenericArgumentPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase326.php' => <<<'PHP'
                <?php

                namespace TestCase326;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return Box<T|U>
                 */
                function boxed($left, $right): Box {
                    return new Box($left);
                }

                class TestClass {
                    public function run(): void {
                        $box = boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase326\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase326\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 327 keeps its member graph behavior stable.
     */
    public function testTemplateUnionCallableReturnPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase327.php' => <<<'PHP'
                <?php

                namespace TestCase327;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param callable(): (T|U) $factory
                 * @return T|U
                 */
                function apply(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(): (Mailer|Notifier) $factory */
                        $factory = static fn() => new Mailer();

                        $result = apply($factory);
                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase327\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase327\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 328 keeps its member graph behavior stable.
     */
    public function testParenthesizedTemplateUnionReturnPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase328.php' => <<<'PHP'
                <?php

                namespace TestCase328;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return (T|U)
                 */
                function pick($left, $right) {
                    return $left;
                }

                class TestClass {
                    public function run(): void {
                        $result = pick(new Mailer(), new Notifier());

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase328\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase328\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 329 keeps its member graph behavior stable.
     */
    public function testNullableTemplateUnionReturnPreservesBothConcreteTypes(): void
    {
        $sources = [
            'TestCase329.php' => <<<'PHP'
                <?php

                namespace TestCase329;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return T|U|null
                 */
                function maybe($left, $right) {
                    return $left;
                }

                class TestClass {
                    public function run(): void {
                        $result = maybe(new Mailer(), new Notifier());

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase329\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase329\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 330 keeps its member graph behavior stable.
     */
    public function testTemplateUnionInsideShapeInsideGenericPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase330.php' => <<<'PHP'
                <?php

                namespace TestCase330;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return Box<array{service: T|U}>
                 */
                function boxedShape($left, $right): Box {
                    return new Box(['service' => $left]);
                }

                class TestClass {
                    public function run(): void {
                        $box = boxedShape(new Mailer(), new Notifier());
                        $shape = $box->get();

                        $shape['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase330\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase330\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 331 keeps its member graph behavior stable.
     */
    public function testTemplateUnionInsideListShapePreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase331.php' => <<<'PHP'
                <?php

                namespace TestCase331;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return array{items: list<T|U>}
                 */
                function configuredList($left, $right): array {
                    return ['items' => [$left, $right]];
                }

                class TestClass {
                    public function run(): void {
                        $config = configuredList(new Mailer(), new Notifier());

                        $config['items'][0]->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase331\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase331\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 332 keeps its member graph behavior stable.
     */
    public function testTemplateUnionInsideNestedGenericPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase332.php' => <<<'PHP'
                <?php

                namespace TestCase332;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return Box<Box<T|U>>
                 */
                function nestedBox($left, $right): Box {
                    return new Box(new Box($left));
                }

                class TestClass {
                    public function run(): void {
                        $box = nestedBox(new Mailer(), new Notifier());

                        $box->get()->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase332\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase332\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 333 keeps its member graph behavior stable.
     */
    public function testTemplateUnionInsideCallableGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase333.php' => <<<'PHP'
                <?php

                namespace TestCase333;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param callable(): Box<T|U> $factory
                 * @return Box<T|U>
                 */
                function apply(callable $factory): Box {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(): Box<Mailer|Notifier> $factory */
                        $factory = static fn() => new Box(new Mailer());

                        $box = apply($factory);
                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase333\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase333\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 334 keeps its member graph behavior stable.
     */
    public function testTemplateUnionInsideNullableGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase334.php' => <<<'PHP'
                <?php

                namespace TestCase334;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return Box<T|U>|null
                 */
                function maybeBox($left, $right): ?Box {
                    return new Box($left);
                }

                class TestClass {
                    public function run(): void {
                        $box = maybeBox(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase334\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase334\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 345 keeps its member graph behavior stable.
     */
    public function testParenthesizedTemplateUnionInsideGenericPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase345.php' => <<<'PHP'
                <?php

                namespace TestCase345;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return Box<(T|U)>
                 */
                function boxed($left, $right): Box {
                    return new Box($left);
                }

                class TestClass {
                    public function run(): void {
                        $box = boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase345\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase345\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 346 keeps its member graph behavior stable.
     */
    public function testParenthesizedTemplateUnionInsideShapePreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase346.php' => <<<'PHP'
                <?php

                namespace TestCase346;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return array{service: (T|U)}
                 */
                function shape($left, $right): array {
                    return ['service' => $left];
                }

                class TestClass {
                    public function run(): void {
                        $shape = shape(new Mailer(), new Notifier());

                        $shape['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase346\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase346\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 347 keeps its member graph behavior stable.
     */
    public function testTemplateIntersectionUnionPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase347.php' => <<<'PHP'
                <?php

                namespace TestCase347;

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
                 * @return (T&HasSend)|U
                 */
                function pick($left, $right) {
                    return $left;
                }

                class TestClass {
                    public function run(): void {
                        $result = pick(new Mailer(), new Notifier());

                        $result->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase347\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase347\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 348 keeps its member graph behavior stable.
     */
    public function testNullableParenthesizedTemplateGenericPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase348.php' => <<<'PHP'
                <?php

                namespace TestCase348;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                /**
                 * @template T
                 * @template U
                 * @param T $left
                 * @param U $right
                 * @return (Box<T|U>)|null
                 */
                function maybeBox($left, $right): ?Box {
                    return new Box($left);
                }

                class TestClass {
                    public function run(): void {
                        $box = maybeBox(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase348\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase348\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 349 keeps its member graph behavior stable.
     */
    public function testParenthesizedTemplateIntersectionInsideCallablePreservesConcreteType(): void
    {
        $sources = [
            'TestCase349.php' => <<<'PHP'
                <?php

                namespace TestCase349;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                /**
                 * @template T of HasSend
                 * @param callable(): (T&HasSend) $factory
                 * @return T&HasSend
                 */
                function apply(callable $factory) {
                    return $factory();
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(): (Mailer&HasSend) $factory */
                        $factory = static fn() => new Mailer();

                        apply($factory)->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase349\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 350 keeps its member graph behavior stable.
     */
    public function testMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase350.php' => <<<'PHP'
                <?php

                namespace TestCase350;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class TestClass {
                    public function run(): void {
                        $factory = new Factory();
                        $box = $factory->boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase350\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase350\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 351 keeps its member graph behavior stable.
     */
    public function testStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase351.php' => <<<'PHP'
                <?php

                namespace TestCase351;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return array{service: T|U}
                     */
                    public static function shape($left, $right): array {
                        return ['service' => $left];
                    }
                }

                class TestClass {
                    public function run(): void {
                        $shape = Factory::shape(new Mailer(), new Notifier());

                        $shape['service']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase351\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase351\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 352 keeps its member graph behavior stable.
     */
    public function testInheritedMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase352.php' => <<<'PHP'
                <?php

                namespace TestCase352;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class Factory extends ParentFactory {
                }

                class TestClass {
                    public function run(): void {
                        $factory = new Factory();
                        $box = $factory->boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase352\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase352\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 354 keeps its member graph behavior stable.
     */
    public function testMethodTemplateIntersectionCallableReturnPreservesConcreteType(): void
    {
        $sources = [
            'TestCase354.php' => <<<'PHP'
                <?php

                namespace TestCase354;

                interface HasSend {
                    public function send(): void;
                }

                class Mailer implements HasSend {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @template T of HasSend
                     * @param callable(): (T&HasSend) $factory
                     * @return T&HasSend
                     */
                    public function apply(callable $factory) {
                        return $factory();
                    }
                }

                class TestClass {
                    public function run(): void {
                        /** @var callable(): (Mailer&HasSend) $callable */
                        $callable = static fn() => new Mailer();

                        $factory = new Factory();
                        $factory->apply($callable)->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase354\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 355 keeps its member graph behavior stable.
     */
    public function testInheritedStaticMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase355.php' => <<<'PHP'
                <?php

                namespace TestCase355;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public static function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class Factory extends ParentFactory {
                }

                class TestClass {
                    public function run(): void {
                        $box = Factory::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase355\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase355\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 356 keeps its member graph behavior stable.
     */
    public function testSelfStaticInheritedMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase356.php' => <<<'PHP'
                <?php

                namespace TestCase356;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public static function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $box = self::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase356\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase356\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 357 keeps its member graph behavior stable.
     */
    public function testStaticStaticInheritedMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase357.php' => <<<'PHP'
                <?php

                namespace TestCase357;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public static function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $box = static::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase357\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase357\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 358 keeps its member graph behavior stable.
     */
    public function testParentStaticMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase358.php' => <<<'PHP'
                <?php

                namespace TestCase358;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                /**
                 * @template T
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

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return Box<T|U>
                     */
                    public static function boxed($left, $right): Box {
                        return new Box($left);
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $box = parent::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase358\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase358\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 360 keeps its member graph behavior stable.
     */
    public function testInheritedStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase360.php' => <<<'PHP'
                <?php

                namespace TestCase360;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return array{primary: T, secondary: U}
                     */
                    public static function pair($left, $right): array {
                        return ['primary' => $left, 'secondary' => $right];
                    }
                }

                class Factory extends ParentFactory {
                }

                class TestClass {
                    public function run(): void {
                        $pair = Factory::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase360\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase360\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 361 keeps its member graph behavior stable.
     */
    public function testSelfStaticInheritedMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase361.php' => <<<'PHP'
                <?php

                namespace TestCase361;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return array{primary: T, secondary: U}
                     */
                    public static function pair($left, $right): array {
                        return ['primary' => $left, 'secondary' => $right];
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $pair = self::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase361\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase361\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 362 keeps its member graph behavior stable.
     */
    public function testStaticStaticInheritedMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase362.php' => <<<'PHP'
                <?php

                namespace TestCase362;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return array{primary: T, secondary: U}
                     */
                    public static function pair($left, $right): array {
                        return ['primary' => $left, 'secondary' => $right];
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $pair = static::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase362\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase362\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 363 keeps its member graph behavior stable.
     */
    public function testParentStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase363.php' => <<<'PHP'
                <?php

                namespace TestCase363;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class ParentFactory {
                    /**
                     * @template T
                     * @template U
                     * @param T $left
                     * @param U $right
                     * @return array{primary: T, secondary: U}
                     */
                    public static function pair($left, $right): array {
                        return ['primary' => $left, 'secondary' => $right];
                    }
                }

                class Factory extends ParentFactory {
                    public function run(): void {
                        $pair = parent::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase363\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase363\\Notifier', 'send');
    }
}
