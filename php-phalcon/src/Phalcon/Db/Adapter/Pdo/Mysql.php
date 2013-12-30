<?php
namespace Phalcon\Db\Adapter\Pdo;

class Mysql
{
    protected $options;
    protected $pdo;
    
    public function __construct($options)
    {
        $this->options = $options;
    }

    public function getInternalHandler()
    {
        if ( !isset($this->pdo) ) {
            $this->pdo = $this->connect();
        }
        return $this->pdo;
    }
    
    protected function connect()
    {
        $conf = $this->options;
        $dsn = 'mysql:';
        if ( isset($conf['host']) ) {
            $dsn .= 'host='.$conf['host'].';';
        }
        if ( isset($conf['dbname']) ) {
            $dsn .= 'dbname='.$conf['dbname'].';';
        }
        return new \PDO($dsn, isset($conf['username']) ? $conf['username'] : null,
                        isset($conf['password']) ? $conf['password'] : null,
                        isset($conf['options']) ? $conf['options'] : null);
    }

    public function fetchOne($sql, $fetchMode=Db::FETCH_ASSOC, $bindParams=null, $bindTypes=null)
    {
        $stmt = $this->getInternalHandler()->prepare($sql);
        $stmt->execute($bindParams);
        return $stmt->fetch($fetchMode);
    }

    public function fetchAll($sql, $fetchMode=Db::FETCH_ASSOC, $bindParams=null, $bindTypes=null)
    {
        $stmt = $this->getInternalHandler()->prepare($sql);
        $stmt->execute($bindParams);
        return $stmt->fetchAll($fetchMode);
    }

    public function prepare($sql)
    {
        return $this->getInternalHandler()->prepare($sql);
    }

    public function execute($sql, $bindParams=null, $bindTypes=null)
    {
        $stmt = $this->getInternalHandler()->prepare($sql);
        return $stmt->execute($bindParams);
    }

    public function query($sql, $bindParams=null, $bindTypes=null)
    {
        $stmt = $this->getInternalHandler()->prepare($sql);
        $stmt->execute($bindParams);
        return $stmt;
    }

    public function lastInsertId($sequenceName=null)
    {
        return $this->getInternalHandler()->lastInsertId($sequenceName);
    }

    public function quote($field)
    {
        return '`'.$field.'`';
    }
    
    public function insert($table, $values, $fields=null)
    {
        $sql = sprintf("INSERT %s %sVALUES(%s)", $this->quote($table),
                       isset($fields) ? '('.implode(',', array_map(array($this, 'quote'), $fields)).') ' : '',
                       substr(str_repeat('?,', count($values)), 0, -1));
        return $this->execute($sql, $values);
    }
}
