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

use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\ParameterReflection;
use Sami\Reflection\PropertyReflection;
use Sami\Reflection\ConstantReflection;
use Sami\Project;

class NodeVisitor extends \PHPParser_NodeVisitorAbstract
{
    protected $context;

    public function __construct(ParserContext $context)
    {
        $this->context = $context;
    }

    public function enterNode(\PHPParser_Node $node)
    {
        if ($node instanceof \PHPParser_Node_Stmt_Namespace) {
            $this->context->enterNamespace((string) $node->name);
        } elseif ($node instanceof \PHPParser_Node_Stmt_Use) {
            $this->addAliases($node);
        } elseif ($node instanceof \PHPParser_Node_Stmt_Interface) {
            $this->addInterface($node);
        } elseif ($node instanceof \PHPParser_Node_Stmt_Class) {
            $this->addClass($node);
        } elseif ($node instanceof \PHPParser_Node_Stmt_Trait) {
            $this->addTrait($node);
        } elseif ($this->context->getClass() && $node instanceof \PHPParser_Node_Stmt_Property) {
            $this->addProperty($node);
        } elseif ($this->context->getClass() && $node instanceof \PHPParser_Node_Stmt_ClassMethod) {
            $this->addMethod($node);
        } elseif ($this->context->getClass() && $node instanceof \PHPParser_Node_Stmt_ClassConst) {
            $this->addConstant($node);
        }
    }

    public function leaveNode(\PHPParser_Node $node)
    {
        if ($node instanceof \PHPParser_Node_Stmt_Namespace) {
            $this->context->leaveNamespace();
        } elseif ($node instanceof \PHPParser_Node_Stmt_Class || $node instanceof \PHPParser_Node_Stmt_Interface || $node instanceof \PHPParser_Node_Stmt_Trait) {
            $this->context->leaveClass();
        }
    }

    protected function addAliases(\PHPParser_Node_Stmt_Use $node)
    {
        foreach ($node->uses as $use) {
            $this->context->addAlias($use->alias, (string) $use->name);
        }
    }

    protected function addInterface(\PHPParser_Node_Stmt_Interface $node)
    {
        $class = $this->addClassOrInterface($node);

        $class->setInterface(true);
        foreach ($node->extends as $interface) {
            $class->addInterface((string) $interface);
        }
    }

    protected function addClass(\PHPParser_Node_Stmt_Class $node)
    {
        $class = $this->addClassOrInterface($node);

        foreach ($node->implements as $interface) {
            $class->addInterface((string) $interface);
        }

        if ($node->extends) {
            $class->setParent((string) $node->extends);
        }
    }

   protected function addTrait(\PHPParser_Node_Stmt_Trait $node)
    {
        $class = $this->addClassOrInterface($node);

        $class->setTrait(true);        
    }
    
    
    protected function addClassOrInterface($node)
    {
        $class = new ClassReflection((string) $node->namespacedName, $node->getLine());
        $class->setModifiers($node->type);
        $class->setNamespace($this->context->getNamespace());
        $class->setAliases($this->context->getAliases());
        $class->setHash($this->context->getHash());
        $class->setFile($this->context->getFile());

        if ($this->context->getFilter()->acceptClass($class)) {
            $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $class);
            $class->setDocComment($node->getDocComment());
            $class->setShortDesc($comment->getShortDesc());
            $class->setLongDesc($comment->getLongDesc());
            if ($errors = $comment->getErrors()) {
                $this->context->addErrors((string) $class, $node->getLine(), $errors);
                $class->setErrors($errors);
            } else {
                $class->setTags($comment->getOtherTags());
            }

            $this->context->enterClass($class);
        }

        return $class;
    }

    protected function addMethod(\PHPParser_Node_Stmt_ClassMethod $node)
    {
        $method = new MethodReflection($node->name, $node->getLine());
        $method->setModifiers((string) $node->type);

        if ($this->context->getFilter()->acceptMethod($method)) {
            $this->context->getClass()->addMethod($method);

            $method->setByRef((string) $node->byRef);

            foreach ($node->params as $param) {
                $parameter = new ParameterReflection($param->name, $param->getLine());
                $parameter->setModifiers((string) $param->type);
                $parameter->setByRef($param->byRef);
                if ($param->default) {
                    $parameter->setDefault($this->context->getPrettyPrinter()->prettyPrintExpr($param->default));
                }
                if ((string) $param->type) {
                    $parameter->setHint($this->resolveHint(array(array((string) $param->type, false))));
                }
                $method->addParameter($parameter);
            }

            $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $method);
            $method->setDocComment($node->getDocComment());
            $method->setShortDesc($comment->getShortDesc());
            $method->setLongDesc($comment->getLongDesc());
            if (!$errors = $comment->getErrors()) {
                $errors = $this->updateMethodParametersFromTags($method, $comment->getTag('param'));

                if ($tag = $comment->getTag('return')) {
                    $method->setHint($this->resolveHint($tag[0][0]));
                    $method->setHintDesc($tag[0][1]);
                }

                $method->setExceptions($comment->getTag('throws'));
                $method->setTags($comment->getOtherTags());
            }

            $this->context->addErrors((string) $method, $node->getLine(), $errors);
            $method->setErrors($errors);
        }
    }

    protected function addProperty(\PHPParser_Node_Stmt_Property $node)
    {
        foreach ($node->props as $prop) {
            $property = new PropertyReflection($prop->name, $prop->getLine());
            $property->setModifiers($node->type);

            if ($this->context->getFilter()->acceptProperty($property)) {
                $this->context->getClass()->addProperty($property);

                $property->setDefault($prop->default);

                $comment = $this->context->getDocBlockParser()->parse($node->getDocComment(), $this->context, $property);
                $property->setDocComment($node->getDocComment());
                $property->setShortDesc($comment->getShortDesc());
                $property->setLongDesc($comment->getLongDesc());
                if ($errors = $comment->getErrors()) {
                    $this->context->addErrors((string) $property, $prop->getLine(), $errors);
                    $property->setErrors($errors);
                } else {
                    if ($tag = $comment->getTag('var')) {
                        $property->setHint($this->resolveHint($tag[0][0]));
                        $property->setHintDesc($tag[0][1]);
                    }

                    $property->setTags($comment->getOtherTags());
                }
            }
        }
    }

    protected function addConstant(\PHPParser_Node_Stmt_ClassConst $node)
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
            return array(sprintf('"%d" @param tags are defined by "%d" are expected', count($tags), count($method->getParameters())));
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
        if ('self' === $alias || 'static' === $alias) {
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
}
