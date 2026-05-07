# ExpressionTypeResolver Migration Plan

## Goal

Turn `ExpressionTypeResolver` into a small orchestration facade while preserving the current public API and behavior.

This migration started while the facade was still named `SimpleExpressionTypeResolver`.
Historical phase notes keep that original name when they describe work completed before the rename.

The target architecture is:

- `ExpressionTypeResolver` as the public facade.
- `ExpressionTypeResolverRegistry` as the resolver dispatcher.
- One expression resolver per PHP expression family.
- Shared domain services for logic used by several resolvers.

## Ground Rules

- One migration phase must move one clear responsibility.
- Refactoring and bug fixing must not be mixed in the same phase.
- If a bug is discovered during migration, stop the refactor, add a focused test, fix the bug, then resume.
- `ExpressionTypeResolver` public methods must stay compatible with current callers until the migration is complete.
- Avoid generic helper buckets such as `ExpressionResolverHelper`.
- Avoid traits for shared behavior.
- Shared logic must move into named domain services.
- When production code changes, run the full `MemberGraphStabilityTest.php` suite.
- When only tests, fixtures, or docs change, targeted filters are enough.

## Target Building Blocks

### Context

- `ExpressionResolutionContext`

Expected responsibility:

- carry local variable types
- carry current class
- carry active template definitions
- carry current use-import aliases

### Registry

- `ExpressionResolverInterface`
- `ExpressionTypeResolverRegistry`

Expected responsibility:

- find the first resolver supporting a node
- delegate resolution to that resolver
- keep resolver ordering explicit and deterministic

### Shared Services

Initial candidates:

- `ClassNameResolver`
- `StaticOwnerResolver`
- `DeclaringMethodResolver`

Later candidates:

- `FunctionLikeCallResolver`
- `StructuredPhpDocTypeResolver`
- `TemplateSubstitutionResolver`
- `NativeTypeResolver`
- `LiteralValueResolver`
- `ArrayShapeAccessResolver`

Extraction rule:

- keep logic private when it is used by one resolver only
- consider extraction when it is used by at least two resolvers
- extract when it is used by three or more resolvers, or when it represents a clear domain concept

## Phases

### Phase 0 - Baseline

Status: completed

Keep `SimpleExpressionTypeResolver` as the only public entry point.

No behavior change.

Validation:

- Current focused tests remain green.
- Latest full suite remains the reference baseline until production code changes.

### Phase 1 - Context and Common Owner Services

Status: completed

Create:

- `ExpressionResolutionContext`
- `ClassNameResolver`
- `StaticOwnerResolver`
- `DeclaringMethodResolver`

Then replace equivalent private logic inside `SimpleExpressionTypeResolver` where the replacement is straightforward.

Do not extract expression resolvers yet.

Validation:

- `php -l` on changed production files
- focused owner/static/class-name filters when relevant
- full `MemberGraphStabilityTest.php`

Result:

- Added `ExpressionResolutionContext`.
- Added `ClassNameResolver`.
- Added `StaticOwnerResolver`.
- Added `DeclaringMethodResolver`.
- Replaced equivalent private logic inside `SimpleExpressionTypeResolver` with calls to the new services.
- Removed the now-unused namespace extraction helper from `SimpleExpressionTypeResolver`.
- `php -l` passed for all changed production files.
- Focused V20.33-V20.37 owner/static/generic filter passed with 25 tests and 69 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 2 - Minimal Registry

Status: completed

Create:

- `ExpressionResolverInterface`
- `ExpressionTypeResolverRegistry`

The registry may initially dispatch to only one extracted resolver.

Validation:

- `php -l` on changed production files
- full `MemberGraphStabilityTest.php`

Result:

- Added `ExpressionResolverInterface`.
- Added `ExpressionTypeResolverRegistry`.
- Wired an empty registry into `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` now consults the registry before falling back to its legacy implementation.
- No expression strategy was extracted yet.
- `php -l` passed for all changed production files.
- Focused cross-file static/generic filter passed with 10 tests and 25 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 3 - VariableExpressionResolver

