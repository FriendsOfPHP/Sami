<?php

/*
 * This file is part of the Sami utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sami\Reflection;

use Sami\Project;

class ClassReflection extends Reflection
{
    const CATEGORY_CLASS = 1;
    const CATEGORY_INTERFACE = 2;
    const CATEGORY_TRAIT = 3;

    static private $categoryName = array(
        1 => 'class',
        2 => 'interface',
        3 => 'trait',
    );

    protected $project;
    protected $hash;
    protected $namespace;
    protected $modifiers;
    protected $properties = array();
    protected $methods = array();
    protected $interfaces = array();
    protected $constants = array();
    protected $parent;
    protected $file;
    protected $category = self::CATEGORY_CLASS;
    protected $projectClass = true;
    protected $aliases = array();
    protected $errors = array();

    public function __toString()
    {
        return $this->name;
    }

    public function getClass()
    {
        return $this;
    }

    public function isProjectClass()
    {
        return $this->projectClass;
    }

    public function isPhpClass()
    {
        try {
            $r = new \ReflectionClass($this->name);

            return $r->isInternal();
        } catch (\ReflectionException $e) {
            return false;
        }
    }

    public function setName($name)
    {
        parent::setName(ltrim($name, '\\'));
    }

    public function getShortName()
    {
        if (false !== $pos = strrpos($this->name, '\\')) {
            return substr($this->name, $pos + 1);
        }

        return $this->name;
    }

    public function isAbstract()
    {
        return self::MODIFIER_ABSTRACT === (self::MODIFIER_ABSTRACT & $this->modifiers);
    }

    public function isFinal()
    {
        return self::MODIFIER_FINAL === (self::MODIFIER_FINAL & $this->modifiers);
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = ltrim($namespace, '\\');
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function addProperty(PropertyReflection $property)
    {
        $this->properties[$property->getName()] = $property;
        $property->setClass($this);
    }

    public function getProperties($deep = false)
    {
        if (false === $deep) {
            return $this->properties;
        }

        $properties = array();
        if ($this->getParent()) {
            foreach ($this->getParent()->getProperties(true) as $name => $property) {
                $properties[$name] = $property;
            }
        }

        foreach ($this->properties as $name => $property) {
            $properties[$name] = $property;
        }

        return $properties;
    }

    /*
     * Can be any iterator (so that we can lazy-load the properties)
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    public function addConstant(ConstantReflection $constant)
    {
        $this->constants[$constant->getName()] = $constant;
        $constant->setClass($this);
    }

    public function getConstants($deep = false)
    {
        if (false === $deep) {
            return $this->constants;
        }

        $constants = array();
        if ($this->getParent()) {
            foreach ($this->getParent()->getConstants(true) as $name => $constant) {
                $constants[$name] = $constant;
            }
        }

        foreach ($this->constants as $name => $constant) {
            $constants[$name] = $constant;
        }

        return $constants;
    }

    public function setConstants($constants)
    {
        $this->constants = $constants;
    }

    public function addMethod(MethodReflection $method)
    {
        $this->methods[$method->getName()] = $method;
        $method->setClass($this);
    }

    public function getMethod($name)
    {
        return isset($this->methods[$name]) ? $this->methods[$name] : false;
    }

    public function getParentMethod($name)
    {
        if ($this->getParent()) {
            foreach ($this->getParent()->getMethods(true) as $n => $method) {
                if ($name == $n) {
                    return $method;
                }
            }
        }

        foreach ($this->getInterfaces(true) as $interface) {
            foreach ($interface->getMethods(true) as $n => $method) {
                if ($name == $n) {
                    return $method;
                }
            }
        }
    }

    public function getMethods($deep = false)
    {
        if (false === $deep) {
            return $this->methods;
        }

        $methods = array();
        if ($this->isInterface()) {
            foreach ($this->getInterfaces(true) as $interface) {
                foreach ($interface->getMethods(true) as $name => $method) {
                    $methods[$name] = $method;
                }
            }
        }

        if ($this->getParent()) {
            foreach ($this->getParent()->getMethods(true) as $name => $method) {
                $methods[$name] = $method;
            }
        }

        foreach ($this->methods as $name => $method) {
            $methods[$name] = $method;
        }

        return $methods;
    }

    public function setMethods($methods)
    {
        $this->methods = $methods;
    }

    public function addInterface($interface)
    {
        $this->interfaces[$interface] = $interface;
    }

    public function getInterfaces($deep = false)
    {
        $interfaces = array();
        foreach ($this->interfaces as $interface) {
            $interfaces[] = $this->project->getClass($interface);
        }

        if (false === $deep) {
            return $interfaces;
        }

        $allInterfaces = $interfaces;
        foreach ($interfaces as $interface) {
            $allInterfaces = array_merge($allInterfaces, $interface->getInterfaces(true));
        }

        if ($parent = $this->getParent()) {
            $allInterfaces = array_merge($allInterfaces, $parent->getInterfaces(true));
        }

        return $allInterfaces;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent($deep = false)
    {
        if (!$this->parent) {
            return $deep ? array() : null;
        }

        $parent = $this->project->getClass($this->parent);

        if (false === $deep) {
            return $parent;
        }

        return array_merge(array($parent), $parent->getParent(true));
    }

    public function setInterface($boolean)
    {
        if ($boolean) {
            $this->category = self::CATEGORY_INTERFACE;
        } else {
            $this->category = self::CATEGORY_CLASS;
        }
    }

    public function isInterface()
    {
        return $this->category === self::CATEGORY_INTERFACE;
    }

    public function setTrait($boolean)
    {
        if ($boolean) {
            $this->category = self::CATEGORY_TRAIT;
        } else {
            $this->category = self::CATEGORY_CLASS;
        }
    }

    public function isTrait()
    {
        return $this->category === self::CATEGORY_TRAIT;
    }
    
    public function setCategory($category)
    {
        $this->category = $category;
    }
    
    public function isException()
    {
        $parent = $this;
        while ($parent = $parent->getParent()) {
            if ('Exception' == $parent->getName()) {
                return true;
            }
        }

        return false;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    public function toArray()
    {
        return array(
            'name'         => $this->name,
            'line'         => $this->line,
            'short_desc'   => $this->shortDesc,
            'long_desc'    => $this->longDesc,
            'hint'         => $this->hint,
            'tags'         => $this->tags,
            'namespace'    => $this->namespace,
            'file'         => $this->file,
            'hash'         => $this->hash,
            'parent'       => $this->parent,
            'modifiers'    => $this->modifiers,
            'is_trait'     => $this->isTrait(),
            'is_interface' => $this->isInterface(),
            'aliases'      => $this->aliases,
            'errors'       => $this->errors,
            'interfaces'   => $this->interfaces,
            'properties'   => array_map(function ($property) { return $property->toArray(); }, $this->properties),
            'methods'      => array_map(function ($method) { return $method->toArray(); }, $this->methods),
            'constants'    => array_map(function ($constant) { return $constant->toArray(); }, $this->constants),
        );
    }

    static public function fromArray(Project $project, $array)
    {
        $class = new self($array['name'], $array['line']);
        $class->shortDesc  = $array['short_desc'];
        $class->longDesc   = $array['long_desc'];
        $class->hint       = $array['hint'];
        $class->tags       = $array['tags'];
        $class->namespace  = $array['namespace'];
        $class->hash       = $array['hash'];
        $class->file       = $array['file'];
        $class->modifiers  = $array['modifiers'];
        if ($array['is_interface']) {
            $class->setInterface(true);
        }
        if ($array['is_trait']) {
            $class->setTrait(true);
        }
        $class->aliases    = $array['aliases'];
        $class->errors     = $array['errors'];
        $class->parent     = $array['parent'];
        $class->interfaces = $array['interfaces'];
        $class->constants  = $array['constants'];

        $class->setProject($project);

        foreach ($array['methods'] as $method) {
            $method = MethodReflection::fromArray($project, $method);
            $method->setClass($class);
            $class->addMethod($method);
        }

        foreach ($array['properties'] as $property) {
            $property = PropertyReflection::fromArray($project, $property);
            $property->setClass($class);
            $class->addProperty($property);
        }

        foreach ($array['constants'] as $constant) {
            $constant = ConstantReflection::fromArray($project, $constant);
            $constant->setClass($class);
            $class->addConstant($constant);
        }

        return $class;
    }
    
    public function getCategoryName()
    {
        return self::$categoryName[$this->category];
    }
}
