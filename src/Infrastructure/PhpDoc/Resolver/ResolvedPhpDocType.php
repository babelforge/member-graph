<?php

declare(strict_types=1);

namespace PhpNoobs\MemberGraph\Infrastructure\PhpDoc\Resolver;

use PhpNoobs\MemberGraph\Domain\Symbol\SymbolCollection;

/**
 * Represents one resolved PHPDoc type tree.
 */
final class ResolvedPhpDocType
{
    /**
     * @param SymbolCollection                $symbols            resolved symbols for the current node
     * @param int                             $kinds              resolved type kinds
     * @param ResolvedPhpDocTypeCollection    $genericArguments   resolved generic arguments
     * @param ShapeFieldCollection            $shapeFields        resolved array-shape fields
     * @param ResolvedPhpDocTemplateReference $templateReference  resolved template reference
     * @param ResolvedPhpDocTypeCollection    $intersectionTypes  resolved intersection members
     * @param ResolvedPhpDocTypeCollection    $callableParameters resolved callable parameter types
     * @param ResolvedPhpDocType|null         $callableReturnType resolved callable return type
     */
    private function __construct(
        public SymbolCollection $symbols,
        public int $kinds = ResolvedPhpDocTypeKind::REGULAR->value,
        public ResolvedPhpDocTypeCollection $genericArguments = new ResolvedPhpDocTypeCollection(),
        public ShapeFieldCollection $shapeFields = new ShapeFieldCollection(),
        public ResolvedPhpDocTemplateReference $templateReference = new ResolvedPhpDocTemplateReference(''),
        public ResolvedPhpDocTypeCollection $intersectionTypes = new ResolvedPhpDocTypeCollection(),
        public ResolvedPhpDocTypeCollection $callableParameters = new ResolvedPhpDocTypeCollection(),
        public ?self $callableReturnType = null,
    ) {
    }

    public function isUsable(): bool
    {
        return !$this->symbols->isEmpty()
            || !$this->genericArguments->isEmpty()
            || !$this->shapeFields->isEmpty()
            || '' !== $this->templateReference->name
            || !$this->intersectionTypes->isEmpty()
            || !$this->callableParameters->isEmpty()
            || $this->callableReturnType instanceof self
            || $this->isParenthesized();
    }

    /**
     * @param SymbolCollection                  $symbols            resolved symbols for the current node
     * @param ResolvedPhpDocTypeCollection      $genericArguments   resolved generic arguments
     * @param ShapeFieldCollection              $shapeFields        resolved array-shape fields
     * @param ResolvedPhpDocTemplateReference   $templateReference  resolved template reference
     * @param ResolvedPhpDocTypeCollection|null $intersectionTypes  resolved intersection members
     * @param ResolvedPhpDocTypeCollection|null $callableParameters resolved callable parameter types
     * @param ResolvedPhpDocType|null           $callableReturnType resolved callable return type
     */
    public static function new(
        SymbolCollection $symbols,
        ResolvedPhpDocTypeCollection $genericArguments,
        ShapeFieldCollection $shapeFields,
        ResolvedPhpDocTemplateReference $templateReference,
        ?ResolvedPhpDocTypeCollection $intersectionTypes = null,
        ?ResolvedPhpDocTypeCollection $callableParameters = null,
        ?self $callableReturnType = null,
    ): self {
        $intersectionTypes ??= new ResolvedPhpDocTypeCollection();
        $callableParameters ??= new ResolvedPhpDocTypeCollection();

        $kinds = ResolvedPhpDocTypeKind::REGULAR->value;

        if (!$genericArguments->isEmpty()) {
            $kinds = ResolvedPhpDocTypeKind::addFlag($kinds, ResolvedPhpDocTypeKind::WITH_GENERIC);
        }

        if (!$shapeFields->isEmpty()) {
            $kinds = ResolvedPhpDocTypeKind::addFlag($kinds, ResolvedPhpDocTypeKind::WITH_ARRAY_SHAPE);
        }

        if ($templateReference->isNotBlank()) {
            $kinds = ResolvedPhpDocTypeKind::addFlag($kinds, ResolvedPhpDocTypeKind::WITH_TEMPLATE);
        }

        if (!$intersectionTypes->isEmpty()) {
            $kinds = ResolvedPhpDocTypeKind::addFlag($kinds, ResolvedPhpDocTypeKind::WITH_INTERSECTION);
        }

        if (!$callableParameters->isEmpty() || $callableReturnType instanceof self) {
            $kinds = ResolvedPhpDocTypeKind::addFlag($kinds, ResolvedPhpDocTypeKind::WITH_CALLABLE_SIGNATURE);
        }

        return new self(
            symbols: $symbols,
            kinds: $kinds,
            genericArguments: $genericArguments,
            shapeFields: $shapeFields,
            templateReference: $templateReference,
            intersectionTypes: $intersectionTypes,
            callableParameters: $callableParameters,
            callableReturnType: $callableReturnType,
        );
    }

