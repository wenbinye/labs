<?php
namespace Ruyitao\Db;

class BatchUpdater
{
    const INSERT = 'insert';
    const UPDATE = 'update';
    const INSERT_IGNORE = 'insert_ignore';
    const UPDATE_OR_CREATE = 'update_or_create';

    public $quote = '`';
    private $dbConnection;
    private $table;
    private $primaryKey;
    private $batchSize = 50;
    /**
     * @var array 自动更新时间字段名，例如：
     * <code>
     *   $db_updater->setTimestampColumns(array(
     *     'create' => 'created_at',
     *     'update' => 'updated_at'
     *   ));
     * </code>
     */
    private $timestampColumns;
    private $rows;
    private $isRowUniform;

    public function __construct(\PDO $db, $table, $primaryKey)
    {
        $this->setDbConnetion($db);
        $this->setTable($table);
        $this->setPrimaryKey($primaryKey);
    }
    
    public function getDbConnection()
    {
        return $this->dbConnection;
    }

    public function setDbConnetion($db)
    {
        $this->dbConnection = $db;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        return $this;
    }

    public function getBatchSize()
    {
        return $this->batchSize;
    }

    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
        return $this;
    }

    public function getIsRowUniform()
    {
        return $this->isRowUniform;
    }

    public function setIsRowUniform($isRowUniform)
    {
        $this->isRowUniform = $isRowUniform;
        return $this;
    }

    public function getTimestampColumns()
    {
        return $this->timestampColumns;
    }

    public function setTimestampColumns($columns)
    {
        $this->timestampColumns = $columns;
        return $this;
    }

    public function insert($row)
    {
        return $this->add($row, self::INSERT);
    }

    public function insertIgnore($row)
    {
        return $this->add($row, self::INSERT_IGNORE);
    }
    
    public function update($row)
    {
        return $this->add($row, self::UPDATE);
    }

    public function updateOrCreate($row)
    {
        return $this->add($row, self::UPDATE_OR_CREATE);
    }
    
    public function flush($type=null)
    {
        if ( !empty($this->rows) ) {
            if ( isset($type) ) {
                $this->flushRows($type);
            } else {
                array_map(array($this, 'flushRows'), array_keys($this->rows));
            }
        }
        return $this;
    }

    protected function quote($name)
    {
        return $this->quote . $name . $this->quote;
    }
    
    protected function add($row, $type)
    {
        // insert 不检查 PK，可能需要插入PK为自增ID的记录
        if ( $type != self::INSERT && empty($row[$this->primaryKey]) ) {
            return false;
        }
        $this->rows[$type][] = $row;
        if ( count($this->rows[$type]) > $this->batchSize ) {
            $this->flush($type);
        }
        return true;
    }
    
    protected function flushRows($type)
    {
        if ( empty($this->rows[$type]) ) {
            return;
        }
        $rows = $this->rows[$type];
        switch ( $type ) {
        case self::INSERT:
            $this->insertRows($rows);
            break;
        case self::INSERT_IGNORE:
            list($exists, $new) = $this->checkExistRows($rows);
            if ( !empty($new) ) {
                $this->insertRows($new);
            }
            break;
        case self::UPDATE:
            $this->updateRows($rows);
            break;
        case self::UPDATE_OR_CREATE:
            list($exists, $new) = $this->checkExistRows($rows);
            if ( !empty($exists) ) {
                $this->updateRows($exists);
            }
            if ( !empty($new) ) {
                $this->insertRows($new);
            }
            break;
        default:
            throw new \RuntimeException("should never be here");
        }
        $this->rows[$type] = array();
    }

    protected function getBindingParams($row, $scenario)
    {
        $params = array();
        foreach ( $row as $key => $val ) {
            $params[':'.$key] = $val;
        }
        if ( isset($this->timestampColumns[$scenario]) ) {
            $params[':'.$this->timestampColumns[$scenario]] = date('Y-m-d H:i:s');
        }
        return $params;
    }

    protected function buildInsertSql($row)
    {
        $columns = array_keys($row);
        if ( isset($this->timestampColumns['create']) ) {
            $columns[] = $this->timestampColumns['create'];
        }
        $sql = sprintf('INSERT INTO %s (%s) VALUES(%s)',
                       $this->quote($this->table),
                       implode(',', array_map(array($this, 'quote'), $columns)),
                       implode(',', array_map(function($name) { return ':' . $name; }, $columns)));
        return $sql;
    }

    protected function buildUpdateSql($row)
    {
        $columns = array_keys($row);
        $columns = array_flip($columns);
        unset($columns[$this->primaryKey]);
        $columns = array_keys($columns);
        if ( isset($this->timestampColumns['update']) ) {
            $columns[] = $this->timestampColumns['update'];
        }
        $sql = sprintf('UPDATE %s SET %s WHERE %s=:%s',
                       $this->quote($this->table),
                       implode(', ', array_map(function($name) { return "{$name}=:{$name}"; }, $columns)),
                       $this->quote($this->primaryKey),
                       $this->primaryKey);
        return $sql;
    }

    protected function checkRowUniform($rows)
    {
        $columns = array_keys($rows[0]);
        for ( $i=1,$len=count($rows); $i<$len; $i++ ) {
            if ( $columns != array_keys($rows[$i]) ) {
                return false;
            }
        }
        return true;
    }
    
    protected function insertRows($rows)
    {
        $uniform = ($this->isRowUniform || $this->checkRowUniform($rows));
        if ( $uniform ) {
            $stmt = $this->dbConnection->prepare($this->buildInsertSql($rows[0]));
            foreach ( $rows as $row ) {
                $stmt->execute($this->getBindingParams($row, 'create'));
            }
        } else {
            foreach ( $rows as $row ) {
                $stmt = $this->dbConnection->prepare($this->buildInsertSql($row));
                $stmt->execute($this->getBindingParams($row, 'create'));
            }
        }
    }

    protected function updateRows($rows)
    {
        $uniform = ($this->isRowUniform || $this->checkRowUniform($rows));
        if ( $uniform ) {
            $stmt = $this->dbConnection->prepare($this->buildUpdateSql($rows[0]));
            foreach ( $rows as $row ) {
                $stmt->execute($this->getBindingParams($row, 'update'));
            }
        } else {
            foreach ( $rows as $row ) {
                $stmt = $this->dbConnection->prepare($this->buildUpdateSql($row));
                $stmt->execute($this->getBindingParams($row, 'update'));
            }
        }
    }

    protected function checkExistRows($rows)
    {
        $pk = array();
        foreach ( $rows as $row ) {
            $pk[] = $row[$this->primaryKey];
        }
        $sql = sprintf(
            "SELECT %s FROM %s WHERE %s in (%s)",
            $this->quote($this->primaryKey),
            $this->quote($this->table),
            $this->quote($this->primaryKey),
            substr(str_repeat('?,', count($pk)), 0, -1)
        );
        $stmt = $this->dbConnection->prepare($sql);
        $stmt->execute($pk);
        $exists_pk = $stmt->fetchAll(\PDO::FETCH_GROUP);
        $exists = array();
        $new = array();
        foreach ( $rows as $row ) {
            if ( isset($exists_pk[$row[$this->primaryKey]]) ) {
                $exists[] = $row;
            } else {
                $new[] = $row;
            }
        }
        return array($exists, $new);
    }
}
