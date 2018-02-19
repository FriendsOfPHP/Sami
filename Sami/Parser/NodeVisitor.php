<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Parser;

use PhpParser\Node as AbstractNode;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\ClassConst as ClassConstNode;
use PhpParser\Node\Stmt\ClassMethod as ClassMethodNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\TraitUse as TraitUseNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\Node\Stmt\Use_ as UseNode;
use PhpParser\Node\NullableType;
use Sami\Project;
use Sami\Reflection\ClassReflection;
use Sami\Reflection\ConstantReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\ParameterReflection;
use Sami\Reflection\PropertyReflection;

class NodeVisitor extends NodeVisitorAbstract
{
    protected $context;

    public function __construct(ParserContext $context)
    {
        $this->context = $context;
    }

    public function enterNode(AbstractNode $node)
    {
        if ($node instanceof NamespaceNode) {
            $this->context->enterNamespace((string) $node->name);
        } elseif ($node instanceof UseNode) {
            $this->addAliases($node);
        } elseif ($node instanceof InterfaceNode) {
            $this->addInterface($node);
        } elseif ($node instanceof ClassNode) {
            $this->addClass($node);
        } elseif ($node instanceof TraitNode) {
            $this->addTrait($node);
        } elseif ($this->context->getClass() && $node instanceof TraitUseNode) {
            $this->addTraitUse($node);
        } elseif ($this->context->getClass() && $node instanceof PropertyNode) {
            $this->addProperty($node);
        } elseif ($this->context->getClass() && $node instanceof ClassMethodNode) {
            $this->addMethod($node);
        } elseif ($this->context->getClass() && $node instanceof ClassConstNode) {
            $this->addConstant($node);
        }
    }

    public function leaveNode(AbstractNode $node)
    {
        if ($node instanceof NamespaceNode) {
            $this->context->leaveNamespace();
        } elseif ($node instanceof ClassNode || $node instanceof InterfaceNode || $node instanceof TraitNode) {
            $this->context->leaveClass();
        }
    }

    protected function addAliases(UseNode $node)
    {
        foreach ($node->uses as $use) {
            $this->context->addAlias($use->alias, (string) $use->name);
        }
    }

    protected function addInterface(InterfaceNode $node)
    {
        $class = $this->addClassOrInterface($node);

        $class->setInterface(true);
        foreach ($node->extends as $interface) {
            $class->addInterface((string) $interface);
        }
    }

    protected function addClass(ClassNode $node)
    {
        // Skip anonymous classes
        if ($node->isAnonymous()) {
            return;
        }

        $class = $this->addClassOrInterface($node);

        foreach ($node->implements as $interface) {
            $class->addInterface((string) $interface);
        }

        if ($node->extends) {
            $class->setParent((string) $node->extends);
        }
    }

    protected function addTrait(TraitNode $node)
    {
        $class = $this->addClassOrInterface($node);

        $class->setTrait(true);
    }

    protected function addClassOrInterface(ClassLikeNode $node)
    {
        $class = new ClassReflection((string) $node->namespacedName, $node->getLine());
        if ($node instanceof ClassNode) {
            $class->setModifiers($node->flags);
        }
        $class->setNamespace($this->context->getNamespace());
        $class->setAliases($this->context->getAliases());
        $class->setHash($this->context->getHash());
        $class->setFile($this->context->getFile());

        $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $class);
        $class->setDocComment($node->getDocComment());
        $class->setShortDesc($comment->getShortDesc());
        $class->setLongDesc($comment->getLongDesc());
        $class->setSee($this->resolveSee($comment->getTag('see')));
        if ($errors = $comment->getErrors()) {
            $class->setErrors($errors);
        } else {
            $class->setTags($comment->getOtherTags());
        }

        if ($this->context->getFilter()->acceptClass($class)) {
            if ($errors) {
                $this->context->addErrors((string) $class, $node->getLine(), $errors);
            }
            $this->context->enterClass($class);
        }

