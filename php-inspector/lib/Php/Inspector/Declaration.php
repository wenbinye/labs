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
        
        $classAttributes = "";
        if ($refl->isInterface()) {
            $classAttributes .= "interface ";
        } else {
            if ($refl->isFinal()) {
                $classAttributes .= "final ";
            }
            if ($refl->isAbstract()) {
                $classAttributes .= "abstract ";
            }
            $classAttributes .= "class ";
        }
        $php = $classAttributes . $refl->getName();
        if ($parent = $refl->getParentClass()) {
            $php .= ' extends ' . $parent->getName();
        }
        
        if ( $interfaces = $refl->getInterfaceNames() ) {
            $php .= " implements\n" . $indent . join(",\n".$indent, $interfaces);
        }
        $php .= PHP_EOL . '{' . PHP_EOL;

        /* constants */
        if ( $constants = $refl->getConstants() ) {
            foreach ($constants as $k => $v) {
                $php .= $indent . "const " . $k . " = " . $v . ";" . PHP_EOL;
            }
            $php .= PHP_EOL;
        }

        if ( $properties = $refl->getProperties() ) {
            $count = 0;
            foreach ( $properties as $p) {
                if ( $p->isPublic() ) {
                    $count++;
                    $php .= $indent . ($p->isStatic() ? 'static ' : '' )
                        . 'public $' . $p->getName() . ';' . PHP_EOL;
                }
            }
            if ( $count > 0 )
                $php .= PHP_EOL;
        }
        
        foreach ($refl->getMethods() as $method) {
            if ($method->isPublic()) {
                if ($method->getDocComment()) {
                    $php .= $indent . $method->getDocComment() . PHP_EOL;                
                }
                $php .= $indent . ($method->isStatic() ? 'static ' : '') . 'public function ';
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
                        $php .= '=' . str_replace("\n", " ", var_export($parameter->getDefaultValue(), true));
                    }
                }
                $php .= ') {}' . PHP_EOL;
            }
        }
        $php .= '}' . PHP_EOL;
        return $php;
    }
}
