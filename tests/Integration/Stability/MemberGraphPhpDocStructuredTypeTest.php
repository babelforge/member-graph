<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Tests\Integration\Stability;

use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssue;
use PhpNoobs\MemberGraph\Application\Validator\PhpDoc\PhpDocResolutionIssueType;
use PhpNoobs\MemberGraph\Application\Source\MemberGraphPhpSourceRegistryInstance;
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
final class MemberGraphPhpDocStructuredTypeTest extends AbstractMemberGraphStabilityTestCase
{
    /**
     * Ensures generic PHPDoc types keep their outer and inner symbols.
     *
     * @return void
     */
    public function testLocalVarPhpDocGenericTypeFlattensInnerSymbols(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $lexer = $factory->createLexer();
        $parser = $factory->createParser();

        $tokens = new TokenIterator(
            $lexer->tokenize('/** @var Collection<Mailer> $items */')
        );

        $phpDocNode = $parser->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $resolved = $resolver->resolveStructured(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Collection', 'TestCase\\Collection')->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        );

        $flattened = $resolved->flattenAllSymbols()->all();
        sort($flattened);

        $this->assertSame(
            ['TestCase\Collection', 'TestCase\Service\Mailer'],
            $flattened,
        );
    }

    /**
     * Ensures generic PHPDoc types support multiple arguments.
     *
     * @return void
     */
    public function testPhpDocGenericTypeSupportsMultipleArguments(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var Map<string, Mailer> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $resolved = $resolver->resolveStructured(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer')->set('Map', 'TestCase\\Map'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        );

        $flattened = $resolved->flattenAllSymbols(true)->all();
        sort($flattened);

        $this->assertSame(
            ['TestCase\Map', 'TestCase\Service\Mailer'],
            $flattened,
        );
    }

    /**
     * Ensures nested generic PHPDoc value extraction returns the leaf value symbol.
     *
     * @return void
     */
    public function testRecursivePhpDocValueExtractionStrategySupportsNestedGenericArguments(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var Collection<T<V>> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Collection', 'TestCase\\Collection')->set('T', 'TestCase\\T')->set('V', 'TestCase\\V'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        sort($symbols);

        $this->assertSame(
            ['TestCase\\V'],
            $symbols,
        );
    }

    /**
     * Ensures key-value generic PHPDoc value extraction returns the value symbol.
     *
     * @return void
     */
    public function testRecursivePhpDocValueExtractionStrategyExtractsAllLeafGenericSymbols(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var Map<string, Mailer> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer')->set('Map', 'TestCase\\Map'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        sort($symbols);

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures array generic PHPDoc value extraction returns the value symbol.
     *
     * @return void
     */
    public function testPhpDocArrayGenericResolvesValueTypeForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array<Mailer> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures list generic PHPDoc value extraction returns the item symbol.
     *
     * @return void
     */
    public function testPhpDocListGenericResolvesValueTypeForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var list<Mailer> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures array key-value generic PHPDoc value extraction returns the value symbol.
     *
     * @return void
     */
    public function testPhpDocArrayKeyValueGenericResolvesValueTypeForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array<string, Mailer> $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures array shape PHPDoc value extraction returns every field symbol.
     *
     * @return void
     */
    public function testPhpDocArrayShapeResolvesAllFieldValueTypesForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{foo: Mailer, bar: Logger} $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer')->set('Logger', 'TestCase\\Service\\Logger'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            [
                'TestCase\\Service\\Mailer',
                'TestCase\\Service\\Logger',
            ],
            $symbols,
        );
    }

    /**
     * Ensures nested array shape PHPDoc value extraction returns nested field symbols.
     *
     * @return void
     */
    public function testPhpDocNestedArrayShapeResolvesNestedValueTypesForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{config: array{mailer: Mailer}} $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures numeric-key array shape PHPDoc value extraction returns every field symbol.
     *
     * @return void
     */
    public function testPhpDocArrayShapeWithNumericKeysResolvesAllValueTypesForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{0: Mailer, 1: Logger} $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer')->set('Logger', 'TestCase\\Service\\Logger'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            [
                'TestCase\\Service\\Mailer',
                'TestCase\\Service\\Logger',
            ],
            $symbols,
        );
    }

    /**
     * Ensures union fields inside array shapes return all possible value symbols.
     *
     * @return void
     */
    public function testPhpDocArrayShapeWithUnionFieldResolvesAllUnionValueTypesForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{service: Mailer|Logger} $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer')->set('Logger', 'TestCase\\Service\\Logger'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            [
                'TestCase\\Service\\Mailer',
                'TestCase\\Service\\Logger',
            ],
            $symbols,
        );
    }

    /**
     * Ensures generic fields inside array shapes return their inner value symbols.
     *
     * @return void
     */
    public function testPhpDocArrayShapeWithGenericFieldResolvesInnerGenericValueTypeForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{items: array<string, Mailer>} $data */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection()->set('Mailer', 'TestCase\\Service\\Mailer'),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            ['TestCase\\Service\\Mailer'],
            $symbols,
        );
    }

    /**
     * Ensures empty array shapes do not produce value symbols.
     *
     * @return void
     */
    public function testPhpDocEmptyArrayShapeResolvesNoValueTypeForUsage(): void
    {
        $factory = new PhpDocParserFactory();
        $resolver = new PhpDocTypeNodeResolver(new MemberGraphPhpSourceRegistryInstance());

        $tokens = new TokenIterator(
            $factory->createLexer()->tokenize('/** @var array{} $items */')
        );

        $phpDocNode = $factory->createParser()->parse($tokens);
        $varTag = $phpDocNode->getVarTagValues()[0];

        $symbols = $resolver->resolveForValueUsage(
            $varTag->type,
            'TestCase',
            new UsesByAliasCollection(),
            new PhpDocTemplateDefinitionCollection(),
            new TypeIndexContext(),
            PhpDocTagKind::VAR
        )->all();

        $this->assertSame(
            [],
            $symbols,
        );
    }

    /**
     * Ensures legacy fixture 61 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testLocalVarPhpDocGenericTypeResolvesInnerValueTypeForUsage(): void
    {
        $sources = [
            'TestCase61.php' => <<<'PHP'
<?php

namespace TestCase61;

use TestCase61\Collection;
use TestCase61\Service\Mailer;

class Runner
{
    public function run(): void
    {
        /** @var Collection<Mailer> $items */
        $items = getCollection();

        $items->send();
    }
}

