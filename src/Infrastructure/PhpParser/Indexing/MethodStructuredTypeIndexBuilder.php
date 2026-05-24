<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\Method\MethodParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Method\MethodReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\ParentMethodNodeResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Inheritance\PhpDocInheritDocResolver;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * Builds structured method return and parameter type indexes.
 */
final readonly class MethodStructuredTypeIndexBuilder
{
    /**
     * Constructor.
     *
     * @param ReturnPhpDocTypeExtractor         $returnPhpDocTypeExtractor         the return PHPDoc type extractor
     * @param ParamPhpDocTypeExtractor          $paramPhpDocTypeExtractor          the parameter PHPDoc type extractor
     * @param PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor the template definition extractor
     * @param PhpDocInheritDocResolver          $phpDocInheritDocResolver          the inheritDoc resolver
     * @param ParentMethodNodeResolver          $parentMethodNodeResolver          the parent method node resolver
     */
    public function __construct(
        private ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor,
        private ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor,
        private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
        private PhpDocInheritDocResolver $phpDocInheritDocResolver,
        private ParentMethodNodeResolver $parentMethodNodeResolver,
    ) {
    }

    /**
     * Builds structured method return and parameter type indexes.
     *
     * @param MethodReturnTypeIndex $methodReturnTypeIndex the method return type index
     */
    public function build(
        MethodReturnTypeIndex $methodReturnTypeIndex,
    ): MethodStructuredTypeBuildResult {
        $returnIndex = new MethodReturnStructuredTypeIndex();
        $paramIndex = new MethodParameterStructuredTypeIndex();

        foreach ($methodReturnTypeIndex as $key => $details) {
            $details->setResolved();

            $methodNode = $details->parentNode;

            if (!$methodNode instanceof ClassMethod) {
                continue;
            }

            $owner = $this->extractOwner($key);
            $methodName = $methodNode->name->toString();

            $allParentMethodNodes = $this->parentMethodNodeResolver->resolveAllParents($owner, $methodName);
            $effectiveDoc = $this->phpDocInheritDocResolver->mergeEffectiveDoc(
                $methodNode,
                $allParentMethodNodes,
                $details->context
            );
            $this->phpDocInheritDocResolver::setEffectiveDocComment($methodNode, $effectiveDoc);

            $templateDefinitions = $this->phpDocTemplateDefinitionExtractor->extract(
                node: $methodNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                visibleTemplateDefinitions: new PhpDocTemplateDefinitionCollection(),
                context: $details->context,
                phpDocTagKind: PhpDocTagKind::TEMPLATE,
            );

            $structuredReturnType = $this->returnPhpDocTypeExtractor->extractStructured(
                node: $methodNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                templateDefinitions: $templateDefinitions,
                context: $details->context
            );

            if (null !== $structuredReturnType) {
                $returnIndex->set($owner, $methodName, $structuredReturnType);
            }

            $structuredParams = $this->paramPhpDocTypeExtractor->extract(
                node: $methodNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                templateDefinitions: $templateDefinitions,
                context: $details->context
            );

            foreach ($structuredParams as $parameterName => $resolvedType) {
                $paramIndex->set(
                    $owner,
                    $methodName,
                    $parameterName,
                    $resolvedType->structuredType,
                );
            }
        }

        return new MethodStructuredTypeBuildResult(
            returnTypeIndex: $returnIndex,
            parameterTypeIndex: $paramIndex,
        );
    }

    /**
     * Extracts the owner FQCN from one indexed method key.
     *
     * @param string $key the indexed method key
     */
    private function extractOwner(string $key): string
    {
        $pos = strrpos($key, '::');

        if (false === $pos) {
            return '';
        }

        return substr($key, 0, $pos);
    }
}