Status: completed

Extract:

- `VariableExpressionResolver`

Responsibilities:

- `$this`
- local variables
- `VariableTypeInfo`
- empty fallback for unknown variables

Validation:

- focused variable/local-flow filters when relevant
- full `MemberGraphStabilityTest.php`

Result:

- Added `VariableExpressionResolver`.
- Registered `VariableExpressionResolver` in `ExpressionTypeResolverRegistry`.
- Moved `$this`, local variable symbol resolution, and variable structured PHPDoc lookup out of `SimpleExpressionTypeResolver`.
- Removed the now-unused variable structured PHPDoc helper from `SimpleExpressionTypeResolver`.
- `php -l` passed for all changed production files.
- Focused generic/local-flow filter passed with 5 tests and 10 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 4 - NewExpressionResolver

Status: completed

Extract:

- `NewExpressionResolver`

Responsibilities:

- `new ClassName`
- constructor-driven generic inference
- class template substitution
- `self`, `static`, and `parent` normalization in new-expression structured types
- constructor PHPDoc handling

Validation:

- V20.37 focused filter
- generic/new focused filters when relevant
- full `MemberGraphStabilityTest.php`

Result:

- Added `NewExpressionResolver`.
- Added `TemplateSubstitutionCollector`.
- Added `SpecialClassReferenceNormalizer`.
- Registered `NewExpressionResolver` in `ExpressionTypeResolverRegistry`.
- Moved direct new-expression symbol resolution out of `SimpleExpressionTypeResolver`.
- Moved structured new-expression generic inference out of `SimpleExpressionTypeResolver`.
- Removed the now-unused new-expression structured helper from `SimpleExpressionTypeResolver`.
- `php -l` passed for all changed production files.
- V20.37 new/generic focused filter passed with 5 tests and 10 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 5 - StaticCallExpressionResolver

Status: completed

Extract:

- `StaticCallExpressionResolver`

Responsibilities:

- `Factory::method`
- `self::`, `static::`, and `parent::`
- effective owner resolution
- declaring owner resolution
- static method template substitution
- static `@inheritDoc` behavior already covered by tests

Validation:

- V20.33 focused filter
- V20.34 focused filter
- V20.35 focused filter
- V20.36 focused filter
- full `MemberGraphStabilityTest.php`

Checkpoint:

- Stop after this phase and review the architecture before extracting instance method calls.

Result:

- Added `StaticCallExpressionResolver`.
- Added `StaticCallExpressionResolverDelegateInterface` as a temporary bridge while the shared function-like call engine still lives in `SimpleExpressionTypeResolver`.
- Registered `StaticCallExpressionResolver` in `ExpressionTypeResolverRegistry`.
- Moved static-call dispatch out of the general `resolve()` and `resolveStructuredPhpDocType()` branches in `SimpleExpressionTypeResolver`.
- Kept effective owner resolution, declaring owner resolution, template substitution, and static `@inheritDoc` handling behind the existing function-like call internals for this phase.
- `php -l` passed for all changed production files.
- Focused static/template/inherited filter passed with 17 tests and 51 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 6 - MethodCallExpressionResolver

Status: completed

Extract:

- `MethodCallExpressionResolver`

Likely shared service:

- `FunctionLikeCallResolver`

Responsibilities:

- instance method calls
- nullsafe method calls if grouped here
- receiver owner resolution
- declaring owner resolution
- method template substitution
- chained calls

Validation:

- focused method/template/nullsafe/chained-call filters
- full `MemberGraphStabilityTest.php`

Result:

- Added `MethodCallExpressionResolver`.
- Added `MethodCallExpressionResolverDelegateInterface` as a temporary bridge while the shared function-like call engine still lives in `SimpleExpressionTypeResolver`.
- Registered `MethodCallExpressionResolver` in `ExpressionTypeResolverRegistry`.
- Moved instance and nullsafe method-call dispatch out of the general `resolve()` and `resolveStructuredPhpDocType()` branches in `SimpleExpressionTypeResolver`.
- Kept receiver owner resolution, declaring owner resolution, template substitution, chained-call handling, and nullsafe behavior behind the existing function-like call internals for this phase.
- `php -l` passed for all changed production files.
- Focused method/template/nullsafe/chained-call filter passed with 15 tests and 33 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 7 - Property Resolvers