namespace TestCase61;

class Collection
{
}

namespace TestCase61\Service;

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
        $foundCollection = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase61\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase61\Collection' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundCollection = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundCollection);


    }

    /**
     * Ensures legacy fixture 62 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testParamPhpDocGenericTypeResolvesInnerValueTypeForUsage(): void
    {
        $sources = [
            'TestCase62.php' => <<<'PHP'
<?php

namespace TestCase62;

use TestCase62\Map;
use TestCase62\Service\Mailer;

class Runner
{
    /**
     * @param Map<string, Mailer> $items
     */
    public function run($items): void
    {
        $items->send();
    }
}

namespace TestCase62;

class Map
{
}

namespace TestCase62\Service;

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
        $foundMap = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase62\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase62\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase62\Map' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMap = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertFalse($foundMap);


    }

    /**
     * Ensures legacy fixture 63 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testReturnPhpDocGenericTypeResolvesInnerUnionValueTypesForUsage(): void
    {
        $sources = [
            'TestCase63.php' => <<<'PHP'
<?php

namespace TestCase63;

use TestCase63\Collection;
use TestCase63\Service\Mailer;
use TestCase63\Service\Notifier;

class Runner
{
    /**
     * @return Collection<Mailer|Notifier>
     */
    public function make()
    {
        return getCollection();
    }

    public function run(): void
    {
        $items = $this->make();
        $items->send();
    }
}

namespace TestCase63;

class Collection
{
}

namespace TestCase63\Service;

class Mailer
{
    public function send(): void
    {
    }
}

namespace TestCase63\Service;

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
        $foundCollection = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if ('TestCase63\Runner::run' !== $usage->sourceSymbol) {
                    continue;
                }

                if ('TestCase63\Service\Mailer' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundMailer = true;
                }

                if ('TestCase63\Service\Notifier' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundNotifier = true;
                }

                if ('TestCase63\Collection' === $usage->target->owner && 'send' === $usage->target->name) {
                    $foundCollection = true;
                }
            }
        }

        $this->assertTrue($foundMailer);
        $this->assertTrue($foundNotifier);
        $this->assertFalse($foundCollection);


    }

    /**
     * Ensures legacy fixture 64 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testLocalVarPhpDocArrayGenericResolvesInnerValueTypeForUsage(): void
    {
        $sources = [
            'TestCase64.php' => <<<'PHP'
<?php

namespace TestCase64;

use TestCase64\Service\Mailer;

class Runner
{
    public function run(): void
    {
        /** @var array<Mailer> $items */
        $items = getItems();

        $items->send();
    }
}

namespace TestCase64\Service;

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
                if (
                    'TestCase64\Service\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $foundMailer = true;
                }
            }
        }

        $this->assertTrue($foundMailer);

    }

    /**
     * Ensures legacy fixture 145 keeps its member graph behavior stable.
     *
     * @return void
     */
    public function testGenericPhpDocRefinesArray(): void
    {
        $sources = [
            'TestCase145.php' => <<<'PHP'
<?php

namespace TestCase145;

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
        $items = (new Factory())->make();
        $items['a']->send();
    }
}
PHP,
        ];

        $memberDependencyGraph = $this->buildGraphFromSources($sources);

        $found = false;

        foreach ($memberDependencyGraph->usages->all() as $group) {
            foreach ($group as $usage) {
                if (
                    'TestCase145\\Mailer' === $usage->target->owner
                    && 'send' === $usage->target->name
                ) {
                    $found = true;
                }
            }
        }

        $this->assertTrue($found);
    }
}
