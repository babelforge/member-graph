# Testing And Debugging

Navigation: [Back to README](README.md) | [Previous: PHPDoc And Structured Types](06-phpdoc-and-structured-types.md) | [Next: Maintenance Guide](08-maintenance-guide.md)

Stability tests are the main reference for the `MemberGraph` component.

## Main Tests

Main tests:

```text
tests/Integration/MemberGraph/MemberGraphStabilityTest.php
tests/Integration/MemberGraph/MemberGraphStabilityTestSpecific.php
```

Generated test cases live in:

```text
tests/Integration/MemberGraph/TestFileWriters/MemberGraphStabilityWriter.php
```

## Test Pattern

The usual structure is:

```php
public function testSomething(): void
{
    try {
        $memberDependencyGraph = $this->getMemberDependencyGraphForFile(123);

        $this->assertMemberUsageExists($memberDependencyGraph, ...);
    } finally {
        $this->cleanRuntimeEnvironment();
    }
}
```

The test number maps to the generated case in `MemberGraphStabilityWriter`.

## When To Run Focused Tests

When only tests are added and production code is unchanged, it is often enough to run:

```bash
vendor/bin/phpunit tests/Integration/MemberGraph/MemberGraphStabilityTestSpecific.php
```

or a focused filter on the new group.

## When To Run Full Tests

Run the full `MemberGraphStabilityTest.php` when touching:

- central builder;
- projection;
- traversal;
- expression resolution;
- structured PHPDoc;
- template substitution;
- global indexes;
- owners or polymorphism.

Command:

```bash
vendor/bin/phpunit tests/Integration/MemberGraph/MemberGraphStabilityTest.php
```

## Useful Debug Targets

When debugging, identify:

- the expected member;
- the expected owner;
- the expected usage type;
- whether the information comes from a native type, flat PHPDoc, or structured PHPDoc;
- whether the case crosses inheritance, interface, trait, or polymorphism logic;
- whether the case depends on template substitution.

## Common Failure Families

### Missing Usage

Check:

- whether the receiver is resolved;
- whether the previous return type is available;
- whether the property or method exists in `availableMembers`;
- whether the structured type was flattened too early.

### Wrong Owner

Check:

- `self`, `static`, `parent`;
- inheritance;
- interface versus implementation;
- trait projection;
- imported aliases;
- class constant owner traversal.

### Parameter Usage Missing

Check:

- named argument;
- target method or function resolution;
- native parameter indexes;
- structured PHPDoc parameter indexes.

### Template Not Substituted

Check:

- visible template definition;
- argument matching;
- owner-template context;
- union/generic/shape/callable substitution strategy;
- merge of multiple substitutions.

Navigation: [Back to README](README.md) | [Previous: PHPDoc And Structured Types](06-phpdoc-and-structured-types.md) | [Next: Maintenance Guide](08-maintenance-guide.md)
