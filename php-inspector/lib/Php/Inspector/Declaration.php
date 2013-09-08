<?php
namespace Php\Inspector;

class Declaration
{
    public static function constant($name)
    {
        if ( !defined($name) ) {
            return;
        }
        return 'define(\'' . $name . '\', ' . var_export(constant($name), true) . ');' . PHP_EOL;
    }

    public static function func($function)
    {
        if ( !function_exists($function) ) {
            return;
        }
        $refl = new \ReflectionFunction($function);
        $php = 'function ' . $refl->getName() . '(';
        foreach ($refl->getParameters() as $i => $parameter) {
            if ($i >= 1) {
                $php .= ', ';
            }
            if ($typehint = $parameter->getClass()) {
                $php .= $typehint->getName() . ' ';
            }
            $php .= '$' . $parameter->getName();
            if ($parameter->isDefaultValueAvailable()) {
                $php .= ' = ' . $parameter->getDefaultValue();
            }
        }
        $php .= ') {}' . PHP_EOL;
        return $php;
    }

    public static function klass($class)
    {
        if ( !class_exists($class) ) {
            return;
        }
        $refl = new \ReflectionClass($class);
        $indent = '    ';
        $php = 'class ' . $refl->getName();
        if ($parent = $refl->getParentClass()) {
            $php .= ' extends ' . $parent->getName();
        }
        $php .= PHP_EOL . '{' . PHP_EOL;
        foreach ($refl->getProperties() as $property) {
            $php .= $indent . '$' . $property->getName() . ';' . PHP_EOL;
        }
        foreach ($refl->getMethods() as $method) {
            if ($method->isPublic()) {
                if ($method->getDocComment()) {
                    $php .= $indent . $method->getDocComment() . PHP_EOL;                
                }
                $php .= $indent . 'public function ';
                if ($method->returnsReference()) {
                    $php .= '&';
                }
                $php .= $method->getName() . '(';
                foreach ($method->getParameters() as $i => $parameter) {
                    if ($i >= 1) {
                        $php .= ', ';
                    }
                    if ($parameter->isArray()) {
                        $php .= 'array ';
                    }
                    if ($typehint = $parameter->getClass()) {
                        $php .= $typehint->getName() . ' ';
                    }
                    $php .= '$' . $parameter->getName();
                    if ($parameter->isDefaultValueAvailable()) {
                        $php .= ' = ' . $parameter->getDefaultValue();
                    }
                }
                $php .= ') {}' . PHP_EOL;
            }
        }
        $php .= '}' . PHP_EOL;
        return $php;
    }
}
