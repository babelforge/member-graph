<?php

declare(strict_types=1);

namespace BabelForge\MemberGraph\Infrastructure\PhpDoc\Template;

use BabelForge\MemberGraph\Domain\Index\ClassLike\ClassLikeNodeIndex;
use BabelForge\MemberGraph\Domain\Index\Template\ClassTemplateDefinitionIndex;
use BabelForge\MemberGraph\Domain\Index\Template\PhpDocTemplateDefinitionCollection;
use BabelForge\MemberGraph\Domain\Type\TypeIndexContext;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Extractor\PhpDocTemplateDefinitionExtractor;
use BabelForge\MemberGraph\Infrastructure\PhpDoc\Resolver\PhpDocTagKind;
use BabelForge\MemberGraph\Infrastructure\UseStatements\UsesByAliasCollection;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;

/**
 * Builds the class template definition index from class-like nodes.
 */
final readonly class ClassTemplateDefinitionIndexBuilder
{
    /**
     * @param PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor the template definition extractor
     */
    public function __construct(
        private PhpDocTemplateDefinitionExtractor $phpDocTemplateDefinitionExtractor,
    ) {
    }

    /**
     * Builds the class template definition index.
     *
     * @param ClassLikeNodeIndex $classLikeNodeIndex the class-like node index
     * @param string             $fullFilePath       the current original file path
     * @param string             $virtualFilePath    the current virtual file path
     */
    public function build(
        ClassLikeNodeIndex $classLikeNodeIndex,
        string $fullFilePath = '',
        string $virtualFilePath = '',
    ): ClassTemplateDefinitionIndex {
        $index = new ClassTemplateDefinitionIndex();

        foreach ($classLikeNodeIndex as $owner => $classLikeNode) {
            $context = $this->buildContext(
                owner: $owner,
                classLikeNode: $classLikeNode,
                fullFilePath: $fullFilePath,
                virtualFilePath: $virtualFilePath,
            );

            $definitions = $this->phpDocTemplateDefinitionExtractor->extract(
                node: $classLikeNode,
                currentNamespace: $context->namespace,
                usesByAlias: $context->usesByAlias,
                visibleTemplateDefinitions: new PhpDocTemplateDefinitionCollection(),
                context: $context,
                phpDocTagKind: PhpDocTagKind::TEMPLATE,
            );

            $index->set($owner, $definitions);
        }

        return $index;
    }

    /**
     * Builds one type index context for one class-like node.
     *
     * @param string    $owner           the owner FQCN
     * @param ClassLike $classLikeNode   the class-like node
     * @param string    $fullFilePath    the original file path
     * @param string    $virtualFilePath the virtual file path
     */
    private function buildContext(
        string $owner,
        ClassLike $classLikeNode,
        string $fullFilePath,
        string $virtualFilePath,
    ): TypeIndexContext {
        $context = new TypeIndexContext();
        $context->fullFilePath = $fullFilePath;
        $context->virtualFilePath = $virtualFilePath;
        $context->namespace = $this->resolveNamespace($classLikeNode);
        $context->owner = $owner;
        $context->member = '';
        $context->usesByAlias = $this->resolveUsesByAlias($classLikeNode);

        return $context;
    }

    /**
     * Resolves the namespace for one class-like node.
     *
     * @param ClassLike $classLikeNode the class-like node
     */
    private function resolveNamespace(ClassLike $classLikeNode): string
    {
        $namespacedName = $classLikeNode->getAttribute('namespacedName');

        if (!$namespacedName instanceof Name) {
            return '';
        }

        $parts = $namespacedName->getParts();
        array_pop($parts);

        return implode('\\', $parts);
    }

    /**
     * Resolves use imports visible from one class-like node.
     *
     * @param ClassLike $classLikeNode the class-like node
     */
    private function resolveUsesByAlias(ClassLike $classLikeNode): UsesByAliasCollection
    {
        $usesByAlias = new UsesByAliasCollection();

        $namespaceNode = $this->findNamespaceNode($classLikeNode);

        if (null === $namespaceNode) {
            return $usesByAlias;
        }

        foreach ($namespaceNode->stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $useUse) {
                    $fqcn = $useUse->name->toString();
                    $alias = null !== $useUse->alias
                        ? $useUse->alias->toString()
                        : $useUse->name->getLast();

                    $usesByAlias->set($alias, $fqcn);
                }

                continue;
            }

            if ($stmt instanceof GroupUse) {
                $prefix = $stmt->prefix->toString();

                foreach ($stmt->uses as $useUse) {
                    $fqcn = $prefix.'\\'.$useUse->name->toString();
                    $alias = null !== $useUse->alias
                        ? $useUse->alias->toString()
                        : $useUse->name->getLast();

                    $usesByAlias->set($alias, $fqcn);
                }
            }
        }

        return $usesByAlias;
    }

    /**
     * Finds the surrounding namespace node of one class-like node.
     *
     * @param ClassLike $classLikeNode the class-like node
     */
    private function findNamespaceNode(ClassLike $classLikeNode): ?Namespace_
    {
        $parent = $classLikeNode->getAttribute('parent');

        while ($parent instanceof Node) {
            if ($parent instanceof Namespace_) {
                return $parent;
            }

            $parent = $parent->getAttribute('parent');
        }

        return null;
    }
}
