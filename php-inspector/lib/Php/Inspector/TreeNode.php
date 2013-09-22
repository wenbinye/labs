<?php
namespace Php\Inspector;

class TreeNode
{
    protected $tree;
    protected $children;
    protected $parent;
    protected $id;
    protected $attributes;

    public function __construct($tree, $id, $parent, $attributes=array(), $children=array())
    {
        $this->tree = $tree;
        $this->id = $id;
        $this->children = $children;
        $this->attributes = $attributes;
        $this->parent = $parent;
        if ( $parent ) {
            $parent->addChild($this);
        }
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getName()
    {
        return isset($this->attributes['name']) ? $this->attributes['name'] : $this->getId();
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default=null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }
    
    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setAttribute($name, $value)
    {
        if ( !isset($value) ) {
            unset($this->attributes[$name]);
        } else {
            $this->attributes[$name] = $value;
        }
        return $this;
    }
    
    public function setParent($parent)
    {
        if ( $this->parent ) {
            $this->parent->removeChild($this);
        }
        $parent->addChild($this);
        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setChildren($children)
    {
        $this->children = array();
        $this->addChildren($children);
        return $this;
    }
    
    public function addChildren($nodes)
    {
        foreach ( $nodes as $node ) {
            $this->addChild($node);
        }
        return $this;
    }

    public function addChild(TreeNode $node)
    {
        $this->children[$node->getId()] = $node;
        $node->parent = $this;
        return $this;
    }

    public function removeChild(TreeNode $node)
    {
        unset($this->children[$node->getId()]);
        return $this;
    }
    
    public function hasChildren()
    {
        return !empty($this->children);
    }
    
    public function getChildren()
    {
        return $this->children;
    }
}
