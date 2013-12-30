<?php
namespace Phalcon;

/**
 * phalcon 兼容 filter 实现
 * @see http://docs.phalconphp.com/en/latest/reference/filter.html
 */
class Filter
{
    protected static $FILTERS = array(
        'email' => FILTER_SANITIZE_EMAIL,
        'int' => FILTER_SANITIZE_NUMBER_INT,
        'float' => FILTER_SANITIZE_NUMBER_FLOAT,
        'url' => FILTER_SANITIZE_URL
    );
    
    public function sanitize($value, $filter)
    {
        if ( isset(self::$FILTERS[$filter]) ) {
            return filter_var($value, self::$FILTERS[$filter]);
        } elseif ( method_exists($this, $filter) ) {
            return $this->$filter($value);
        }
    }

    public function trim($value)
    {
        return trim($value);
    }
}
