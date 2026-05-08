<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphPhpDocFlatTypeTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 14 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesVariableType(): void
    {
        $sources = [
            'TestCase14.php' => <<<'PHP'
                <?php

                namespace TestCase14;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function run(): void
                    {
                        /** @var A $service */
                        $service = getService();

                        $service->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase14\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 15 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocWithoutVariableNameResolvesAssignedVariableType(): void
    {
        $sources = [
            'TestCase15.php' => <<<'PHP'
                <?php

                namespace TestCase15;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function run(): void
                    {
                        /** @var A */
                        $service = getService();

                        $service->foo();
                    }
                }


                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase15\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 16 keeps its member graph behavior stable.
     */
    public function testUnsupportedLocalVarPhpDocTypeIsIgnored(): void
    {
        $sources = [
            'TestCase16.php' => <<<'PHP'
                <?php

                namespace TestCase16;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function foo(): void
                    {
                    }

                    public function run(): void
                    {
                        /** @var A|B $service */
                        $service = getService();

                        $service->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundB = false;
        $foundUnknown = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase16\\A' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $foundA = true;
                }

                if (
                    'TestCase16\\B' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $foundB = true;
                }

                if (
                    'unknown' === $usage->target->owner
                    && 'foo' === $usage->target->name
                ) {
                    $foundUnknown = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
        $this->assertFalse($foundUnknown);
    }

    /**
     * Ensures legacy fixture 35 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesImportedShortName(): void
    {
        $sources = [
            'TestCase35.php' => <<<'PHP'
                <?php

                namespace TestCase35;

                use TestCase35\Service\Mailer;

                class Runner
                {
                    public function run(): void
                    {
                        /** @var Mailer $mailer */
                        $mailer = getService();

                        $mailer->send();
                    }
                }

                namespace TestCase35\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase35\Service\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 36 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesImportedAlias(): void
    {
        $sources = [
            'TestCase36.php' => <<<'PHP'
                <?php

                namespace TestCase36;

                use TestCase36\Service\Mailer as AppMailer;

                class Runner
                {
                    public function run(): void
                    {
                        /** @var AppMailer $mailer */
                        $mailer = getService();

                        $mailer->send();
                    }
                }

                namespace TestCase36\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase36\Service\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 37 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesSimpleUnion(): void
    {
        $sources = [
            'TestCase37.php' => <<<'PHP'
                <?php

                namespace TestCase37;

                use TestCase37\Service\Mailer;
                use TestCase37\Service\Notifier;

                class Runner
                {
                    public function run(): void
                    {
                        /** @var Mailer|Notifier $service */
                        $service = getService();

                        $service->send();
                    }
                }

                namespace TestCase37\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase37\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase37\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase37\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundNotifier);
    }

    /**
     * Ensures legacy fixture 38 keeps its member graph behavior stable.
     */
    public function testFileTypeIndexesBuilderSupportsMethodReturnUnionTypes(): void
    {
        $sources = [
            'TestCase38.php' => <<<'PHP'
                <?php

                namespace TestCase38;

                class A {}
                class B {}

                class C
                {
                    public function make(): A|B
                    {
                        return new A();
                    }

                    public function run(): void
                    {
                        $x = $this->make();
                        $x->foo();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundB = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase38\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundA = true;
                }

                if ('TestCase38\B' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundB = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
    }

    /**
     * Ensures legacy fixture 39 keeps its member graph behavior stable.
     */
    public function testFileTypeIndexesBuilderSupportsPropertyUnionTypes(): void
    {
        $sources = [
            'TestCase39.php' => <<<'PHP'
                <?php

                namespace TestCase39;

                class A {}
                class B {}

                class C
                {
                    public A|B $service;

                    public function run(): void
                    {
                        $this->service->foo();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundB = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase39\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundA = true;
                }

                if ('TestCase39\B' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundB = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
    }

    /**
     * Ensures legacy fixture 40 keeps its member graph behavior stable.
     */
    public function testNullableTypesAreReducedToUnderlyingSymbol(): void
    {
        $sources = [
            'TestCase40.php' => <<<'PHP'
                <?php

                namespace TestCase40;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class C
                {
                    public ?A $service;

                    public function run(): void
                    {
                        $this->service->foo();
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase40\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    $foundA = true;
                }
            }
        }

        $this->assertTrue($foundA);
    }

    /**
     * Ensures legacy fixture 42 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesNullableType(): void
    {
        $sources = [
            'TestCase42.php' => <<<'PHP'
                <?php

                namespace TestCase42;

                use TestCase42\Service\Mailer;

                class Runner
                {
                    public function run(): void
                    {
                        /** @var ?Mailer $mailer */
                        $mailer = getService();

                        $mailer->send();
                    }
                }

                namespace TestCase42\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase42\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 43 keeps its member graph behavior stable.
     */
    public function testLocalVarPhpDocResolvesAliasedUnion(): void
    {
        $sources = [
            'TestCase43.php' => <<<'PHP'
                <?php

                namespace TestCase43;

                use TestCase43\Service\Mailer as AppMailer;
                use TestCase43\Service\Notifier as AppNotifier;

                class Runner
                {
                    public function run(): void
                    {
                        /** @var AppMailer|AppNotifier $service */
                        $service = getService();

                        $service->send();
                    }
                }

                namespace TestCase43\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase43\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase43\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase43\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundNotifier);
    }

    /**
     * Ensures legacy fixture 44 keeps its member graph behavior stable.
     */
    public function testParamPhpDocUnionResolvesMethodCallTargets(): void
    {
        $sources = [
            'TestCase44.php' => <<<'PHP'
                <?php

                namespace TestCase44;

                use TestCase44\Service\Mailer;
                use TestCase44\Service\Notifier;

                class Runner
                {
                    /**
                     * @param Mailer|Notifier $service
                     */
                    public function run($service): void
                    {
                        $service->send();
                    }
                }

                namespace TestCase44\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase44\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase44\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase44\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase44\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundNotifier);
    }

    /**
     * Ensures legacy fixture 45 keeps its member graph behavior stable.
     */
    public function testNativeParameterTypeHasPriorityOverParamPhpDoc(): void
    {
        $sources = [
            'TestCase45.php' => <<<'PHP'
                <?php

                namespace TestCase45;

                use TestCase45\Service\Mailer;
                use TestCase45\Service\Notifier;

                class Runner
                {
                    /**
                     * @param Notifier $service
                     */
                    public function run(Mailer $service): void
                    {
                        $service->send();
                    }
                }

                namespace TestCase45\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase45\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase45\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase45\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase45\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 46 keeps its member graph behavior stable.
     */
    public function testReturnPhpDocUnionResolvesMethodCallTargets(): void
    {
        $sources = [
            'TestCase46.php' => <<<'PHP'
                <?php

                namespace TestCase46;

                use TestCase46\Service\Mailer;
                use TestCase46\Service\Notifier;

                class Runner
                {
                    /**
                     * @return Mailer|Notifier
                     */
                    public function make()
                    {
                        return getService();
                    }

                    public function run(): void
                    {
                        $service = $this->make();
                        $service->send();
                    }
                }

                namespace TestCase46\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase46\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase46\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase46\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase46\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundNotifier);
    }

    /**
     * Ensures legacy fixture 47 keeps its member graph behavior stable.
     */
    public function testNativeReturnTypeHasPriorityOverReturnPhpDoc(): void
    {
        $sources = [
            'TestCase47.php' => <<<'PHP'
                <?php

                namespace TestCase47;

                use TestCase47\Service\Mailer;
                use TestCase47\Service\Notifier;

                class Runner
                {
                    /**
                     * @return Notifier
                     */
                    public function make(): Mailer
                    {
                        return new Mailer();
                    }

                    public function run(): void
                    {
                        $service = $this->make();
                        $service->send();
                    }
                }

                namespace TestCase47\Service;

                class Mailer
                {
                    public function send(): void
                    {
                    }
                }

                namespace TestCase47\Service;

                class Notifier
                {
                    public function send(): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase47\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase47\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase47\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 143 keeps its member graph behavior stable.
     */
    public function testNativeOverridesConflictingPhpDoc(): void
    {
        $sources = [
            'TestCase143.php' => <<<'PHP'
                <?php

                namespace TestCase143;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return Notifier
                     */
                    public function make(): Mailer {
                        return new Mailer();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $x = (new Factory())->make();
                        $x->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase143\\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase143\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 144 keeps its member graph behavior stable.
     */
    public function testNativeUnionIsPreserved(): void
    {
        $sources = [
            'TestCase144.php' => <<<'PHP'
                <?php

                namespace TestCase144;

                class A { public function foo(): void {} }
                class B { public function foo(): void {} }

                class Factory {
                    public function make(): A|B {
                        return new A();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $x = (new Factory())->make();
                        $x->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundA = false;
        $foundB = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase144\\A' === $usage->target->owner) {
                    $foundA = true;
                }
                if ('TestCase144\\B' === $usage->target->owner) {
                    $foundB = true;
                }
            }
        }

        $this->assertTrue($foundA);
        $this->assertTrue($foundB);
    }

    /**
     * Ensures legacy fixture 146 keeps its member graph behavior stable.
     */
    public function testMixedNativeUsesPrecisePhpDoc(): void
    {
        $sources = [
            'TestCase146.php' => <<<'PHP'
                <?php

                namespace TestCase146;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return Mailer
                     */
                    public function make(): mixed {
                        return new Mailer();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $x = (new Factory())->make();
                        $x->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase146\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 147 keeps its member graph behavior stable.
     */
    public function testNativeMorePreciseThanPhpDoc(): void
    {
        $sources = [
            'TestCase147.php' => <<<'PHP'
                <?php

                namespace TestCase147;

                class Mailer {
                    public function send(): void {}
                }

                class Factory {
                    /**
                     * @return object
                     */
                    public function make(): Mailer {
                        return new Mailer();
                    }
                }

                class TestClass {
                    public function run(): void {
                        $x = (new Factory())->make();
                        $x->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase147\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 154 keeps its member graph behavior stable.
     */
    public function testNativeUnionReturnTypeStillWinsOverContradictoryPhpDoc(): void
    {
        $sources = [
            'TestCase154.php' => <<<'PHP'
                <?php

                namespace TestCase154;

                class A {
                    public function foo(): void {}
                }

                class B {
                    public function foo(): void {}
                }

                class C {
                    /**
                     * @return A
                     */
                    public function make(): A|B {
                        return new A();
                    }

                    public function run(): void {
                        $x = $this->make();
                        $x->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase154\\A', 'foo');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase154\\B', 'foo');
    }
}
