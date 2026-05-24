<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Tests\Integration\Stability;

/**
 * Covers migrated legacy member graph stability fixtures.
 */
final class MemberGraphCrossFileResolutionTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 365 keeps its member graph behavior stable.
     */
    public function testCrossFileInheritedStaticMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase365/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase365\Domain;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }
                PHP,
            'TestCase365/Support.php' => <<<'PHP'
                <?php

                namespace TestCase365\Support;

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
                PHP,
            'TestCase365/Factory.php' => <<<'PHP'
                <?php

                namespace TestCase365\Factory;

                use TestCase365\Support\Box;

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
                PHP,
            'TestCase365/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase365\App;

                use TestCase365\Domain\Mailer;
                use TestCase365\Domain\Notifier;
                use TestCase365\Factory\Factory;

                class TestClass {
                    public function run(): void {
                        $box = Factory::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase365\\Domain\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase365\\Domain\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 366 keeps its member graph behavior stable.
     */
    public function testCrossFileSelfStaticInheritedMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase366/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase366\Domain;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }
                PHP,
            'TestCase366/Support.php' => <<<'PHP'
                <?php

                namespace TestCase366\Support;

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
                PHP,
            'TestCase366/Factory.php' => <<<'PHP'
                <?php

                namespace TestCase366\Factory;

                use TestCase366\Domain\Mailer;
                use TestCase366\Domain\Notifier;
                use TestCase366\Support\Box;

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
                PHP,
            'TestCase366/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase366\App;

                use TestCase366\Domain\Mailer;
                use TestCase366\Domain\Notifier;
                use TestCase366\Factory\Factory;

                class TestClass extends Factory {
                    public function run(): void {
                        $box = self::boxed(new Mailer(), new Notifier());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase366\\Domain\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase366\\Domain\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 367 keeps its member graph behavior stable.
     */
    public function testCrossFileStaticStaticInheritedMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase367/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase367\Domain;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }
                PHP,
            'TestCase367/Factory.php' => <<<'PHP'
                <?php

                namespace TestCase367\Factory;

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
                PHP,
            'TestCase367/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase367\App;

                use TestCase367\Domain\Mailer;
                use TestCase367\Domain\Notifier;
                use TestCase367\Factory\Factory;

                class TestClass extends Factory {
                    public function run(): void {
                        $pair = static::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase367\\Domain\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase367\\Domain\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 368 keeps its member graph behavior stable.
     */
    public function testCrossFileParentStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase368/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase368\Domain;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }
                PHP,
            'TestCase368/Factory.php' => <<<'PHP'
                <?php

                namespace TestCase368\Factory;

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
                PHP,
            'TestCase368/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase368\App;

                use TestCase368\Domain\Mailer;
                use TestCase368\Domain\Notifier;
                use TestCase368\Factory\Factory;

                class TestClass extends Factory {
                    public function run(): void {
                        $pair = parent::pair(new Mailer(), new Notifier());

                        $pair['primary']->send();
                        $pair['secondary']->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase368\\Domain\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase368\\Domain\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 369 keeps its member graph behavior stable.
     */
    public function testCrossFileInheritDocStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase369/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase369\Domain;

                class Mailer {
                    public function send(): void {}
                }

                class Notifier {
                    public function send(): void {}
                }
                PHP,
            'TestCase369/Factory.php' => <<<'PHP'
                <?php

                namespace TestCase369\Factory;

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
                    /**
                     * @inheritDoc
                     */
                    public static function pair($left, $right): array {
                        return parent::pair($left, $right);
                    }
                }
                PHP,
            'TestCase369/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase369\App;

                use TestCase369\Domain\Mailer;
                use TestCase369\Domain\Notifier;
                use TestCase369\Factory\Factory;

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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase369\\Domain\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase369\\Domain\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 370 keeps its member graph behavior stable.
     */
    public function testCrossFileNewGenericConstructorPreservesConcreteType(): void
    {
        $sources = [
            'TestCase370/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase370\Domain;

                class Mailer {
                    public function send(): void {}
                }
                PHP,
            'TestCase370/Support.php' => <<<'PHP'
                <?php

                namespace TestCase370\Support;

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
                PHP,
            'TestCase370/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase370\App;

                use TestCase370\Domain\Mailer;
                use TestCase370\Support\Box;

                class TestClass {
                    public function run(): void {
                        $box = new Box(new Mailer());

                        $box->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase370\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 371 keeps its member graph behavior stable.
     */
    public function testCrossFileConstructorInjectedGenericPropertyPreservesConcreteType(): void
    {
        $sources = [
            'TestCase371/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase371\Domain;

                class Mailer {
                    public function send(): void {}
                }
                PHP,
            'TestCase371/Support.php' => <<<'PHP'
                <?php

                namespace TestCase371\Support;

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
                PHP,
            'TestCase371/Service.php' => <<<'PHP'
                <?php

                namespace TestCase371\App;

                use TestCase371\Domain\Mailer;
                use TestCase371\Support\Box;

                class Service {
                    /**
                     * @param Box<Mailer> $box
                     */
                    public function __construct(private Box $box) {}

                    public function run(): void {
                        $this->box->get()->send();
                    }
                }
                PHP,
            'TestCase371/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase371\App;

                use TestCase371\Domain\Mailer;
                use TestCase371\Support\Box;

                class TestClass {
                    public function run(): void {
                        $service = new Service(new Box(new Mailer()));

                        $service->run();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase371\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 372 keeps its member graph behavior stable.
     */
    public function testCrossFilePhpDocGenericPropertyPreservesConcreteType(): void
    {
        $sources = [
            'TestCase372/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase372\Domain;

                class Mailer {
                    public function send(): void {}
                }
                PHP,
            'TestCase372/Support.php' => <<<'PHP'
                <?php

                namespace TestCase372\Support;

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
                PHP,
            'TestCase372/Service.php' => <<<'PHP'
                <?php

                namespace TestCase372\App;

                use TestCase372\Domain\Mailer;
                use TestCase372\Support\Box;

                class Service {
                    /**
                     * @var Box<Mailer>
                     */
                    public Box $box;

                    public function run(): void {
                        $this->box->get()->send();
                    }
                }
                PHP,
            'TestCase372/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase372\App;

                use TestCase372\Domain\Mailer;
                use TestCase372\Support\Box;

                class TestClass {
                    public function run(): void {
                        $service = new Service();
                        $service->box = new Box(new Mailer());

                        $service->run();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase372\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 373 keeps its member graph behavior stable.
     */
    public function testCrossFileGenericMethodReturnChainPreservesConcreteType(): void
    {
        $sources = [
            'TestCase373/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase373\Domain;

                class Mailer {
                    public function send(): void {}
                }
                PHP,
            'TestCase373/Support.php' => <<<'PHP'
                <?php

                namespace TestCase373\Support;

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
                PHP,
            'TestCase373/Service.php' => <<<'PHP'
                <?php

                namespace TestCase373\App;

                use TestCase373\Domain\Mailer;
                use TestCase373\Support\Box;

                class Service {
                    /**
                     * @return Box<Mailer>
                     */
                    public function getBox(): Box {
                        return new Box(new Mailer());
                    }
                }
                PHP,
            'TestCase373/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase373\App;

                class TestClass {
                    public function run(): void {
                        $service = new Service();

                        $service->getBox()->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase373\\Domain\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 374 keeps its member graph behavior stable.
     */
    public function testCrossFileNestedGenericConstructorPreservesConcreteType(): void
    {
        $sources = [
            'TestCase374/Domain.php' => <<<'PHP'
                <?php

                namespace TestCase374\Domain;

                class Mailer {
                    public function send(): void {}
                }
                PHP,
            'TestCase374/Support.php' => <<<'PHP'
                <?php

                namespace TestCase374\Support;

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
                PHP,
            'TestCase374/TestClass.php' => <<<'PHP'
                <?php

                namespace TestCase374\App;

                use TestCase374\Domain\Mailer;
                use TestCase374\Support\Box;

                class TestClass {
                    public function run(): void {
                        $box = new Box(new Box(new Mailer()));

                        $box->get()->get()->send();
                    }
                }
                PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase374\\Domain\\Mailer', 'send');
    }
}
