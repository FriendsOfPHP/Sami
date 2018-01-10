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

    private static $categoryName = array(
        1 => 'class',
        2 => 'interface',
        3 => 'trait',
    );

    private static $phpInternalClasses = array(
        'stdclass' => true,
        'exception' => true,
        'errorexception' => true,
        'error' => true,
        'parseerror' => true,
        'typeerror' => true,
        'arithmeticerror' => true,
        'divisionbyzeroerror' => true,
        'closure' => true,
        'generator' => true,
        'closedgeneratorexception' => true,
        'datetime' => true,
        'datetimeimmutable' => true,
        'datetimezone' => true,
        'dateinterval' => true,
        'dateperiod' => true,
        'libxmlerror' => true,
        'sqlite3' => true,
        'sqlite3stmt' => true,
        'sqlite3result' => true,
        'domexception' => true,
        'domstringlist' => true,
        'domnamelist' => true,
        'domimplementationlist' => true,
        'domimplementationsource' => true,
        'domimplementation' => true,
        'domnode' => true,
        'domnamespacenode' => true,
        'domdocumentfragment' => true,
        'domdocument' => true,
        'domnodelist' => true,
        'domnamednodemap' => true,
        'domcharacterdata' => true,
        'domattr' => true,
        'domelement' => true,
        'domtext' => true,
        'domcomment' => true,
        'domtypeinfo' => true,
        'domuserdatahandler' => true,
        'domdomerror' => true,
        'domerrorhandler' => true,
        'domlocator' => true,
        'domconfiguration' => true,
        'domcdatasection' => true,
        'domdocumenttype' => true,
        'domnotation' => true,
        'domentity' => true,
        'domentityreference' => true,
        'domprocessinginstruction' => true,
        'domstringextend' => true,
        'domxpath' => true,
        'finfo' => true,
        'logicexception' => true,
        'badfunctioncallexception' => true,
        'badmethodcallexception' => true,
        'domainexception' => true,
        'invalidargumentexception' => true,
        'lengthexception' => true,
        'outofrangeexception' => true,
        'runtimeexception' => true,
        'outofboundsexception' => true,
        'overflowexception' => true,
        'rangeexception' => true,
        'underflowexception' => true,
        'unexpectedvalueexception' => true,
        'recursiveiteratoriterator' => true,
        'iteratoriterator' => true,
        'filteriterator' => true,
        'recursivefilteriterator' => true,
        'callbackfilteriterator' => true,
        'recursivecallbackfilteriterator' => true,
        'parentiterator' => true,
        'limititerator' => true,
        'cachingiterator' => true,
        'recursivecachingiterator' => true,
        'norewinditerator' => true,
        'appenditerator' => true,
        'infiniteiterator' => true,
        'regexiterator' => true,
        'recursiveregexiterator' => true,
        'emptyiterator' => true,
        'recursivetreeiterator' => true,
        'arrayobject' => true,
        'arrayiterator' => true,
        'recursivearrayiterator' => true,
        'splfileinfo' => true,
        'directoryiterator' => true,
        'filesystemiterator' => true,
        'recursivedirectoryiterator' => true,
        'globiterator' => true,
        'splfileobject' => true,
        'spltempfileobject' => true,
        'spldoublylinkedlist' => true,
        'splqueue' => true,
        'splstack' => true,
        'splheap' => true,
        'splminheap' => true,
        'splmaxheap' => true,
        'splpriorityqueue' => true,
        'splfixedarray' => true,
        'splobjectstorage' => true,
        'multipleiterator' => true,
        'pdoexception' => true,
        'pdo' => true,
        'pdostatement' => true,
        'pdorow' => true,
        'sessionhandler' => true,
        'reflectionexception' => true,
        'reflection' => true,
        'reflectionfunctionabstract' => true,
        'reflectionfunction' => true,
        'reflectiongenerator' => true,
        'reflectionparameter' => true,
        'reflectiontype' => true,
        'reflectionmethod' => true,
        'reflectionclass' => true,
        'reflectionobject' => true,
        'reflectionproperty' => true,
        'reflectionextension' => true,
        'reflectionzendextension' => true,
        '__php_incomplete_class' => true,
        'php_user_filter' => true,
        'directory' => true,
        'assertionerror' => true,
        'simplexmlelement' => true,
        'simplexmliterator' => true,
        'pharexception' => true,
        'phar' => true,
        'phardata' => true,
        'pharfileinfo' => true,
        'xmlreader' => true,
        'xmlwriter' => true,
        'collator' => true,
        'numberformatter' => true,
        'normalizer' => true,
        'locale' => true,
        'messageformatter' => true,
        'intldateformatter' => true,
        'resourcebundle' => true,
        'transliterator' => true,
        'intltimezone' => true,
        'intlcalendar' => true,
        'intlgregoriancalendar' => true,
        'spoofchecker' => true,
        'intlexception' => true,
        'intliterator' => true,
        'intlbreakiterator' => true,
        'intlrulebasedbreakiterator' => true,
        'intlcodepointbreakiterator' => true,
        'intlpartsiterator' => true,
        'uconverter' => true,
        'intlchar' => true,
        'traversable' => true,
        'iteratoraggregate' => true,
        'iterator' => true,
        'arrayaccess' => true,
        'serializable' => true,
        'throwable' => true,
        'datetimeinterface' => true,
        'jsonserializable' => true,
        'recursiveiterator' => true,
        'outeriterator' => true,
        'countable' => true,
        'seekableiterator' => true,
        'splobserver' => true,
        'splsubject' => true,
        'sessionhandlerinterface' => true,
        'sessionidinterface' => true,
        'sessionupdatetimestamphandlerinterface' => true,
        'reflector' => true,
    );

    /** @var Project */
    protected $project;

    protected $hash;
    protected $namespace;
    protected $modifiers;
    protected $properties = array();
    protected $methods = array();
    protected $interfaces = array();
    protected $constants = array();
    protected $traits = array();
    protected $parent;
    protected $file;
    protected $relativeFilePath;
    protected $category = self::CATEGORY_CLASS;
    protected $projectClass = true;
    protected $aliases = array();
    protected $errors = array();
    protected $fromCache = false;

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
        return isset(self::$phpInternalClasses[strtolower($this->name)]);
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

    public function setRelativeFilePath($relativeFilePath)
    {
        $this->relativeFilePath = $relativeFilePath;
    }

    public function getRelativeFilePath()
    {
        return $this->relativeFilePath;
    }

    public function getSourcePath($line = null)
    {
        if (null === $this->relativeFilePath) {
            return '';
        }

        return $this->project->getViewSourceUrl($this->relativeFilePath, $line);
    }

    /**
     * @return Project
     */
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

        foreach ($this->getTraits(true) as $trait) {
            foreach ($trait->getProperties(true) as $name => $property) {
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
        return $this->methods[$name] ?? false;
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

        foreach ($this->getTraits(true) as $trait) {
            foreach ($trait->getMethods(true) as $name => $method) {
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

    public function addTrait($trait)
    {
        $this->traits[$trait] = $trait;
    }

    public function getTraits($deep = false)
    {
        $traits = array();
        foreach ($this->traits as $trait) {
            $traits[] = $this->project->getClass($trait);
        }

        if (false === $deep) {
            return $traits;
        }

        $allTraits = $traits;
        foreach ($traits as $trait) {
            $allTraits = array_merge($allTraits, $trait->getTraits(true));
        }

        if ($parent = $this->getParent()) {
            $allTraits = array_merge($allTraits, $parent->getTraits(true));
        }

        return $allTraits;
    }

    public function setTraits($traits)
    {
        $this->traits = $traits;
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
        $this->category = $boolean ? self::CATEGORY_INTERFACE : self::CATEGORY_CLASS;
    }

    public function isInterface()
    {
        return self::CATEGORY_INTERFACE === $this->category;
    }

    public function setTrait($boolean)
    {
        $this->category = $boolean ? self::CATEGORY_TRAIT : self::CATEGORY_CLASS;
    }

    public function isTrait()
    {
        return self::CATEGORY_TRAIT === $this->category;
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

    public function isFromCache()
    {
        return $this->fromCache;
    }

    public function notFromCache()
    {
        $this->fromCache = false;
    }

    public function toArray()
    {
        return array(
            'name' => $this->name,
            'line' => $this->line,
            'short_desc' => $this->shortDesc,
            'long_desc' => $this->longDesc,
            'hint' => $this->hint,
            'tags' => $this->tags,
            'namespace' => $this->namespace,
            'file' => $this->file,
            'relative_file' => $this->relativeFilePath,
            'hash' => $this->hash,
            'parent' => $this->parent,
            'modifiers' => $this->modifiers,
            'is_trait' => $this->isTrait(),
            'is_interface' => $this->isInterface(),
            'aliases' => $this->aliases,
            'errors' => $this->errors,
            'interfaces' => $this->interfaces,
            'traits' => $this->traits,
            'properties' => array_map(function ($property) {
                return $property->toArray();
            }, $this->properties),
            'methods' => array_map(function ($method) {
                return $method->toArray();
            }, $this->methods),
            'constants' => array_map(function ($constant) {
                return $constant->toArray();
            }, $this->constants),
        );
    }

    public static function fromArray(Project $project, $array)
    {
        $class = new self($array['name'], $array['line']);
        $class->shortDesc = $array['short_desc'];
        $class->longDesc = $array['long_desc'];
        $class->hint = $array['hint'];
        $class->tags = $array['tags'];
        $class->namespace = $array['namespace'];
        $class->hash = $array['hash'];
        $class->file = $array['file'];
        $class->relativeFilePath = $array['relative_file'];
        $class->modifiers = $array['modifiers'];
        $class->fromCache = true;
        if ($array['is_interface']) {
            $class->setInterface(true);
        }
        if ($array['is_trait']) {
            $class->setTrait(true);
        }
        $class->aliases = $array['aliases'];
        $class->errors = $array['errors'];
        $class->parent = $array['parent'];
        $class->interfaces = $array['interfaces'];
        $class->constants = $array['constants'];
        $class->traits = $array['traits'];

        $class->setProject($project);

        foreach ($array['methods'] as $method) {
            $class->addMethod(MethodReflection::fromArray($project, $method));
        }

        foreach ($array['properties'] as $property) {
            $class->addProperty(PropertyReflection::fromArray($project, $property));
        }

        foreach ($array['constants'] as $constant) {
            $class->addConstant(ConstantReflection::fromArray($project, $constant));
        }

        return $class;
    }

    public function getCategoryName()
    {
        return self::$categoryName[$this->category];
    }

    public function sortInterfaces($sort)
    {
        if (is_callable($sort)) {
            uksort($this->interfaces, $sort);
        } else {
            ksort($this->interfaces);
        }
    }
}
