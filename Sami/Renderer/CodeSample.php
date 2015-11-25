<?php

namespace Sami\Renderer;

use Sami\Reflection\ClassReflection;
use Sami\Reflection\MethodReflection;
use Sami\Reflection\SubParamReflection;

class CodeSample
{
    private $method;
    private $class;

    public function __construct(ClassReflection $class, MethodReflection $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    public function render()
    {
        $content = $args = '';

        $params = $this->method->getParameters();
        $count = 0;

        foreach ($params as $param) {
            ++$count;

            if (!empty($subParams = $param->getSubParams())) {
                $content .= sprintf("$%s = [\n", $param->getName());
                $longestName = $param->longestPropertyName() + 1;
                foreach ($subParams as $subParam) {
                    $content .= $this->indent(
                        sprintf("'%-".$longestName."s => %s,\n", $subParam->getName() . "'", $this->renderSubParam($subParam, 2)),
                        1
                    );
                }
                $args = sprintf("$%s", $param->getName());
                $content .= '];' . PHP_EOL;
            } else {
                $args .= $this->exampleValue($param->getHint(), $param->getName());
            }

            if ($count < count($params)) {
                $args .= ', ';
            }
        }

        $response = '';

        $hints = $this->method->getHint();

        if (count($hints) > 1) {
            $response = '$response = ';
        } elseif (isset($hints[0])) {
            $matches = [];
            preg_match('#(?:.+\\\)?(\w+)#', $hints[0]->getName(), $matches);
            if (isset($matches[1]) && $matches[1] !== 'void') {
                $response = sprintf("$%s = ", lcfirst($matches[1]));
            }
        }

        $content .= sprintf(
            "\n%s\$%s->%s(%s);", $response, lcfirst($this->class->getShortName()), $this->method->getName(), $args
        );

        return $content;
    }

    private function exampleValue($type, $name)
    {
        switch ($type) {
            case 'string':
                $content = sprintf("'{%s}'", $name);
                break;
            case 'bool':
            case 'boolean':
                $content = 'true';
                break;
            case 'integer':
            case 'int':
            case 'numeric':
            case 'float':
                $content = '100';
                break;
            case 'array':
                $content = '[]';
                break;
            default:
                $content = sprintf('$'."%s", $name);
                break;
        }

        return $content;
    }

    private function renderSubParam(SubParamReflection $subParam, $indent)
    {
        $name = $subParam->getName();
        $type = $subParam->getType();

        $content = '';

        switch ($type) {
            default:
                $content = $this->exampleValue($type, $name);
                break;
            case 'array':
                $content .= '[' . PHP_EOL;
                for ($i = 1; $i <= 2; $i++) {
                    $content .= $this->indent(
                        $this->renderSubParam($subParam->getItemSchema(), $indent+1) . ',' . PHP_EOL,
                        $indent
                    );
                }
                $content .= $this->indent(']', $indent-1);
                break;
            case 'object':
                if (strpos(strtolower($name), 'metadata') !== false) {
                    $content .= '[' . PHP_EOL;
                    $content .= $this->indent("'{key1}' => '{val1}',\n", $indent);
                    $content .= $this->indent("'{key2}' => '{val1}',\n", $indent);
                    $content .= $this->indent(']', $indent-1);
                    break;
                }
                if (!empty($properties = $subParam->getProperties())) {
                    $content .= '[' . PHP_EOL;
                    $longestName = $subParam->longestPropertyName() + 1;
                    foreach ($properties as $property) {
                        $content .= $this->indent(
                            sprintf("'%-".$longestName."s => %s,\n", $property->getName() . "'", $this->renderSubParam($property, $indent+1)),
                            $indent
                        );
                    }
                    $content .= $this->indent(']', $indent-1);
                } else {
                    $content .= '[]';
                }
                break;
        }

        return $content;
    }

    private function indent($content, $indent)
    {
        return str_repeat('&nbsp;', $indent * 3) . $content;
    }
}