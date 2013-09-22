<?php
namespace Php;

use Php\Inspector\TreeNode;
use Php\Inspector\Tree;
use Php\Inspector\Declaration;

class Inspector
{
    public function parseArgv()
    {
        $defs = array(
            'c:' => 'constant:',
            't:' => 'tree:',
            'i:' => 'interface:',
            'd:' => 'declaration:',
            'b:' => 'bootstrap:',
            'h' => 'help',
        );
        $shortopts = '';
        $longopts = array();
        foreach ( $defs as $short => $long ) {
            if ( is_numeric($short) ) {
                $shortopts .= $long;
            } else {
                $shortopts .= $short;
                if ( isset($long) ) {
                    $longopts[] = $long;
                }
            }
        }
        $options = getopt($shortopts, $longopts);
        foreach ( $defs as $short => $long ) {
            $short = rtrim($short, ':');
            if ( isset($options[$short]) ) {
                $options[rtrim($long, ':')] = $options[$short];
            }
        }
        return $options;
    }

    public function dispatch()
    {
        $args = $this->parseArgv();
        if ( isset($args['help']) ) {
            $this->usage();
            return;
        }
        if ( !empty($args['bootstrap']) ) {
            require($args['bootstrap']);
        }
        if ( !empty($args['constant']) ) {
            $this->listConstant($args['constant']);
        } elseif ( !empty($args['tree']) ) {
            $this->listClassTree($args['tree']);
        } elseif ( !empty($args['interface']) ) {
            $this->listInterfaceClasses($args['interface']);
        } elseif ( !empty($args['declaration']) ) {
            $this->showDeclaration($args['declaration']);
        } else {
            $this->usage();
        }
    }

    private function displayTree($tree)
    {
        $render = new Tree\AsciiRender($tree);
        $render->render();
    }
    
    public function listClassTree($match)
    {
        $tree = new Tree;
        $classes = array();
        foreach ( get_declared_classes() as $class_name ) {
            if ( strpos($class_name, $match) !== false ) {
                $node = $tree->find($class_name);
                $class = new \ReflectionClass($class_name);
                $parent_class = $class->getParentClass();
                if ( $parent_class ) {
                    $parent = $tree->find($parent_class->getName());
                    $node->setParent($parent);
                }
            }
        }
        $this->displayTree($tree);
    }

    public function listInterfaceClasses($match)
    {
        $interfaces = array();
        foreach ( get_declared_interfaces() as $name ) {
            if ( strpos($name, $match) !== false ) {
                $interfaces[$name] = 1;
            }
        }
        if ( !empty($interfaces) ) {
            $tree = new Tree;
            $autoId = 1;
            foreach ( get_declared_classes() as $class_name ) {
                $class = new \ReflectionClass($class_name);
                $implements = $class->getInterfaceNames();
                if ( !empty($implements) ) {
                    foreach ( $implements as $interface ) {
                        if ( isset($interfaces[$interface]) ) {
                            $node = $tree->makeNode($autoId++, null, array('name' => $class_name));
                            $node->setParent($tree->find($interface));
                        }
                    }
                }
            }
            $this->displayTree($tree);
        } else {
            echo "No matches!\n";
        }
    }
    
    public function listConstant($match)
    {
        $matches = array();
        $sort = true;
        if ( preg_match('/^[a-z]:/', $match) ) {
            $type = substr($match, 0, 1);
            $match = substr($match, 2);
            if ( $type == 'p' ) {
                foreach ( get_defined_constants() as $name => $val ) {
                    if ( strpos($name, $match) === 0 ) {
                        $matches[$name] = $val;
                    }
                }
            } elseif ( $type == 'c' ) {
                $all = get_defined_constants(true);
                if ( empty($match) ) {
                    echo "Constant categories:\n";
                    echo "\t", implode("\n\t", array_keys($all)), "\n";
                    exit;
                } elseif ( isset($all[$match]) ) {
                    $matches = $all[$match];
                }
            } elseif ( $type == 'a' ) {
                $matches = get_defined_constants();
                $sort = false;
            }
        } else {
            $re = '/' . $match . '/';
            foreach ( get_defined_constants() as $name => $val ) {
                if ( preg_match($re, $name) ) {
                    $matches[$name] = $val;
                }
            }
        }
        if ( !empty($matches) ) {
            if ( $sort ) {
                asort($matches, SORT_NUMERIC);
            }
            echo count($matches) . " constants match:\n";
            $max_len = max(array_map('strlen', array_keys($matches)));
            foreach ( $matches as $name => $val ) {
                printf("\t%-{$max_len}s = %d\n", $name, $val);
            }
        } else {
            echo "No matches!\n";
        }
    }

    public function showDeclaration($class)
    {
        $ret = Declaration::klass($class);
        if ( null === $ret ) {
            die("Class '{$class}' does not exists!");
        }
        echo $ret;
    }
    
    public function usage()
    {
        echo <<<EOF
Usage:

  php-inspector [options]

Options:

    --constant    -c query  List constants
    --tree        -t query  List class hierachy tree
    --interface   -i query  List classes implement the interface
    --declaration -d query  Show declaration of the class 

Examples:

  List constants those match PREG_
    php-inspector -c PREG_

  List constants belong to category pcre:
    php-inspector -c c:pcre

  List all constants categories:
    php-inspector -c c:

  List constants begin with PREG_
    php-inspector -c p:PREG_

  List all constants:
    php-inspector -c a:

  List hierachy tree for all classes those match Phalcon:
    php-inspector -t Phalcon

  List classes implement interfaces those match Phalcon:
    php-inspector -i Phalcon

  Show class declaration:
    php-inspector -d Phalcon\Config

EOF;
    }
}
