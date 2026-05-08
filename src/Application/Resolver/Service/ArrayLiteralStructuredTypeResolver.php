<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Application\Resolver\Service;

use PhpNoobs\MemberGraph\Application\Resolver\Contracts\ExpressionTypeResolverInterface;
use PhpNoobs\MemberGraph\Application\Resolver\ExpressionResolutionContext;
use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocType;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ResolvedPhpDocTypeCollection;
use PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver\ShapeFieldCollection;
use PhpParser\Node\Expr\Array_;

/**
 * Resolves literal array expressions to structured PHPDoc types.
 */
final readonly class ArrayLiteralStructuredTypeResolver
{
    /**
     * Constructor.
     *
     * @param LiteralValueResolver $literalValueResolver The literal value resolver.
     */
    public function __construct(
        private LiteralValueResolver $literalValueResolver,
    ) {
    }

    /**
     * Resolves one array expression to a structured PHPDoc type.
     *
     * @param Array_ $expression The array expression.
     * @param ExpressionResolutionContext $context The expression resolution context.
     * @param ExpressionTypeResolverInterface $expressionTypeResolver The recursive expression resolver.
     *
     * @return ResolvedPhpDocType
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
     *
     * @return ResolvedPhpDocType
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
     * @param ResolvedPhpDocTypeCollection $valueTypes The inferred list value types.
     *
     * @return ResolvedPhpDocType
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
