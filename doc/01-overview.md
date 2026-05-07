# Overview

Navigation: [Back to README](README.md) | [Next: Public Usage](02-public-usage.md)

The `MemberGraph` component answers one precise question: which PHP members are declared, which members are used, and which owners do those usages target?

It allows transformation rules to know, for example:

- which method is called by `$service->send()`;
- which property is read by `$this->mailer`;
- which constant is targeted by `self::NAME`;
- which parameter is targeted by a named argument;
- which members are available on a class after inheritance, interfaces, and traits are applied.

## Main Output

The main output is `MemberDependencyGraph`.

It contains:

- `declarations`: members declared in the analyzed code;
- `usages`: method, function, property, and constant usages;
- `parameterUsages`: parameter usages, especially through named arguments;
- `availableMembers`: members available after inheritance, trait, and interface projection;
- `knownOwners`: known classes, interfaces, traits, and enums;
- `interfaceImplementationsIndex`: polymorphic index for interfaces and abstract classes;
- `dependencyGraphIssues`: optional PHPDoc diagnostic collection.

## Why It Exists

A class-level dependency graph can say that class `A` depends on class `B`. The member graph goes further:

```php
$mailer = $container->mailer();
$mailer->send();
```

The component can collect:

- the declaration of `Container::mailer()`;
- the return type of `mailer()`;
- the usage of `Mailer::send()`;
- the chain that connects the call to its real owner.

## Productive Scope

The component is built for code that is expected to be reasonable and compatible with PHPStan-like static analysis.

Its primary goal is not to validate invalid or inconsistent PHP code. Heavy validation belongs to rules or diagnostic layers.

## Supported Concepts

The graph currently covers:

- classes, interfaces, traits, and enums;
- methods, functions, properties, constants, and enum cases;
- class inheritance;
- interface implementation and extension;
- traits, aliases, precedence, and visibility adaptations;
- method calls, static calls, and function calls;
- instance and static properties;
- class, interface, trait constants, and enum cases;
- native types, unions, and nullable types;
- PHPDoc `@var`, `@param`, `@return`, `@template`, and `@inheritDoc`;
- generics, array shapes, callables, intersections, and PHPDoc unions;
- template substitution on functions, methods, static calls, constructors, and properties;
- nullsafe chains;
- named arguments.

## Non-Goals

The component should not become:

- a complete PHP validator;
- a PHPStan clone;
- a business rules layer;
- a system that invents dependencies from non-concrete PHPDoc constraints.

Important example: `@template T of InterfaceName` preserves a constraint, but it must not replace the inferred concrete type as the dependency owner.

Navigation: [Back to README](README.md) | [Next: Public Usage](02-public-usage.md)