Status: completed

Extract:

- `PropertyFetchExpressionResolver`
- `StaticPropertyFetchExpressionResolver`

Responsibilities:

- native property types
- structured PHPDoc property types
- promoted properties
- static properties
- property-backed propagation

Validation:

- focused promoted/static/nullsafe/property filters
- full `MemberGraphStabilityTest.php`

Result:

- Added `PropertyFetchExpressionResolver`.
- Added `PropertyFetchExpressionResolverDelegateInterface` as a temporary bridge while property internals still live in `SimpleExpressionTypeResolver`.
- Added `StaticPropertyFetchExpressionResolver`.
- Added `StaticPropertyFetchExpressionResolverDelegateInterface` as a temporary bridge while static-property internals still live in `SimpleExpressionTypeResolver`.
- Registered both property resolvers in `ExpressionTypeResolverRegistry`.
- Moved instance, nullsafe, and static property-fetch dispatch out of the general `resolve()` and `resolveStructuredPhpDocType()` branches in `SimpleExpressionTypeResolver`.
- Kept native property types, structured PHPDoc property types, promoted-property behavior, static-property owner fallback, and property-backed propagation behind the existing internals for this phase.
- `php -l` passed for all changed production files.
- Focused property/promoted/static/nullsafe filter passed with 29 tests and 62 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 8 - Constants

Status: completed

Extract:

- `ClassConstFetchExpressionResolver`
- `ConstFetchExpressionResolver`

Responsibilities:

- class constant owner resolution
- `self::`, `static::`, and `parent::` constants
- interface, trait, and enum constants
- scalar class-constant values used as array-shape keys

Validation:

- focused class-constant/enum/array-shape-key filters
- full `MemberGraphStabilityTest.php`

Result:

- Added `ClassConstFetchExpressionResolver`.
- Added `ClassConstFetchExpressionResolverDelegateInterface` as a temporary bridge while class-constant owner internals still live in `SimpleExpressionTypeResolver`.
- Added `ConstFetchExpressionResolver`.
- Registered both constant resolvers in `ExpressionTypeResolverRegistry`.
- Moved class-constant fetch symbol dispatch out of the general `resolve()` branch in `SimpleExpressionTypeResolver`.
- Moved `true`, `false`, and `null` constant structured-type dispatch out of the general `resolveStructuredPhpDocType()` branch in `SimpleExpressionTypeResolver`.
- Kept `self::`, `static::`, `parent::`, interface, trait, enum, and scalar class-constant array-shape key internals behind the existing constant helpers for this phase.
- `php -l` passed for all changed production files.
- Focused class-constant/enum/array-shape-key filter passed with 28 tests and 57 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 9 - Array Resolvers

Status: completed

Extract:

- `ArrayExpressionResolver`
- `ArrayDimFetchExpressionResolver`

Responsibilities:

- array/list/iterable construction
- array shapes
- literal-key access
- class-constant-key access
- conservative fallback for dynamic keys

Validation:

- focused array-shape/dynamic-key/class-constant-key filters
- full `MemberGraphStabilityTest.php`

Result:

- Added `ArrayExpressionResolver`.
- Added `ArrayExpressionResolverDelegateInterface` as a temporary bridge while array construction internals still live in `SimpleExpressionTypeResolver`.
- Added `ArrayDimFetchExpressionResolver`.
- Added `ArrayDimFetchExpressionResolverDelegateInterface` as a temporary bridge while array access internals still live in `SimpleExpressionTypeResolver`.
- Registered both array resolvers in `ExpressionTypeResolverRegistry`.
- Moved array structured-type dispatch out of the general `resolveStructuredPhpDocType()` branch in `SimpleExpressionTypeResolver`.
- Moved array-dimension symbol and structured-type dispatch out of the general `resolve()` and `resolveStructuredPhpDocType()` branches in `SimpleExpressionTypeResolver`.
- Kept array/list/shape construction, literal-key access, class-constant-key access, union access, and conservative dynamic-key fallback behind the existing array helpers for this phase.
- `php -l` passed for all changed production files.
- Focused array-shape/dynamic-key/class-constant-key filter passed with 30 tests and 68 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 10 - Assignments and Local Propagation