    /**
     * @param SymbolCollection                  $symbols            resolved symbols for the current node
     * @param int                               $kinds              resolved type kinds
     * @param ResolvedPhpDocTypeCollection      $genericArguments   resolved generic arguments
     * @param ShapeFieldCollection              $shapeFields        resolved array-shape fields
     * @param ResolvedPhpDocTemplateReference   $templateReference  resolved template reference
     * @param ResolvedPhpDocTypeCollection|null $intersectionTypes  resolved intersection members
     * @param ResolvedPhpDocTypeCollection|null $callableParameters resolved callable parameter types
     * @param ResolvedPhpDocType|null           $callableReturnType resolved callable return type
     */
    public static function fromParts(
        SymbolCollection $symbols,
        int $kinds,
        ResolvedPhpDocTypeCollection $genericArguments,
        ShapeFieldCollection $shapeFields,
        ResolvedPhpDocTemplateReference $templateReference,
        ?ResolvedPhpDocTypeCollection $intersectionTypes = null,
        ?ResolvedPhpDocTypeCollection $callableParameters = null,
        ?self $callableReturnType = null,
    ): self {
        return new self(
            symbols: $symbols,
            kinds: $kinds,
            genericArguments: $genericArguments,
            shapeFields: $shapeFields,
            templateReference: $templateReference,
            intersectionTypes: $intersectionTypes ?? new ResolvedPhpDocTypeCollection(),
            callableParameters: $callableParameters ?? new ResolvedPhpDocTypeCollection(),
            callableReturnType: $callableReturnType,
        );
    }

    public static function regular(SymbolCollection $symbols): self
    {
        return new self(
            symbols: $symbols,
        );
    }

