<?php
namespace Php\Inspector\Tree;

use Php\Inspector\Tree;
use Php\Inspector\TreeNode;

abstract class BaseRender
{
    protected $tree;

    public function __construct($tree)
    {
        $this->setTree($tree);
    }
    
    public function setTree($tree)
    {
        if ( $tree instanceof Tree ) {
            $tree = $tree->getRoot();
        }
        if ( !($tree instanceof TreeNode) ) {
            throw new \InvalidArgumentException("Parameter 'tree' is not a Php\Inspector\Tree object");
        }
        $this->tree = $tree;
    }

    public function getTree()
    {
        return $this->tree;
    }
    
    abstract public function render();
}
