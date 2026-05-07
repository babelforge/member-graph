# Type And Expression Resolution

Navigation: [Back to README](README.md) | [Previous: Domain Model](04-domain-model.md) | [Next: PHPDoc And Structured Types](06-phpdoc-and-structured-types.md)

Expression resolution transforms a PHPParser node into information that can be used by the graph.

It can produce:

- a flat `SymbolCollection`;
- a structured `ResolvedPhpDocType`;
- member or parameter usages through collectors.

## Facade

The main facade is `ExpressionTypeResolver`.

Path:

```text
Application/Resolver/ExpressionTypeResolver.php
```

It exposes only:

- `resolve()`;
- `resolveStructuredPhpDocType()`;
- `extractStructuredSymbols()`.

The facade must not become a large PHP language class again. Specialized strategies should remain in sub-services.

## Resolver Graph

`ExpressionResolverGraphFactory` builds the resolution graph.

It wires:

- expression resolvers;
- common services;
- structured PHPDoc services;
- template substitution services.

This factory prevents `ExpressionTypeResolver` from carrying all internal wiring.

## Expression Strategies

`Application/Resolver/Expression/` contains one strategy per major expression family.

Examples:

- `VariableExpressionResolver`;
- `NewExpressionResolver`;
- `MethodCallExpressionResolver`;
- `StaticCallExpressionResolver`;
- `PropertyFetchExpressionResolver`;
- `StaticPropertyFetchExpressionResolver`;
- `ClassConstFetchExpressionResolver`;
- `ConstFetchExpressionResolver`;
- `ArrayExpressionResolver`;
- `ArrayDimFetchExpressionResolver`;
- `FunctionCallExpressionResolver`.

Rule: one class should represent an identifiable resolution strategy, not a tiny one-line method without readability value.

## Service Resolvers

`Application/Resolver/Service/` contains services shared by several strategies.

Examples:

- owner resolution;
- structured return selection;
- array-shape access;
- property resolution;
- `self`, `static`, `parent` normalization;
- generic constructor inference;
- callable invocation;
- native versus structured resolution.

These services prevent expression strategies from talking directly to each other.

## Template Substitution

`Application/Resolver/Service/TemplateSubstitution/` contains template substitution strategies.

`TemplateSubstitutionCollector` remains the subsystem facade.

Covered cases include:

- direct template `T`;
- union `T|false`;
- generic `Box<T>`;
- array shape `array{service: T}`;
- callable `callable(T): T`;
- collection shape;
- merge of multiple substitutions.

## Flat vs Structured Types

Two representations intentionally coexist.

`SymbolCollection`:

- flat representation;
- useful for dependency owners;
- suited to final graph usages.

`ResolvedPhpDocType`:

- structured representation;
- preserves generics, shapes, callables, unions, intersections, and templates;
- used to resolve complex chains before flattening.

Do not flatten too early. A flattened shape or generic loses information required by the next resolution step.

## Native vs PHPDoc Priority

Current rule:

- the native type keeps priority when PHPDoc does not provide more useful precision;
- structured PHPDoc keeps priority when it carries richer information, such as a shape, generic, callable, or useful intersection.

This rule is sensitive. Any modification must be covered by a focused test.

## Assignment Resolution

There is currently no registry-backed `AssignExpressionResolver`.

Assignments are collected during traversal by `LocalVariableTypeCollector` and `VariableTypePropagationResolver`.

This avoids mixing pure expression resolution with local state mutation.

Navigation: [Back to README](README.md) | [Previous: Domain Model](04-domain-model.md) | [Next: PHPDoc And Structured Types](06-phpdoc-and-structured-types.md)