    public static function newGeneric(SymbolCollection $symbols, ResolvedPhpDocTypeCollection $genericArguments): self
    {
        return new self(
            symbols: $symbols,
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_GENERIC,
            ),
            genericArguments: $genericArguments,
        );
    }

    public static function newShaped(SymbolCollection $symbols, ShapeFieldCollection $shapeFields): self
    {
        return new self(
            symbols: $symbols,
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_ARRAY_SHAPE,
            ),
            shapeFields: $shapeFields,
        );
    }

    public static function template(SymbolCollection $symbols, ResolvedPhpDocTemplateReference $templateReference): self
    {
        return new self(
            symbols: $symbols,
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_TEMPLATE,
            ),
            templateReference: $templateReference,
        );
    }

    public static function intersection(ResolvedPhpDocTypeCollection $intersectionTypes): self
    {
        return new self(
            symbols: new SymbolCollection(),
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_INTERSECTION,
            ),
            intersectionTypes: $intersectionTypes,
        );
    }

    public static function parenthesized(self $wrappedType): self
    {
        $genericArguments = new ResolvedPhpDocTypeCollection();
        $genericArguments->add($wrappedType);

        return new self(
            symbols: new SymbolCollection(),
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_PARENTHESIZED,
            ),
            genericArguments: $genericArguments,
        );
    }

    public static function callableSignature(
        ResolvedPhpDocTypeCollection $parameters,
        ?self $returnType = null,
    ): self {
        return new self(
            symbols: new SymbolCollection()->add('callable'),
            kinds: ResolvedPhpDocTypeKind::addFlag(
                ResolvedPhpDocTypeKind::REGULAR->value,
                ResolvedPhpDocTypeKind::WITH_CALLABLE_SIGNATURE,
            ),
            callableParameters: $parameters,
            callableReturnType: $returnType,
        );
    }

    /**
     * Returns whether the current node represents an array shape.
     */
    public function isShape(): bool
    {
        return ResolvedPhpDocTypeKind::hasFlag($this->kinds, ResolvedPhpDocTypeKind::WITH_ARRAY_SHAPE);
    }

    /**
     * Returns whether the current node represents an array shape.
     */
    public function isNonEmptyShape(): bool
    {
        return $this->isShape() && !$this->shapeFields->isEmpty();
    }

    /**
     * Returns whether the current node represents an array shape.
     */
    public function isEmptyShape(): bool
    {
        return $this->isShape() && $this->shapeFields->isEmpty();
    }

    /**
     * Returns whether the current node represents an intersection.
     */
    public function isIntersection(): bool
    {
        return ResolvedPhpDocTypeKind::hasFlag($this->kinds, ResolvedPhpDocTypeKind::WITH_INTERSECTION);
    }

    /**
     * Returns whether the current node represents a parenthesized type.
     */
    public function isParenthesized(): bool
    {
        return ResolvedPhpDocTypeKind::hasFlag($this->kinds, ResolvedPhpDocTypeKind::WITH_PARENTHESIZED);
    }

    /**
     * Tells whether this node behaves like a union container.
     */
    public function isUnionContainer(): bool
    {
        return !$this->genericArguments->isEmpty()
            && $this->symbols->isEmpty()
            && $this->templateReference->isEmpty()
            && $this->shapeFields->isEmpty();
    }

    /**
     * Returns whether the current node represents a callable signature.
     */
    public function isCallable(): bool
    {
        return ResolvedPhpDocTypeKind::hasFlag($this->kinds, ResolvedPhpDocTypeKind::WITH_CALLABLE_SIGNATURE);
    }

    public function isMeaningful(): bool
    {
        if (!$this->symbols->isEmpty()) {
            return true;
        }

        if ($this->isCallable()) {
            return true;
        }

        if ($this->hasTemplateReference()) {
            return true;
        }

        if ($this->isNonEmptyShape()) {
            return true;
        }

        return false;
    }

    public function isCompositeUnionLike(self $argumentType): bool
    {
        return $this->symbols->isEmpty()
            && !$this->genericArguments->isEmpty()
            && $argumentType->genericArguments->isEmpty();
    }

    /**
     * Returns the resolved field type for a literal shape key.
     */
    public function getShapeField(int|string $key): ?self
    {
        return $this->shapeFields->get($key);
    }

    public function hasTemplateReference(): bool
    {
        return '' !== $this->templateReference->name
            || ResolvedPhpDocTypeKind::hasFlag($this->kinds, ResolvedPhpDocTypeKind::WITH_TEMPLATE);
    }

    public function templateReferenceName(): string
    {
        return $this->templateReference->name;
    }

    public function hasGenericArguments(): bool
    {
        return !$this->genericArguments->isEmpty();
    }

    /**
     * Returns the wrapped type when current node is parenthesized.
     */
    public function getParenthesizedInnerType(): ?self
    {
        if (!$this->isParenthesized()) {
            return null;
        }

        $all = $this->genericArguments->all();

        return $all[0] ?? null;
    }

    /**
     * Flattens the current node and all nested members into one symbol collection.
     *
     * @param bool $excludeScalarBuiltSymbols whether to exclude scalar built-in symbols
     */
    public function flattenAllSymbols(bool $excludeScalarBuiltSymbols = false): SymbolCollection
    {
        $flattened = new SymbolCollection();

        foreach ($this->symbols as $symbol) {
            if ($excludeScalarBuiltSymbols && self::isBuiltinLeafSymbol($symbol)) {
                continue;
            }

            $flattened->add($symbol);
        }

        foreach ($this->genericArguments as $genericArgument) {
            $flattened->addMany($genericArgument->flattenAllSymbols($excludeScalarBuiltSymbols));
        }

        foreach ($this->shapeFields as $shapeField) {
            $flattened->addMany($shapeField->flattenAllSymbols($excludeScalarBuiltSymbols));
        }

        foreach ($this->intersectionTypes as $intersectionType) {
            $flattened->addMany($intersectionType->flattenAllSymbols($excludeScalarBuiltSymbols));
        }

        foreach ($this->callableParameters as $callableParameter) {
            $flattened->addMany($callableParameter->flattenAllSymbols($excludeScalarBuiltSymbols));
        }

        if ($this->callableReturnType instanceof self) {
            $flattened->addMany($this->callableReturnType->flattenAllSymbols($excludeScalarBuiltSymbols));
        }

        return $flattened;
    }

    public static function isBuiltinLeafSymbol(string $symbol): bool
    {
        return in_array(strtolower($symbol), [
            'string',
            'int',
            'float',
            'bool',
            'true',
            'false',
            'null',
            'scalar',
            'numeric',
            'resource',
            'callable',
            'object',
            'mixed',
            'void',
            'never',
        ], true);
    }
}
