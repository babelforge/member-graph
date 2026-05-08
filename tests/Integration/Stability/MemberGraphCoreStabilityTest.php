<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssue;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphCoreStabilityTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 2 keeps its member graph behavior stable.
     */
    public function testGraphIsIdempotent(): void
    {
        $sources = [
            'TestCase2.php' => <<<'PHP'
                <?php

                namespace TestCase2;

                trait T {
                    public function foo() {}
                }

                class A {
                    use T;
                }
                PHP,
        ];

        $graph1 = $this->buildGraphFromSources($sources);
        $graph2 = $this->buildGraphFromSources($sources);

        $this->assertEquals(
            serialize($graph1),
            serialize($graph2)
        );
    }

    /**
     * Ensures legacy fixture 5 keeps its member graph behavior stable.
     */
    public function testDeclaredInsMerge(): void
    {
        $sources = [
            'TestCase5.php' => <<<'PHP'
                <?php

                namespace TestCase5;

                interface A {
                    public function build();
                }

                interface B {
                    public function build();
                }

                class C implements A, B {
                    public function build() {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $available = $memberDependencyGraph->availableMembers->getByOwner('TestCase5\C');

        $found = false;

        foreach ($available as $member) {
            if ('build' === $member->member->name) {
                $found = true;
                $this->assertNotEmpty($member->declaredIns);
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 6 keeps its member graph behavior stable.
     */
    public function testNamedArgumentProjection(): void
    {
        $sources = [
            'TestCase6.php' => <<<'PHP'
                <?php

                namespace TestCase6;

                trait T {
                    public function run(string $x) {
                        return $this->foo(x: $x);
                    }
                }

                class A {
                    use T;

                    public function foo(string $x) {}
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $parameterUsages = $memberDependencyGraph->parameterUsages->all();

        $found = false;

        foreach ($parameterUsages as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase6\A' === $usage->target->owner
                    && 'x' === $usage->target->parameterName
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }

    /**
     * Ensures legacy fixture 31 keeps its member graph behavior stable.
     */
    public function testConcreteClassDoesNotProduceExtraPolymorphicTargets(): void
    {
        $sources = [
            'TestCase31.php' => <<<'PHP'
                <?php

                namespace TestCase31;

                class A
                {
                    public function foo(): void
                    {
                    }
                }

                class B
                {
                    public function test(A $a): void
                    {
                        $a->foo();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $countA = 0;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase31\A' === $usage->target->owner && 'foo' === $usage->target->name) {
                    ++$countA;
                }
            }
        }

        $this->assertSame(1, $countA);
    }

    /**
     * Ensures legacy fixture 101 keeps its member graph behavior stable.
     */
    public function testReturnTagNotUsableRaisesIssue(): void
    {
        $sources = [
            'TestCase101.php' => <<<'PHP'
                <?php

                namespace TestCase101;

                class ParentService
                {
                    /**
                     * @return
                     */
                    public function make()
                    {
                        return getService();
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
                    && ('TestCase101\\ParentService' === $issue->owner)
                    && ('make' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 102 keeps its member graph behavior stable.
     */
    public function testParamTagNotUsableRaisesIssue(): void
    {
        $sources = [
            'TestCase102.php' => <<<'PHP'
                <?php

                namespace TestCase102;

                class ParentService
                {
                    /**
                     * @param $value
                     * @return void
                     */
                    public function consume($value): void
                    {
                    }
                }

                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundIssue = false;

        /** @var PhpDocResolutionIssue $issue */
        foreach ($memberDependencyGraph->dependencyGraphIssues ?? [] as $issue) {
            $foundIssue = $foundIssue
                || ((PhpDocResolutionIssueType::PARAM_TAG_NOT_USABLE === $issue->type)
                    && ('TestCase102\\ParentService' === $issue->owner)
                    && ('consume' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 114 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationInfersStructuredTypeFromConstructor(): void
    {
        $sources = [
            'TestCase114.php' => <<<'PHP'
                <?php

                namespace TestCase114;

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
                    'TestCase114\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 121 keeps its member graph behavior stable.
     */
    public function testGenericClassInstantiationWithBoundInfersConcreteType(): void
    {
        $sources = [
            'TestCase121.php' => <<<'PHP'
                <?php

                namespace TestCase121;

                interface ServiceInterface
                {
                    public function send(): void;
                }

                class Mailer implements ServiceInterface
                {
                    public function send(): void
                    {
                    }
                }

                /**
                 * @template T of ServiceInterface
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
                    'TestCase121\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 123 keeps its member graph behavior stable.
     */
    public function testGenericPropagationThroughMethodReturn(): void
    {
        $sources = [
            'TestCase123.php' => <<<'PHP'
                <?php

                namespace TestCase123;

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
                     * @return Box<Mailer>
                     */
                    public function createBox()
                    {
                        return new Box(new Mailer());
                    }
                }

                class Runner
                {
                    public function run(): void
                    {
                        $service = new Service();
                        $box = $service->createBox();

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
                    'TestCase123\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }
}