Status: completed

Extract:

- `AssignExpressionResolver`
- possibly `VariableTypePropagationResolver`

Responsibilities:

- simple assignment
- assignment from local PHPDoc
- propagation of `VariableTypeInfo`
- interaction with structured returns

Validation:

- focused assignment/local-flow filters when relevant
- full `MemberGraphStabilityTest.php`

Result:

- Added `VariableTypePropagationResolver`.
- Did not add a registry-backed `AssignExpressionResolver`, because assignment collection is currently handled by `MemberGraphBuilderVisitor`, not by `SimpleExpressionTypeResolver::resolve()`.
- Moved assignment-level type filtering, structured type selection, assignment symbol extraction, and assignment reset decision into `VariableTypePropagationResolver`.
- `MemberGraphBuilderVisitor` now delegates assignment propagation decisions to `VariableTypePropagationResolver`.
- Preserved the existing behavior for local PHPDoc assignment, structured return propagation, callable assignments, and stale assignment reset.
- `php -l` passed for all changed production files.
- Focused assignment/local-flow filter passed with 19 tests and 42 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 11 - PHPDoc and Template Services

Status: in progress

Extract only after the expression resolvers reveal the stable service boundaries.

Candidates:

- `StructuredPhpDocTypeResolver`
- `TemplateSubstitutionResolver`
- `FunctionLikeCallResolver`
- `NativeTypeResolver`
- `LiteralValueResolver`
- `ArrayShapeAccessResolver`

Validation:

- focused PHPDoc/template filters
- full `MemberGraphStabilityTest.php`

Result so far:

- Added `NativeTypeResolver`.
- Moved native PHP type to structured PHPDoc type resolution behind `NativeTypeResolver`.
- Moved native-vs-structured precision comparison rules behind `NativeTypeResolver`.
- Kept `SimpleExpressionTypeResolver::resolveNativeReturnTypeToStructuredPhpDocType()` as a small compatibility wrapper for now.
- Added `ArrayShapeAccessResolver`.
- Added `LiteralArrayKeyResolverInterface` as a narrow contract for array-shape literal-key resolution.
- Moved structured array/list/iterable/shape/union access policy behind `ArrayShapeAccessResolver`.
- Added `ClassConstantOwnerResolver`.
- Moved class-constant owner traversal through classes, interfaces, extended interfaces, and traits behind `ClassConstantOwnerResolver`.
- Added `LiteralValueResolver`.
- Moved literal string/int/class-constant key resolution behind `LiteralValueResolver`.
- `ArrayShapeAccessResolver` now receives `LiteralValueResolver` directly, and the temporary `SimpleExpressionTypeResolver` literal-key bridge was removed.
- Added `ArrayLiteralStructuredTypeResolver`.
- Moved literal array expression structured type construction behind `ArrayLiteralStructuredTypeResolver`.
- Removed the regular-array and list structured-type builder helpers from `SimpleExpressionTypeResolver`.
- Added `ClosureExpressionStructuredTypeResolver`.
- Moved closure/arrow-function callable structured type resolution, closure-local variable inference, closure-local simple PHPDoc parsing, and closure return inference behind `ClosureExpressionStructuredTypeResolver`.
- Preserved the previous flat-symbol fallback for closure/arrow-function inferred returns when no structured type is directly available.
- Added `CallableInvocationStructuredTypeResolver`.
- Moved callable-expression invocation return extraction behind `CallableInvocationStructuredTypeResolver`.
- Added `FunctionLikeStructuredReturnResolver`.
- Moved declared-vs-inferred structured return selection and native-return priority rules behind `FunctionLikeStructuredReturnResolver`.
- Added `FunctionLikeParameterResolver`.
- Moved function-like call parameter-name resolution and structured parameter PHPDoc lookup behind `FunctionLikeParameterResolver`.
- `SimpleExpressionTypeResolver` is now 2220 lines after this extraction.
- Reused the existing `TemplateSubstitutionCollector` inside `SimpleExpressionTypeResolver`.
- Removed the duplicate template-substitution collection, direct substitution setting, and union-branch merging helpers from `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` is now 1935 lines after this extraction.
- Added `OwnerTemplateSubstitutionResolver`.
- Moved generic receiver owner template substitution collection and context merging behind `OwnerTemplateSubstitutionResolver`.
- `SimpleExpressionTypeResolver` is now 1869 lines after this extraction.
- Reused `SpecialClassReferenceNormalizer` inside `SimpleExpressionTypeResolver`.
- Removed the duplicate structured PHPDoc `self` / `static` / `parent` normalizer from `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` is now 1790 lines after this extraction.
- Added `StructuredPhpDocTypeInspector`.
- Moved structured root-symbol extraction, template-reference detection, and structured owner declaration traversal behind `StructuredPhpDocTypeInspector`.
- `SimpleExpressionTypeResolver` is now 1636 lines after this extraction.
- Added `PropertyTypeResolver`.
- Moved inherited native property type lookup and static structured-property lookup behind `PropertyTypeResolver`.
- `SimpleExpressionTypeResolver` is now 1543 lines after this extraction.
- Reused `SpecialClassReferenceNormalizer::normalizeSymbols()` for flat return symbols.
- Removed the duplicate flat `self` / `static` / `parent` symbol normalizer from `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` is now 1495 lines after this extraction.
- Added `FunctionNameResolver`.
- Moved function-name resolution from parser attributes behind `FunctionNameResolver`.
- Added `FunctionLikeFlatReturnResolver`.
- Moved flat function and inherited method return symbol resolution behind `FunctionLikeFlatReturnResolver`.
- Removed unused native-return, declaring-owner, class-name, and static-owner wrapper methods from `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` is now 1347 lines after this extraction.
- Added `FunctionLikeCallResolver`.
- Moved common function/method/static-call structured return resolution, template substitution, and flat fallback extraction behind `FunctionLikeCallResolver`.
- `SimpleExpressionTypeResolver` is now 1155 lines after this extraction.
- Added `ArgumentStructuredTypeResolver`.
- Added `MethodCallOwnerResolver`.
- Added `InstancePropertyStructuredTypeResolver`.
- Moved shared argument structured-type resolution, method-call owner preference, and instance property structured PHPDoc resolution behind dedicated services.
- Added `FunctionCallExpressionResolver`.
- Moved function-call expression dispatch into the resolver registry.
- Removed the remaining private transition helpers from `SimpleExpressionTypeResolver`.
- `SimpleExpressionTypeResolver` is now 772 lines after this extraction.
- Removed the temporary expression resolver delegate interfaces.
- Injected concrete domain services into array, class-constant, property, method-call, static-call, and static-property resolvers instead of probing the fallback resolver type.
- `SimpleExpressionTypeResolver` now implements only `ExpressionTypeResolverInterface`.
- `SimpleExpressionTypeResolver` now exposes only the constructor plus `resolve()`, `resolveStructuredPhpDocType()`, and `extractStructuredSymbols()`.
- `SimpleExpressionTypeResolver` is now 331 lines after removing the delegate bridge layer.
- Added `ClosureDocTypeResolver`.
- Added `ClosureLocalVariableTypeResolver`.
- Added `ClosureReturnTypeResolver`.
- Reduced `ClosureExpressionStructuredTypeResolver` to the closure-like callable orchestration responsibility.
- Moved closure-local `@param` / `@var` parsing, closure-local variable inference, and closure return inference behind dedicated services.
- Added `StructuredReturnTypeSelector`.
- Added `NativeReturnTypePriorityResolver`.
- Added `MethodStructuredReturnResolver`.
- Added `FunctionStructuredReturnResolver`.
- Reduced `FunctionLikeStructuredReturnResolver` to a function-like return facade.
- Moved declared-vs-inferred structured return selection, native-vs-structured priority policy, method return lookup, and function return lookup behind dedicated services.
- Added `NativeTypeClassifier`.
- Added `NativeStructuredTypeResolver`.
- Added `NativeVsStructuredPrecisionResolver`.
- Reduced `NativeTypeResolver` to the native type facade.
- Moved native PHP type to structured PHPDoc conversion and native-vs-structured precision comparison behind dedicated services.
- Added `ConstructorArgumentParameterResolver`.
- Added `ConstructorTemplateInferenceResolver`.
- Added `NewExpressionTypeResolver`.
- Reduced `NewExpressionResolver` to object-construction expression dispatch.
- Moved constructor argument-to-parameter matching, constructor template inference, and structured new-expression type construction behind dedicated services.
- Added `ClosureDocTagExtractor`.
- Added `ClosureLocalPhpDocTypeResolver` as the closure-local PHPDoc type conversion service.
- Reduced `ClosureDocTypeResolver` to a closure-local PHPDoc facade.
- Moved closure-local `@param` / `@var` tag extraction and simple closure-local PHPDoc type conversion behind dedicated services.
- Added `TemplateArgumentSubstitutionResolver`.
- Added `TemplateSubstitutionMerger`.
- Reduced `TemplateSubstitutionCollector` to a template-substitution facade while preserving its public API.
- Moved declared-vs-concrete template substitution traversal and substitution merging behind dedicated services.
- Added `FunctionLikeCallTemplateContextResolver`.
- Added `FunctionLikeStructuredCallResolver`.
- Reduced `FunctionLikeCallResolver` to the function-like call facade while preserving its public API.
- Moved call-site template-context construction and structured function-like call return resolution behind dedicated services.
- Added `ClosureParameterVariableTypeResolver`.
- Added `ClosureAssignmentVariableTypeResolver`.
- Reduced `ClosureLocalVariableTypeResolver` to the closure-local variable facade while preserving its public API.
- Moved closure parameter variable metadata resolution and closure-local assignment traversal behind dedicated services.
- Added `TemplateUnionSubstitutionResolver`.
- Added `TemplateGenericSubstitutionResolver`.
- Added `TemplateShapeSubstitutionResolver`.
- Added `TemplateCollectionShapeSubstitutionResolver`.
- Added `TemplateCallableSubstitutionResolver`.
- Reduced `TemplateArgumentSubstitutionResolver` to the recursive template argument substitution orchestrator while preserving its public API.
- Moved union, generic, shape, collection-shape, and callable substitution matching behind dedicated services.
- `php -l` passed for all changed production files.
- Focused native/PHPDoc/template filter passed with 11 tests and 22 assertions.
- Focused array-shape/dynamic-key/class-constant-key filter passed with 30 tests and 68 assertions.
- Focused class-constant/enum/array-shape-key filter passed with 28 tests and 57 assertions after the literal-value extraction.
- Focused array-shape literal-construction filter passed with 19 tests and 46 assertions after the array literal extraction.
- Focused callable/closure/arrow-function filter passed with 52 tests and 110 assertions after the closure extraction.
- Focused callable invocation filter passed with 41 tests and 87 assertions after the callable invocation extraction.
- Focused return/template/callable/native filter passed with 267 tests and 595 assertions after the structured return extraction.
- Focused named-argument/parameter/template/callable/function/method filter passed with 224 tests and 505 assertions after the function-like parameter extraction.
- Focused template/callable/function/method/named/parameter/argument/shape/union filter passed with 258 tests and 584 assertions after reusing `TemplateSubstitutionCollector`.
- Focused template/generic/property/chained/static/method/function/named/argument filter passed with 238 tests and 529 assertions after extracting `OwnerTemplateSubstitutionResolver`.
- Focused self/static/parent/template/callable/method/function/chained/generic filter passed with 260 tests and 580 assertions after reusing `SpecialClassReferenceNormalizer`.
- Focused template/generic/property/method/callable/shape/union/intersection/chained filter passed with 273 tests and 610 assertions after extracting `StructuredPhpDocTypeInspector`.
- Focused property/static/promoted/generic/template/chained/nullsafe filter passed with 209 tests and 461 assertions after extracting `PropertyTypeResolver`.
- Focused self/static/parent/return/method/template/generic filter passed with 278 tests and 621 assertions after reusing flat symbol normalization.
- Focused self/static/parent/return/method/function/template/generic filter passed with 278 tests and 621 assertions after extracting function names and flat function-like returns.
- Focused self/static/parent/return/method/function/template/generic/callable/named/argument filter passed with 285 tests and 635 assertions after extracting `FunctionLikeCallResolver`.
- Focused property/method/function/static/template/generic/chained/nullsafe/return/argument/named filter passed with 275 tests and 616 assertions after extracting argument, method-owner, and instance-property structured services.
- Focused function/callable/method/static/property/template/generic/return/argument/named/array/shape/nullsafe/chained filter passed with 297 tests and 662 assertions after extracting `FunctionCallExpressionResolver` and removing private transition helpers.
- Focused array/shape/class-constant/constant/enum filter passed with 106 tests and 227 assertions after removing the array and class-constant delegate bridges.
- Focused function/callable/method/static/property/template/generic/return/argument/named/array/shape/class-constant/constant/enum/nullsafe/chained filter passed with 324 tests and 716 assertions after removing all expression resolver delegate bridges.
- Focused callable/closure/arrow/function filter passed with 66 tests and 139 assertions after splitting closure structured resolution.
- Focused return/method/function/native/template/inherited/callable/static filter passed with 256 tests and 585 assertions after splitting function-like structured return resolution.
- Focused native/return/template/callable/closure/arrow/array/shape/intersection/union filter passed with 252 tests and 573 assertions after splitting native type resolution.
- Focused new/constructor/generic/template/argument/named/static-factory/cross-file filter passed with 185 tests and 416 assertions after splitting new-expression resolution.
- Focused closure/callable/arrow/var/param/shape/union filter passed with 181 tests and 423 assertions after splitting closure-local PHPDoc resolution.
- Focused template/generic/callable/function/method/named/argument/shape/union filter passed with 276 tests and 616 assertions after splitting template substitution collection.
- Focused function/method/static/callable/template/generic/return/argument/named filter passed with 270 tests and 604 assertions after splitting function-like call resolution.
- Focused closure/arrow/local/variable/var/assign/parameter/callable filter passed with 85 tests and 188 assertions after splitting closure-local variable resolution.
- Focused template/generic/callable/shape/union/intersection/argument/named/function/method filter passed with 276 tests and 616 assertions after splitting template argument substitution.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions after splitting template argument substitution.

