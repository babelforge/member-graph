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
final class MemberGraphInheritDocTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures legacy fixture 89 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMethodReturnTypeIsInheritedFromParent(): void
    {
        $sources = [
            'TestCase89.php' => <<<'PHP'
<?php

namespace TestCase89;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @return Mailer
     */
    public function make()
    {
        return new Mailer();
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

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->make();
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase89\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase89\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 90 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMethodTemplateReturnTypeIsInheritedFromParent(): void
    {
        $sources = [
            'TestCase90.php' => <<<'PHP'
<?php

namespace TestCase90;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value)
    {
        return $value;
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritDoc
     */
    public function identity($value)
    {
        return parent::identity($value);
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->identity(new Mailer());
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase90\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase90\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 91 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocLowercaseMethodReturnTypeIsInheritedFromParent(): void
    {
        $sources = [
            'TestCase91.php' => <<<'PHP'
<?php

namespace TestCase91;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @return Mailer
     */
    public function make()
    {
        return new Mailer();
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritdoc
     */
    public function make()
    {
        return parent::make();
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->make();
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase91\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase91\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 92 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInlineInheritDocMethodReturnTypeIsInheritedFromParent(): void
    {
        $sources = [
            'TestCase92.php' => <<<'PHP'
<?php

namespace TestCase92;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @return Mailer
     */
    public function make()
    {
        return new Mailer();
    }
}

class ChildService extends ParentService
{
    /**
     * {@inheritDoc}
     */
    public function make()
    {
        return parent::make();
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->make();
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase92\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase92\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 93 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocDoesNotInventTypeWhenParentHasNoDoc(): void
    {
        $sources = [
            'TestCase93.php' => <<<'PHP'
<?php

namespace TestCase93;

class ParentService
{
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

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->make();
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundUnknown = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase93\\Runner::run' === $usage->sourceSymbol
                    && 'unknown' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundUnknown = true;
                }
            }
        }

        $this->assertTrue($foundUnknown);
    }

    /**
     * Ensures legacy fixture 94 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMethodTemplateReturnTypeIsInheritedFromParent2(): void
    {
        $sources = [
            'TestCase94.php' => <<<'PHP'
<?php

namespace TestCase94;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value)
    {
        return $value;
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritDoc
     */
    public function identity($value)
    {
        return parent::identity($value);
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->identity(new Mailer());
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase94\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase94\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 95 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMethodTemplateShapeReturnTypeIsInheritedFromParent(): void
    {
        $sources = [
            'TestCase95.php' => <<<'PHP'
<?php

namespace TestCase95;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @template T
     * @param array{service: T} $config
     * @return T
     */
    public function getService(array $config)
    {
        return $config['service'];
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritDoc
     */
    public function getService(array $config)
    {
        return parent::getService($config);
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();

        /** @var array{service: Mailer} $config */
        $config = ['service' => new Mailer()];

        $mailer = $service->getService($config);
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase95\\Runner::run' === $usage->sourceSymbol
                    && 'TestCase95\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 96 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocChildParamOverridesParentIsIncoherent(): void
    {
        $sources = [
            'TestCase96.php' => <<<'PHP'
<?php

namespace TestCase96;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentService
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value)
    {
        return $value;
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritDoc
     * @param Mailer $value
     */
    public function identity($value)
    {
        return parent::identity($value);
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->identity(new Mailer());
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundIssue = false;

        /** @var PhpDocResolutionIssue $issue */
        foreach ($memberDependencyGraph->dependencyGraphIssues as $issue) {
            $foundIssue = $foundIssue ||
                ((PhpDocResolutionIssueType::INHERIT_DOC_MERGE_INCOHERENT === $issue->type)
                    && ('TestCase96\\ChildService' === $issue->owner)
                    && ('identity' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 97 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocChildReturnOverridesParentParamIsInherited(): void
    {
        $sources = [
            'TestCase97.php' => <<<'PHP'
<?php

namespace TestCase97;

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

class ParentService
{
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value)
    {
        return $value;
    }
}

class ChildService extends ParentService
{
    /**
     * @inheritDoc
     * @return Mailer
     */
    public function identity($value)
    {
        return new Mailer();
    }
}

class Runner
{
    public function run(): void
    {
        $service = new ChildService();
        $mailer = $service->identity(new Logger());
        $mailer->send();
    }
}

PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $foundMailer = false;
        $foundLogger = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase97\\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if (
                    'TestCase97\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }

                if (
                    'TestCase97\\Logger' === $usage->target->owner
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
     * Ensures legacy fixture 98 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocParentNotFoundRaisesIssue(): void
    {
        $sources = [
            'TestCase98.php' => <<<'PHP'
<?php

namespace TestCase98;

class ChildService
{
    /**
     * @inheritDoc
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
        foreach ($memberDependencyGraph->dependencyGraphIssues as $issue) {
            $foundIssue = $foundIssue ||
                ((PhpDocResolutionIssueType::INHERIT_DOC_PARENT_NOT_FOUND === $issue->type)
                    && ('TestCase98\\ChildService' === $issue->owner)
                    && ('make' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 99 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocParentNotUsableRaisesIssue(): void
    {
        $sources = [
            'TestCase99.php' => <<<'PHP'
<?php

namespace TestCase99;

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
        foreach ($memberDependencyGraph->dependencyGraphIssues as $issue) {
            $foundIssue = $foundIssue ||
                ((PhpDocResolutionIssueType::RETURN_TAG_NOT_USABLE === $issue->type)
                    && ('TestCase99\\ChildService' === $issue->owner)
                    && ('make' === $issue->member));
        }

        $this->assertTrue($foundIssue);
    }

    /**
     * Ensures legacy fixture 126 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergesParentAndChildPhpDoc(): void
    {
        $sources = [
            'TestCase126.php' => <<<'PHP'
<?php

namespace TestCase126;

class Mailer
{
    public function send(): void
    {
    }
}

class ParentClass
{
    /**
     * @template T
     * @param T $a
     * @param int $b
     * @return T
     */
    public function foo($a, $b)
    {
        return $a;
    }
}

class ChildClass extends ParentClass
{
    /**
     * @inheritDoc
     * @param string $b
     */
    public function foo($a, $b)
    {
        return parent::foo($a, $b);
    }
}

class TestClass
{
    public function run(): void
    {
        $child = new ChildClass();
        $result = $child->foo(new Mailer(), "test");

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
                    'TestCase126\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
    }

    /**
     * Ensures legacy fixture 186 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocReturnTypeIsInheritedFromInterface(): void
    {
        $sources = [
            'TestCase186.php' => <<<'PHP'
<?php

namespace TestCase186;

class Mailer {
    public function send(): void {}
}

function makeMixed(): mixed {
    return new Mailer();
}

interface FactoryContract {
    /**
     * @return Mailer
     */
    public function make();
}

class Factory implements FactoryContract {
    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase186\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 187 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocTemplateReturnTypeIsInheritedFromInterface(): void
    {
        $sources = [
            'TestCase187.php' => <<<'PHP'
<?php

namespace TestCase187;

class Mailer {
    public function send(): void {}
}

interface IdentityContract {
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value);
}

class Identity implements IdentityContract {
    /**
     * @inheritDoc
     */
    public function identity($value) {
        return $value;
    }
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase187\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 188 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocReturnTypeIsInheritedFromTrait(): void
    {
        $sources = [
            'TestCase188.php' => <<<'PHP'
<?php

namespace TestCase188;

class Mailer {
    public function send(): void {}
}

function makeMixed(): mixed {
    return new Mailer();
}

trait MakesMailer {
    /**
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

class Factory {
    use MakesMailer;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase188\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 189 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocReturnTypeIsInheritedFromExtendedInterface(): void
    {
        $sources = [
            'TestCase189.php' => <<<'PHP'
<?php

namespace TestCase189;

class Mailer {
    public function send(): void {}
}

interface BaseFactoryContract {
    /**
     * @return Mailer
     */
    public function make();
}

interface FactoryContract extends BaseFactoryContract {
}

class Factory implements FactoryContract {
    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase189\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 190 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocReturnTypeIsInheritedFromNestedTrait(): void
    {
        $sources = [
            'TestCase190.php' => <<<'PHP'
<?php

namespace TestCase190;

class Mailer {
    public function send(): void {}
}

trait BaseMakesMailer {
    /**
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

trait MakesMailer {
    use BaseMakesMailer;
}

class Factory {
    use MakesMailer;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase190\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 191 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocPrefersParentClassOverInterface(): void
    {
        $sources = [
            'TestCase191.php' => <<<'PHP'
<?php

namespace TestCase191;

class Mailer {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

interface FactoryContract {
    /**
     * @return Notifier
     */
    public function make();
}

class ParentFactory {
    /**
     * @return Mailer
     */
    public function make() {
        return loadMixed();
    }
}

class Factory extends ParentFactory implements FactoryContract {
    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase191\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase191\\Mailer', 'send');
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 199 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTemplateBoundInheritDocFromInterfaceResolvesConcreteType(): void
    {
        $sources = [
            'TestCase199.php' => <<<'PHP'
<?php

namespace TestCase199;

interface HasSend { public function send(): void; }
class Mailer implements HasSend { public function send(): void {} }

interface IdentityContract {
    /**
     * @template T of HasSend
     * @param T $service
     * @return T
     */
    public function identity($service);
}

class Identity implements IdentityContract {
    /** @inheritDoc */
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase199\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 200 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTemplateBoundInheritDocFromParentResolvesConcreteType(): void
    {
        $sources = [
            'TestCase200.php' => <<<'PHP'
<?php

namespace TestCase200;

interface HasSend { public function send(): void; }
class Mailer implements HasSend { public function send(): void {} }

class ParentIdentity {
    /**
     * @template T of HasSend
     * @param T $service
     * @return T
     */
    public function identity($service) { return $service; }
}

class Identity extends ParentIdentity {
    /** @inheritDoc */
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase200\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 211 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTemplateBoundExtendedInterfaceInheritDocResolvesConcreteType(): void
    {
        $sources = [
            'TestCase211.php' => <<<'PHP'
<?php

namespace TestCase211;

interface HasSend { public function send(): void; }
class Mailer implements HasSend { public function send(): void {} }

interface BaseIdentityContract {
    /**
     * @template T of HasSend
     * @param T $service
     * @return T
     */
    public function identity($service);
}

interface IdentityContract extends BaseIdentityContract {}

class Identity implements IdentityContract {
    /** @inheritDoc */
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase211\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 217 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testTemplateBoundInheritDocDoesNotOverrideConcreteType(): void
    {
        $sources = [
            'TestCase217.php' => <<<'PHP'
<?php

namespace TestCase217;

interface HasSend { public function send(): void; }
class PlainService { public function plainOnly(): void {} }

interface IdentityContract {
    /**
     * @template T of HasSend
     * @param T $service
     * @return T
     */
    public function identity($service);
}

class Identity implements IdentityContract {
    /** @inheritDoc */
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase217\\PlainService', 'plainOnly');
    }

    /**
     * Ensures legacy fixture 277 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testClassTraitUseKeepsInterfaceInheritDocReturnType(): void
    {
        $sources = [
            'TestCase277.php' => <<<'PHP'
<?php

namespace TestCase277;

class Mailer {
    public function send(): void {}
}

trait HasHelper {
    public function helper(): void {}
}

interface FactoryContract {
    /**
     * @return Mailer
     */
    public function make();
}

class Factory implements FactoryContract {
    use HasHelper;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();
        $result = $factory->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase277\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 278 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testEnumTraitUseKeepsInterfaceInheritDocReturnType(): void
    {
        $sources = [
            'TestCase278.php' => <<<'PHP'
<?php

namespace TestCase278;

class Mailer {
    public function send(): void {}
}

trait HasHelper {
    public function helper(): void {}
}

interface FactoryContract {
    /**
     * @return Mailer
     */
    public function make();
}

enum Factory implements FactoryContract {
    use HasHelper;

    case Default;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $result = Factory::Default->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase278\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 309 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergeUsesParentTemplateParamAndChildReturn(): void
    {
        $sources = [
            'TestCase309.php' => <<<'PHP'
<?php

namespace TestCase309;

class Mailer {
    public function send(): void {}
}

class ParentService {
    /**
     * @template T
     * @param T $value
     */
    public function identity($value) {
        return $value;
    }
}

class ChildService extends ParentService {
    /**
     * @inheritDoc
     * @return T
     */
    public function identity($value) {
        return parent::identity($value);
    }
}

class TestClass {
    public function run(): void {
        $service = new ChildService();
        $result = $service->identity(new Mailer());
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase309\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 310 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergeUsesParentShapeParamAndChildReturn(): void
    {
        $sources = [
            'TestCase310.php' => <<<'PHP'
<?php

namespace TestCase310;

class Mailer {
    public function send(): void {}
}

class ParentService {
    /**
     * @template T
     * @param array{service: T} $config
     */
    public function getService(array $config) {
        return $config['service'];
    }
}

class ChildService extends ParentService {
    /**
     * @inheritDoc
     * @return T
     */
    public function getService(array $config) {
        return parent::getService($config);
    }
}

class TestClass {
    public function run(): void {
        $service = new ChildService();

        /** @var array{service: Mailer} $config */
        $config = ['service' => new Mailer()];

        $result = $service->getService($config);
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase310\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 311 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergeKeepsChildShapeParamAndParentReturn(): void
    {
        $sources = [
            'TestCase311.php' => <<<'PHP'
<?php

namespace TestCase311;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class ParentService {
    /**
     * @return Mailer
     */
    public function make(array $config) {
        return new Mailer();
    }
}

class ChildService extends ParentService {
    /**
     * @inheritDoc
     * @param array{logger: Logger} $config
     */
    public function make(array $config) {
        $config['logger']->send();

        return parent::make($config);
    }
}

class TestClass {
    public function run(): void {
        $service = new ChildService();

        /** @var array{logger: Logger} $config */
        $config = ['logger' => new Logger()];

        $result = $service->make($config);
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase311\\Logger', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase311\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 312 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocResolvesThroughRecursiveParentChain(): void
    {
        $sources = [
            'TestCase312.php' => <<<'PHP'
<?php

namespace TestCase312;

class Mailer {
    public function send(): void {}
}

class GrandParentService {
    /**
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

class ParentService extends GrandParentService {
    /**
     * @inheritDoc
     */
    public function make() {
        return parent::make();
    }
}

class ChildService extends ParentService {
    /**
     * @inheritDoc
     */
    public function make() {
        return parent::make();
    }
}

class TestClass {
    public function run(): void {
        $service = new ChildService();
        $result = $service->make();
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase312\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 313 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergeUsesInterfaceParamAndChildReturn(): void
    {
        $sources = [
            'TestCase313.php' => <<<'PHP'
<?php

namespace TestCase313;

class Mailer {
    public function send(): void {}
}

interface IdentityContract {
    /**
     * @template T
     * @param T $value
     */
    public function identity($value);
}

class Identity implements IdentityContract {
    /**
     * @inheritDoc
     * @return T
     */
    public function identity($value) {
        return $value;
    }
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase313\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 314 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocMergeUsesTraitParamAndChildReturn(): void
    {
        $sources = [
            'TestCase314.php' => <<<'PHP'
<?php

namespace TestCase314;

class Mailer {
    public function send(): void {}
}

trait IdentityTrait {
    /**
     * @template T
     * @param T $value
     */
    public function identity($value) {
        return $value;
    }
}

class Identity {
    use IdentityTrait;

    /**
     * @inheritDoc
     * @return T
     */
    public function identity($value) {
        return $value;
    }
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase314\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 315 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInlineInheritDocMergeUsesParentTemplateParamAndChildReturn(): void
    {
        $sources = [
            'TestCase315.php' => <<<'PHP'
<?php

namespace TestCase315;

class Mailer {
    public function send(): void {}
}

class ParentService {
    /**
     * @template T
     * @param T $value
     */
    public function identity($value) {
        return $value;
    }
}

class ChildService extends ParentService {
    /**
     * {@inheritDoc}
     * @return T
     */
    public function identity($value) {
        return parent::identity($value);
    }
}

class TestClass {
    public function run(): void {
        $service = new ChildService();
        $result = $service->identity(new Mailer());
        $result->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase315\\Mailer', 'send');
    }

    /**
     * Ensures legacy fixture 335 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocParentClassTemplateTakesPriorityOverInterfaceReturn(): void
    {
        $sources = [
            'TestCase335.php' => <<<'PHP'
<?php

namespace TestCase335;

class Mailer {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

interface IdentityContract {
    /**
     * @return Notifier
     */
    public function identity($value);
}

class ParentIdentity {
    /**
     * @template T
     * @param T $value
     * @return T
     */
    public function identity($value) {
        return $value;
    }
}

class Identity extends ParentIdentity implements IdentityContract {
    /**
     * @inheritDoc
     */
    public function identity($value) {
        return parent::identity($value);
    }
}

class TestClass {
    public function run(): void {
        $identity = new Identity();

        $identity->identity(new Mailer())->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase335\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase335\\Mailer', 'send');
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 336 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocInterfaceTakesPriorityOverTraitReturn(): void
    {
        $sources = [
            'TestCase336.php' => <<<'PHP'
<?php

namespace TestCase336;

class Mailer {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

interface FactoryContract {
    /**
     * @return Mailer
     */
    public function make();
}

trait MakesNotifier {
    /**
     * @return Notifier
     */
    public function make() {
        return new Notifier();
    }
}

class Factory implements FactoryContract {
    use MakesNotifier;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();

        $factory->make()->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase336\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase336\\Mailer', 'send');
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 337 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocChildReturnOverridesParentAndInterfaceReturn(): void
    {
        $sources = [
            'TestCase337.php' => <<<'PHP'
<?php

namespace TestCase337;

class Mailer {
    public function send(): void {}
}

class Logger {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

interface FactoryContract {
    /**
     * @return Notifier
     */
    public function make();
}

class ParentFactory {
    /**
     * @return Logger
     */
    public function make() {
        return new Logger();
    }
}

class Factory extends ParentFactory implements FactoryContract {
    /**
     * @inheritDoc
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();

        $factory->make()->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundLogger = false;
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase337\\Logger' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundLogger = true;
                }

                if ('TestCase337\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase337\\Mailer', 'send');
        $this->assertFalse($foundLogger);
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 338 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocDirectTraitTakesPriorityOverNestedTraitReturn(): void
    {
        $sources = [
            'TestCase338.php' => <<<'PHP'
<?php

namespace TestCase338;

class Mailer {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

trait NestedMakesNotifier {
    /**
     * @return Notifier
     */
    public function make() {
        return new Notifier();
    }
}

trait MakesMailer {
    use NestedMakesNotifier;

    /**
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

class Factory {
    use MakesMailer;

    /**
     * @inheritDoc
     */
    public function make() {
        return loadMixed();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();

        $factory->make()->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase338\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase338\\Mailer', 'send');
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 339 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocRecursiveParentClassTakesPriorityOverInterfaceReturn(): void
    {
        $sources = [
            'TestCase339.php' => <<<'PHP'
<?php

namespace TestCase339;

class Mailer {
    public function send(): void {}
}

class Notifier {
    public function send(): void {}
}

interface FactoryContract {
    /**
     * @return Notifier
     */
    public function make();
}

class GrandParentFactory {
    /**
     * @return Mailer
     */
    public function make() {
        return new Mailer();
    }
}

class ParentFactory extends GrandParentFactory {
    /**
     * @inheritDoc
     */
    public function make() {
        return parent::make();
    }
}

class Factory extends ParentFactory implements FactoryContract {
    /**
     * @inheritDoc
     */
    public function make() {
        return parent::make();
    }
}

class TestClass {
    public function run(): void {
        $factory = new Factory();

        $factory->make()->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);
        $foundNotifier = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase339\\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }
            }
        }

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase339\\Mailer', 'send');
        $this->assertFalse($foundNotifier);
    }

    /**
     * Ensures legacy fixture 353 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase353.php' => <<<'PHP'
<?php

namespace TestCase353;

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
    /**
     * @inheritDoc
     */
    public function boxed($left, $right): Box {
        return parent::boxed($left, $right);
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase353\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase353\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 359 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocStaticMethodTemplateUnionGenericReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase359.php' => <<<'PHP'
<?php

namespace TestCase359;

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
    /**
     * @inheritDoc
     */
    public static function boxed($left, $right): Box {
        return parent::boxed($left, $right);
    }
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase359\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase359\\Notifier', 'send');
    }

    /**
     * Ensures legacy fixture 364 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testInheritDocStaticMethodTemplateUnionShapeReturnPreservesConcreteTypes(): void
    {
        $sources = [
            'TestCase364.php' => <<<'PHP'
<?php

namespace TestCase364;

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
    /**
     * @inheritDoc
     */
    public static function pair($left, $right): array {
        return parent::pair($left, $right);
    }
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

        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase364\\Mailer', 'send');
        $this->assertMemberUsageExists($memberDependencyGraph, 'TestCase364\\Notifier', 'send');
    }
}
