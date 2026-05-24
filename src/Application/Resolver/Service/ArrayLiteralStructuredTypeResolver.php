<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Application\Resolver\Service;

use BabelForge\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use BabelForge\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use BabelForge\MemberGraph\Domain\Symbol\SymbolCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\ShapeFieldCollection;
use PhpParser\Node\Expr\Array_;

/**
 * Resolves literal array expressions to structured PHPDoc types.
 */
final readonly class ArrayLiteralStructuredTypeResolver
{
    /**
     * Constructor.
     *
     * @param LiteralValueResolver $literalValueResolver the literal value resolver
     */
    public function __construct(
        private LiteralValueResolver $literalValueResolver,
    ) {
    }

    /**
     * Resolves one array expression to a structured PHPDoc type.
     *
     * @param Array_                          $expression             the array expression
     * @param ExpressionResolutionContext     $context                the expression resolution context
     * @param ExpressionTypeResolverInterface $expressionTypeResolver the recursive expression resolver
     */
    public function resolve(
        Array_ $expression,
        ExpressionResolutionContext $context,
        ExpressionTypeResolverInterface $expressionTypeResolver,
    ): ResolvedPhpDocType {
        $shapeFields = new ShapeFieldCollection();
        $listValueTypes = new ResolvedPhpDocTypeCollection();
        $hasImplicitKeys = false;
        $hasExplicitKeys = false;

        foreach ($expression->items as $item) {
            if (null === $item) {
                return $this->buildRegularArrayStructuredType();
            }

            $key = $this->literalValueResolver->resolveLiteralArrayKeyForArrayShapeAccess(
                $item->key,
                $context->currentClass,
            );
            $valueType = $expressionTypeResolver->resolveStructuredPhpDocType(
                $item->value,
                $context->variableTypes,
                $context->currentClass,
                $context->templateDefinitions,
                $context->usesByAlias,
            );

            if (!$valueType instanceof ResolvedPhpDocType) {
                return $this->buildRegularArrayStructuredType();
            }

            if (null === $key) {
                $hasImplicitKeys = true;
                $listValueTypes->add($valueType);

                continue;
            }

            $hasExplicitKeys = true;

            $shapeFields->set($key, $valueType);
        }

        if ($hasImplicitKeys && !$hasExplicitKeys) {
            return $this->buildListStructuredType($listValueTypes);
        }

        if ($hasImplicitKeys) {
            return $this->buildRegularArrayStructuredType();
        }

        return ResolvedPhpDocType::newShaped(
            symbols: new SymbolCollection(),
            shapeFields: $shapeFields,
        );
    }

    /**
     * Builds one regular array structured type.
     */
    private function buildRegularArrayStructuredType(): ResolvedPhpDocType
    {
        $symbols = new SymbolCollection();
        $symbols->add('array');

        return ResolvedPhpDocType::regular($symbols);
    }

    /**
     * Builds one list structured type from literal array value types.
     *
     * @param ResolvedPhpDocTypeCollection $valueTypes the inferred list value types
     */
    private function buildListStructuredType(ResolvedPhpDocTypeCollection $valueTypes): ResolvedPhpDocType
    {
        $symbols = new SymbolCollection();
        $symbols->add('list');

        if ($valueTypes->isEmpty()) {
            return ResolvedPhpDocType::newGeneric(
                $symbols,
                new ResolvedPhpDocTypeCollection(),
            );
        }

        if (1 === $valueTypes->count()) {
            $genericArguments = new ResolvedPhpDocTypeCollection();
            $firstValueType = $valueTypes->getItemByIndex(0);

            if (!$firstValueType instanceof ResolvedPhpDocType) {
                return $this->buildRegularArrayStructuredType();
            }

            $genericArguments->add($firstValueType);

            return ResolvedPhpDocType::newGeneric($symbols, $genericArguments);
        }

        $unionType = ResolvedPhpDocType::newGeneric(
            new SymbolCollection(),
            $valueTypes,
        );
        $genericArguments = new ResolvedPhpDocTypeCollection();
        $genericArguments->add($unionType);

        return ResolvedPhpDocType::newGeneric($symbols, $genericArguments);
    }
}