## Current Migration State

- Current phase: Phase 12 consolidation is in progress.
- Latest focused validation: Phase 12.1 expression resolver graph factory extraction, 293 tests and 652 assertions.
- Latest full suite reference: Phase 12.3 template-substitution namespace organization, all tests reported passing.
- `MemberGraphStabilityTestSpecific.php` currently targets TestCase374.

### Phase 12 - Consolidation

Status: in progress

Consolidate the resolver architecture after the Phase 11 extractions.

Goals:

- stop line-count-driven extraction
- keep services that carry stable domain responsibilities
- merge back services that only add indirection
- move construction wiring out of the runtime resolver facade
- make future rollback decisions easier

### Phase 12.1 - Expression Resolver Graph Factory

Status: completed

Extract:

- `ExpressionResolverGraph`
- `ExpressionResolverGraphFactory`

Responsibilities:

- build the shared resolver services
- build expression resolvers
- build `ExpressionTypeResolverRegistry`
- build the closure structured type resolver

Result:

- Added `ExpressionResolverGraph`.
- Added `ExpressionResolverGraphFactory`.
- Moved the internal resolver/service wiring out of `ExpressionTypeResolver`.
- Reduced `ExpressionTypeResolver` from 399 lines to 214 lines.
- Preserved the public `ExpressionTypeResolver` constructor and runtime API.
- `php -l` passed for all resolver files.
- Focused function/method/static/callable/template/generic/property/array/class-constant/new/closure/arrow filter passed with 293 tests and 652 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.
- Full `MemberGraphStabilityTest.php` passed with 386 tests and 865 assertions.

