<?php
namespace Php\Inspector;

class TreeNode
{
    private static $NODES;
    private static $ROOT;
    
    private $children;
    private $parent;
    private $id;
    private $data;

    public function __construct($id, $parent=null, $children=array(), $data=null)
    {
        $this->children = $children;
        $this->id = $id;
        $this->data = $data;
        if ( !isset($parent) ) {
            $parent = self::getRoot();
        }
        $this->parent = $parent;
        if ( $parent ) {
            $parent->addChild($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function setParent($parent)
    {
        if ( $this->parent ) {
            $this->parent->removeChild($this);
        }
        $this->parent = $parent;
        $parent->addChild($this);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setChildren($children)
    {
        $this->children = array();
        $this->addChildren($children);
    }
    
    public function addChildren($nodes)
    {
        foreach ( $nodes as $node ) {
            $this->addChild($node);
        }
    }

    public function addChild(TreeNode $node)
    {
        $this->children[$node->getId()] = $node;
    }

    public function removeChild(TreeNode $node)
    {
        unset($this->children[$node->getId()]);
    }
    
    public function hasChildren()
    {
        return !empty($this->children);
    }
    
    public function getChildren()
    {
        return $this->children;
    }

    public static function getRoot()
    {
        if ( !isset(self::$ROOT) ) {
            self::$ROOT = new self('ROOT', false);
        }
        return self::$ROOT;
    }
    
    public static function find($id)
    {
        if ( !isset(self::$NODES[$id]) ) {
            self::$NODES[$id] = new self($id);
        }
        return self::$NODES[$id];
    }
}
