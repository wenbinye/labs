<?php
namespace Php\Inspector;

class Tree
{
    private $rootId = 'ROOT';
    private $root;
    private $nodes;
    private $nodeFactory;

    public function __construct($nodeFactory=null)
    {
        if ( !isset($nodeFactory) ) {
            $nodeFactory = function($tree, $id, $parent, $data) {
                return new TreeNode($tree, $id, $parent, $data);
            };
        }
        $this->nodeFactory = $nodeFactory;
    }

    public function setRootId($id)
    {
        $this->rootId = $id;
    }

    public function getRootId()
    {
        return $this->rootId;
    }

    public function getNodeFactory()
    {
        return $this->nodeFactory;
    }

    public function setNodeFactory($factory)
    {
        $this->nodeFactory = $factory;
    }

    public function makeNode($id, $parent, $data=array())
    {
        $factory = $this->nodeFactory;
        return $this->nodes[$id] = $factory($this, $id, $parent, $data);
    }
    
    public function find($id)
    {
        if ( !isset($this->nodes[$id]) ) {
            return $this->makeNode($id, $this->getRoot());
        }
        return $this->nodes[$id];
    }

    public function getRoot()
    {
        if ( !isset($this->root) ) {
            $this->root = $this->makeNode($this->rootId, false);
        }
        return $this->root;
    }
}
