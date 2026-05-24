<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpParser\Indexing;

use BabelForge\MemberGraph\Domain\Index\Function\FunctionParameterStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnStructuredTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Function\FunctionReturnTypeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ParamPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\ReturnPhpDocTypeExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use PhpParser\Node\Stmt\Function_;

/**
 * Builds structured function return and parameter type indexes.
 */
final readonly class FunctionStructuredTypeIndexBuilder
{
    /**
     * Constructor.
     *
     * @param ReturnPhpDocTypeExtractor         $returnPhpDocTypeExtractor         the return PHPDoc type extractor
     * @param ParamPhpDocTypeExtractor          $paramPhpDocTypeExtractor          the parameter PHPDoc type extractor
     * @param PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor the template definition extractor
     */
    public function __construct(
        private ReturnPhpDocTypeExtractor $returnPhpDocTypeExtractor,
        private ParamPhpDocTypeExtractor $paramPhpDocTypeExtractor,
        private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
    ) {
    }

    /**
     * Builds structured function return and parameter type indexes.
     *
     * @param FunctionReturnTypeIndex $functionReturnTypeIndex the function return type index
     */
    public function build(
        FunctionReturnTypeIndex $functionReturnTypeIndex,
    ): FunctionStructuredTypeBuildResult {
        $structuredReturnTypeIndex = new FunctionReturnStructuredTypeIndex();
        $structuredParameterTypeIndex = new FunctionParameterStructuredTypeIndex();

        foreach ($functionReturnTypeIndex as $functionName => $details) {
            $functionNode = $details->parentNode;

            if (!$functionNode instanceof Function_) {
                continue;
            }

            $templateDefinitions = $this->phpDocTemplateDefinitionExtractor->extract(
                node: $functionNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                visibleTemplateDefinitions: new PhpDocTemplateDefinitionCollection(),
                context: $details->context,
                phpDocTagKind: PhpDocTagKind::TEMPLATE,
            );

            $structuredReturnType = $this->returnPhpDocTypeExtractor->extractStructured(
                node: $functionNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                templateDefinitions: $templateDefinitions,
                context: $details->context
            );

            if (null !== $structuredReturnType) {
                $structuredReturnTypeIndex->set($functionName, $structuredReturnType);
            }

            $structuredParameters = $this->paramPhpDocTypeExtractor->extract(
                node: $functionNode,
                currentNamespace: $details->namespace,
                usesByAlias: $details->usesByAlias,
                templateDefinitions: $templateDefinitions,
                context: $details->context
            );

            foreach ($structuredParameters as $parameterName => $resolvedType) {
                $structuredParameterTypeIndex->set(
                    $functionName,
                    $parameterName,
                    $resolvedType->structuredType,
                );
            }
        }

        return new FunctionStructuredTypeBuildResult(
            returnTypeIndex: $structuredReturnTypeIndex,
            parameterTypeIndex: $structuredParameterTypeIndex,
        );
    }
}