### Phase 12.2 - Template Substitution Strategy Audit

Status: completed

Audit:

- `TemplateArgumentSubstitutionResolver`
- `TemplateUnionSubstitutionResolver`
- `TemplateGenericSubstitutionResolver`
- `TemplateShapeSubstitutionResolver`
- `TemplateCollectionShapeSubstitutionResolver`
- `TemplateCallableSubstitutionResolver`
- `TemplateSubstitutionMerger`

Decision:

- Keep the template-substitution strategy split.
- Treat these classes as an internal template-substitution subsystem.
- Do not merge them back into `TemplateArgumentSubstitutionResolver` for now.
- Keep `TemplateSubstitutionCollector` as the public facade consumed by the rest of the resolver graph.

Rationale:

- Each strategy maps to a stable PHPDoc type family: union, generic, shape, collection-shape, or callable.
- `TemplateArgumentSubstitutionResolver` remains a readable recursive orchestrator.
- The strategy classes are coupled to the orchestrator, but that coupling is acceptable for an internal subsystem.

### Phase 12.3 - Template Substitution Namespace Organization

Status: completed

Move internal template-substitution strategies to:

- `Resolver/Service/TemplateSubstitution/`

Moved:

- `TemplateArgumentSubstitutionResolver`
- `TemplateUnionSubstitutionResolver`
- `TemplateGenericSubstitutionResolver`
- `TemplateShapeSubstitutionResolver`
- `TemplateCollectionShapeSubstitutionResolver`
- `TemplateCallableSubstitutionResolver`
- `TemplateSubstitutionMerger`

