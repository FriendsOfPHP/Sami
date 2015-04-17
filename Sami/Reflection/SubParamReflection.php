<?php

namespace Sami\Reflection;

class SubParamReflection extends Reflection
{
    private $description;
    private $type;
    private $required;
    private $default;
    private $properties;
    private $itemSchema;

    public function __construct(array $data, $name)
    {
        if (isset($data['description'])) {
            $this->description = $data['description'];
        }

        if (isset($data['type'])) {
            $this->type = $data['type'];
        }

        if (isset($data['required'])) {
            $this->required = $data['required'];
        }

        if (isset($data['default'])) {
            $this->default = $data['default'];
        }

        if (isset($data['properties'])) {
            foreach ($data['properties'] as $propName => $prop) {

                if ($name == 'metadata') {
                    $this->itemSchema = new SubParamReflection($data['properties'], '');
                } else {
                    $this->properties[$propName] = new SubParamReflection($prop, $propName);
                }
            }
        }

        if (isset($data['items'])) {
            $this->itemSchema = new SubParamReflection($data['items'], $name . '[]');
        }
    }

    public function getClass()
    {}

    public function getDescription()
    {
        return $this->description;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getRequired()
    {
        return $this->required;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getItemSchema()
    {
        return $this->itemSchema;
    }

    public function getProperties()
    {
        return $this->properties;
    }
}