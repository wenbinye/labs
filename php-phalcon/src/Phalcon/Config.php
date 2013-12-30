<?php
namespace Phalcon;

class Config implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var array internal data storage
	 */
	private $_d=array();

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param array $data the intial data. Default is null, meaning no initialization.
	 * @param boolean $readOnly whether the list is read-only
	 * @throws CException If data is not null and neither an array nor an iterator.
	 */
	public function __construct($data=null)
	{
		if($data!==null)
			$this->_d = $data;
	}

	/**
	 * Returns an iterator for traversing the items in the list.
	 * This method is required by the interface IteratorAggregate.
	 * @return CMapIterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_d);
	}

	/**
	 * Returns the number of items in the map.
	 * This method is required by Countable interface.
	 * @return integer number of items in the map.
	 */
	public function count()
	{
		return count($this->_d);
	}

	/**
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->_d;
	}

	/**
	 * Merges iterable data into the map.
	 *
	 * Existing elements in the map will be overwritten if their keys are the same as those in the source.
	 * If the merge is recursive, the following algorithm is performed:
	 * <ul>
	 * <li>the map data is saved as $a, and the source data is saved as $b;</li>
	 * <li>if $a and $b both have an array indxed at the same string key, the arrays will be merged using this algorithm;</li>
	 * <li>any integer-indexed elements in $b will be appended to $a and reindexed accordingly;</li>
	 * <li>any string-indexed elements in $b will overwrite elements in $a with the same index;</li>
	 * </ul>
	 *
	 * @param mixed $data the data to be merged with, must be an array or object implementing Traversable
	 * @param boolean $recursive whether the merging should be recursive.
	 *
	 * @throws CException If data is neither an array nor an iterator.
	 */
	public function merge($data)
	{
        foreach( $data as $key => $value ) {
            $this->_d[$key] = $value;
        }
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($key)
	{
		return isset($this->_d[$key]) || array_key_exists($key,$this->_d);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($key)
	{
        if ( isset($this->_d[$key]) ) {
            return is_array($this->_d[$key]) ? new self($this->_d[$key]) : $this->_d[$key];
        } else {
            return null;
        }
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset,$item)
	{
        $this->_d[$offset] = $item;
	}

	/**
	 * Unsets the element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		unset($this->_d[$offset]);
	}

    public function __get($key)
    {
        return $this->offsetGet($key);
    }

    public function __set($offset, $item) 
    {
        $this->_d[$offset] = $item;
    }
}
