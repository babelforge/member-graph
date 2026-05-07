# Domain Model

Navigation: [Back to README](README.md) | [Previous: Build Pipeline](03-build-pipeline.md) | [Next: Type And Expression Resolution](05-type-and-expression-resolution.md)

The `Domain/` directory contains model objects and indexes. These classes should stay as close as possible to graph concepts.

## Graph

`Domain/Graph/` contains member identity and the final graph.

Main concepts:

- `MemberDependencyGraph`;
- `MemberId`;
- `MemberType`;
- `MemberOriginType`.

`MemberId` is the logical identity of a member:

- owner;
- type;
- name.

The `MemberId` hash is used as a stable key in collections.

## Declarations

`Domain/Declaration/` contains collected declarations.

A declaration says: "this member exists here".

It does not necessarily mean that the member is available on all descendants. That projection is handled later by `AvailableMemberProjector`.

## Usage

`Domain/Usage/` contains member usages.

A usage says: "this piece of code uses this target member".

The graph also keeps the source symbol when useful, especially for trait and projection scenarios.

## Parameter

`Domain/Parameter/` contains parameter identities and usages.

This subdomain is separated from member usages because a parameter is not a PHP member in the same sense as a method or property.

## Availability

`Domain/Availability/` represents members available after projection.

`AvailableMember` carries:

- the exposed member;
- its origin;
- its declaration sources;
- optional adaptations.

Projection is required because a call like `$child->send()` may target a member declared in:

- the class itself;
- a parent;
- an interface;
- a trait;
- a trait alias.

## Owner

`Domain/Owner/` represents known owners.

Supported owners:

- classes;
- interfaces;
- traits;
- enums.

`KnownOwnerCollection` preserves structural relationships:

- parent class;
- implemented interfaces;
- extended interfaces;
- used traits;
- trait aliases;
- trait precedence;
- trait visibility adaptations.

## Type

`Domain/Type/` contains type objects used by the graph.

Examples:

- `FunctionLikeReturnType`;
- `FunctionParameterType`;
- `MethodParameterType`;
- `VariableTypeInfo`;
- `VariableTypeSource`;
- `TypeIndexContext`.

`VariableTypeInfo` is important: it carries both the flat type (`SymbolCollection`) and the optional structured PHPDoc type.

## Symbol

`Domain/Symbol/` contains `SymbolCollection`.

`SymbolCollection` represents flat types useful to the dependency graph. It does not replace `ResolvedPhpDocType`, which preserves the complete PHPDoc structure.

## Index

`Domain/Index/` is split by indexed target:

```text
Index/
  ClassLike/
  Constant/
  Function/
  Method/
  Polymorphism/
  Property/
  Template/
```

Practical rule:

- if the class is queryable storage keyed by something, it probably belongs in `Domain/Index/`;
- if the class builds this index from PHPParser, it probably belongs in `Infrastructure/PhpParser/Indexing/`;
- if it enriches the build context, it probably belongs in `Application/Enrich/` or `Application/Build/`.

Navigation: [Back to README](README.md) | [Previous: Build Pipeline](03-build-pipeline.md) | [Next: Type And Expression Resolution](05-type-and-expression-resolution.md)
