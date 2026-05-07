# PHPDoc And Structured Types

Navigation: [Back to README](README.md) | [Previous: Type And Expression Resolution](05-type-and-expression-resolution.md) | [Next: Testing And Debugging](07-testing-and-debugging.md)

PHPDoc support is central in `MemberGraph`, but it must remain in service of the dependency graph.

## Infrastructure Layout

PHPDoc code lives in:

```text
Infrastructure/PhpDoc/
  Extractor/
  Inheritance/
  Parser/
  Renderer/
  Resolver/
  Template/
  Traversal/
  ValueExtraction/
```

Each subdirectory maps to a readable responsibility.

## Extraction

`Extractor/` reads productive PHPDoc tags:

- `@var`;
- `@param`;
- `@return`;
- `@template`.

Extractors should return DTOs or dedicated objects when data becomes structured. Associative arrays should be avoided for public or semi-public contracts.

## Structured Resolution

`Resolver/` contains the structured PHPDoc model:

- `ResolvedPhpDocType`;
- `ResolvedPhpDocTypeCollection`;
- `ResolvedPhpDocTypeKind`;
- `ResolvedPhpDocCallableSignature`;
- `ResolvedPhpDocCallableParameter`;
- `ShapeFieldCollection`;
- `ResolvedPhpDocTemplateReference`.

This model preserves information that would be lost in a plain `SymbolCollection`.

## Value Extraction

`ValueExtraction/` decides what should be considered the useful value of a PHPDoc type.

Examples:

- `array<string, Mailer>` extracts `Mailer`;
- `list<Mailer>` extracts `Mailer`;
- `Collection<Mailer>` extracts `Mailer`;
- `array{service: Mailer}` can expose `Mailer` through its `service` field.

Scalar builtins must not become invented dependency owners.

## Templates

`Template/` contains PHPDoc template definitions and contexts.

Templates are preserved and substituted in productive paths:

- functions;
- methods;
- static calls;
- constructors;
- properties;
- callables;
- array shapes.

Bounds such as `@template T of Foo` are preserved, inherited, and substituted in validated cases.

Important: a bound is not a concrete owner. The graph must keep the inferred real type when it exists.

## Inheritance

`Inheritance/` handles `@inheritDoc`, `@inheritdoc`, and `{@inheritDoc}`.

The effective-doc mechanism uses node attributes, without visible pretty-print AST mutation.

Possible sources include:

- parent class;
- implemented interfaces;
- extended interfaces;
- direct traits;
- nested traits.

Parent-class priority remains higher than interface priority when both provide a productive contract.

## Traversal

`Traversal/` contains effective PHPDoc enrichment.

This step must remain distinct from simple index collection, because later builders consume enriched PHPDoc.

## Rendering

`Renderer/` converts PHPDoc structures back to text when necessary.

Rendering must not become the source of truth. The structured model is the source of truth.

## Validation

PHPDoc validation lives in:

```text
Application/Validator/PhpDoc/
```

This reflects an important decision: validation is an application responsibility, not a pure domain-model responsibility.

The graph should remain productive even when validation rules are not exhaustive.

Navigation: [Back to README](README.md) | [Previous: Type And Expression Resolution](05-type-and-expression-resolution.md) | [Next: Testing And Debugging](07-testing-and-debugging.md)