Result:

- `TemplateSubstitutionCollector` intentionally remains in `Resolver/Service/` as the public facade.
- `Resolver/Service/TemplateSubstitution/` now contains the internal substitution strategies and merger.
- All tests were reported passing after the move.

### Phase 12.4 - Closure-Local PHPDoc Type Resolver Naming

Status: completed

Rename:

- `SimplePhpDocTypeResolver` to `ClosureLocalPhpDocTypeResolver`

Result:

- The resolver name now describes its real scope: closure-local PHPDoc type conversion.
- `ClosureDocTypeResolver` now depends on `ClosureLocalPhpDocTypeResolver`.
- `ExpressionResolverGraphFactory` now wires `ClosureLocalPhpDocTypeResolver`.
- `php -l` passed for all resolver files.
- Focused closure/PHPDoc/var/param/shape/union filter passed with 186 tests and 429 assertions.
- `MemberGraphStabilityTestSpecific.php` passed with 1 test and 2 assertions.

### Phase 12.5 - Lightweight Presentation Audit

Status: completed

Audit:

- resolver class sizes
- current service and expression resolver names
- old delegate bridge remnants
- current-state documentation consistency

Decision:

- Keep the current resolver split.
- Do not merge services solely because they are small.
- Treat the `TemplateSubstitution` namespace as an internal subsystem.
- Keep historical `SimpleExpressionTypeResolver` references in old phase notes, because they describe the name used at the time.

Result:

- No obvious over-extraction requiring rollback was found.
- No `fallbackResolver instanceof` delegate bridge checks remain in resolver code.
- The largest resolver service classes remain under 200 lines, except the graph factory at 269 lines, which is acceptable for wiring.
- `Migration.md` now distinguishes the current `ExpressionTypeResolver` facade name from historical phase notes.