        return $class;
    }

    protected function addMethod(ClassMethodNode $node)
    {
        $method = new MethodReflection($node->name, $node->getLine());
        $method->setModifiers($node->flags);
        $method->setByRef((string) $node->byRef);

        foreach ($node->params as $param) {
            $parameter = new ParameterReflection($param->name, $param->getLine());
            $parameter->setModifiers($param->type);
            $parameter->setByRef($param->byRef);
            if ($param->default) {
                $parameter->setDefault($this->context->getPrettyPrinter()->prettyPrintExpr($param->default));
            }

            $parameter->setVariadic($param->variadic);

            $type = $param->type;
            $typeStr = null;

            if (is_string($param->type)) {
                $type = $param->type;
                $typeStr = (string) $param->type;
            } elseif ($param->type instanceof NullableType) {
                $type = $param->type->type;
                $typeStr = (string) $param->type->type;
            } elseif (null !== $param->type) {
                $typeStr = (string) $param->type;
            }

            if ($type instanceof FullyQualified && 0 !== strpos($typeStr, '\\')) {
                $typeStr = '\\'.$typeStr;
            }

            if (null !== $typeStr) {
                $typeArr = array(array($typeStr, false));

                if ($param->type instanceof NullableType) {
                    $typeArr[] = array('null', false);
                }

                $parameter->setHint($this->resolveHint($typeArr));
            }

            $method->addParameter($parameter);
        }

        $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $method);
        $method->setDocComment($node->getDocComment());
        $method->setShortDesc($comment->getShortDesc());
        $method->setLongDesc($comment->getLongDesc());
        $method->setSee($this->resolveSee($comment->getTag('see')));
        if (!$errors = $comment->getErrors()) {
            $errors = $this->updateMethodParametersFromTags($method, $comment->getTag('param'));

            if ($tag = $comment->getTag('return')) {
                $method->setHint($this->resolveHint($tag[0][0]));
                $method->setHintDesc($tag[0][1]);
            }

            $method->setExceptions($comment->getTag('throws'));
            $method->setTags($comment->getOtherTags());
        }

        $method->setErrors($errors);

        if ($this->context->getFilter()->acceptMethod($method)) {
            $this->context->getClass()->addMethod($method);

            if ($errors) {
                $this->context->addErrors((string) $method, $node->getLine(), $errors);
            }
        }
    }

    protected function addProperty(PropertyNode $node)
    {
        foreach ($node->props as $prop) {
            $property = new PropertyReflection($prop->name, $prop->getLine());
            $property->setModifiers($node->flags);

            $property->setDefault($prop->default);

            $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $property);
            $property->setDocComment($node->getDocComment());
            $property->setShortDesc($comment->getShortDesc());
            $property->setLongDesc($comment->getLongDesc());
            $property->setSee($this->resolveSee($comment->getTag('see')));
            if ($errors = $comment->getErrors()) {
                $property->setErrors($errors);
            } else {
                if ($tag = $comment->getTag('var')) {
                    $property->setHint($this->resolveHint($tag[0][0]));
                    $property->setHintDesc($tag[0][1]);
                }

                $property->setTags($comment->getOtherTags());
            }

            if ($this->context->getFilter()->acceptProperty($property)) {
                $this->context->getClass()->addProperty($property);

                if ($errors) {
                    $this->context->addErrors((string) $property, $prop->getLine(), $errors);
                }
            }
        }
    }

    protected function addTraitUse(TraitUseNode $node)
    {
        foreach ($node->traits as $trait) {
            $this->context->getClass()->addTrait((string) $trait);
        }
    }

    protected function addConstant(ClassConstNode $node)
    {
        foreach ($node->consts as $const) {
            $constant = new ConstantReflection($const->name, $const->getLine());
            $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $constant);
            $constant->setDocComment($node->getDocComment());
            $constant->setShortDesc($comment->getShortDesc());
            $constant->setLongDesc($comment->getLongDesc());

            $this->context->getClass()->addConstant($constant);
        }
    }

    protected function updateMethodParametersFromTags(MethodReflection $method, array $tags)
    {
        // bypass if there is no @param tags defined (@param tags are optional)
        if (!count($tags)) {
            return array();
        }

        if (count($method->getParameters()) != count($tags)) {
            return array(sprintf('"%d" @param tags are expected but only "%d" found', count($method->getParameters()), count($tags)));
        }

        $errors = array();
        foreach (array_keys($method->getParameters()) as $i => $name) {
            if ($tags[$i][1] && $tags[$i][1] != $name) {
                $errors[] = sprintf('The "%s" @param tag variable name is wrong (should be "%s")', $tags[$i][1], $name);
            }
        }

        if ($errors) {
            return $errors;
        }

        foreach ($tags as $i => $tag) {
            $parameter = $method->getParameter($tag[1] ? $tag[1] : $i);
            $parameter->setShortDesc($tag[2]);
            if (!$parameter->hasHint()) {
                $parameter->setHint($this->resolveHint($tag[0]));
            }
        }

        return array();
    }

    protected function resolveHint($hints)
    {
        foreach ($hints as $i => $hint) {
            $hints[$i] = array($this->resolveAlias($hint[0]), $hint[1]);
        }

        return $hints;
    }

    protected function resolveAlias($alias)
    {
        // not a class
        if (Project::isPhpTypeHint($alias)) {
            return $alias;
        }

        // FQCN
        if ('\\' == substr($alias, 0, 1)) {
            return $alias;
        }

        $class = $this->context->getClass();

        // special aliases
        if ('self' === $alias || 'static' === $alias || '\$this' === $alias) {
            return $class->getName();
        }

        // an alias defined by a use statement
        $aliases = $class->getAliases();
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }

        // a class in the current class namespace
        return $class->getNamespace().'\\'.$alias;
    }

    protected function resolveSee(array $see)
    {
        $return = array();
        $matches = array();

        foreach ($see as $seeEntry) {
            $reference = $seeEntry[1];
            $description = $seeEntry[2];
            if ((bool) preg_match('/^[\w]+:\/\/.+$/', $reference)) { //URL
                $return[] = array(
                    $reference,
                    $description,
                    false,
                    false,
                    $reference,
                );
            } elseif ((bool) preg_match('/(.+)\:\:(.+)\(.*\)/', $reference, $matches)) { //Method
                $return[] = array(
                    $reference,
                    $description,
                    $this->resolveAlias($matches[1]),
                    $matches[2],
                    false,
                );
            } else { // We assume, that this is a class reference.
                $return[] = array(
                    $reference,
                    $description,
                    $this->resolveAlias($reference),
                    false,
                    false,
                );
            }
        }

        return $return;
    }
}
