<?php
namespace Ruyitao\Db;

use Ruyitao\Db\BatchUpdater;
/**
 * TestCase for BatchUpdater
 */
class BatchUpdaterTest extends \PHPUnit_Framework_TestCase
{
    function setUp()
    {
        MockPdoStatement::clear();
        $this->o = new BatchUpdater(new MockPdo(''), 'test', 'id');
        $this->o->setTimestampColumns(array(
            'update' => 'gmt_modified',
            'create' => 'gmt_create'
        ));
    }
    
    function testInsert()
    {
        $this->o->insert(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals(count($stmts), 1);
        $this->assertEquals($stmts[0]->sql, 'INSERT INTO `test` (`id`,`name`,`gmt_create`) VALUES(:id,:name,:gmt_create)');
    }

    function testUpdate()
    {
        $this->o->update(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
    }

    function testUpdateOrCreateInsert()
    {
        $this->o->updateOrCreate(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals(count($stmts), 2);
        $this->assertEquals($stmts[0]->sql, 'SELECT `id` FROM `test` WHERE `id` in (?)');
        $this->assertEquals($stmts[1]->sql, 'INSERT INTO `test` (`id`,`name`,`gmt_create`) VALUES(:id,:name,:gmt_create)');
    }

    function testUpdateOrCreateUpdate()
    {
        MockPdoStatement::setQueryResult('SELECT `id` FROM `test` WHERE `id` in (?)', array(1), array(
            array( 'id' => 1 )
        ));
        $this->o->updateOrCreate(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals(count($stmts), 2);
        $this->assertEquals($stmts[0]->sql, 'SELECT `id` FROM `test` WHERE `id` in (?)');
        $this->assertEquals($stmts[1]->sql, 'UPDATE `test` SET name=:name, gmt_modified=:gmt_modified WHERE `id`=:id');
    }
    
    function testInsertIngoreInsert()
    {
        $this->o->insertIgnore(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals(count($stmts), 2);
        $this->assertEquals($stmts[0]->sql, 'SELECT `id` FROM `test` WHERE `id` in (?)');
        $this->assertEquals($stmts[1]->sql, 'INSERT INTO `test` (`id`,`name`,`gmt_create`) VALUES(:id,:name,:gmt_create)');
    }

    function testInsertIngoreIgnore()
    {
        MockPdoStatement::setQueryResult('SELECT `id` FROM `test` WHERE `id` in (?)', array(1), array(
            array( 'id' => 1 )
        ));
        $this->o->insertIgnore(array('id'=>1, 'name'=>'foo'));
        $this->o->flush();
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals(count($stmts), 1);
        $stmts = $this->o->getDbConnection()->statements;
        $this->assertEquals($stmts[0]->sql, 'SELECT `id` FROM `test` WHERE `id` in (?)');
    }
}

class MockPdo extends \PDO
{
    public $statements;
    
    public function __construct($dsn, $user=null, $pass=null, $options=null)
    {
    }
    
    public function prepare($sql, $options=array())
    {
        return $this->statements[] = new MockPdoStatement($sql);
    }
}

class MockPdoStatement
{
    public static $results = array();

    public $sql;
    public $params;
    private $cursor = 0;

    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    public function getKey()
    {
        return md5(strtolower($this->sql) . isset($this->params[0]) ? serialize($this->params[0]) : '');
    }
    
    public function execute($params)
    {
        $this->params[] = $params;
    }

    public function fetchAll($fetch_mode=null)
    {
        $key = $this->getKey();
        if ( isset(self::$results[$key]) ) {
            $rows = self::$results[$key];
            if ( $fetch_mode & \PDO::FETCH_GROUP ) {
                $ret = array();
                foreach ( $rows as $row ) {
                    $val = array_values($row);
                    $ret[$val[0]][] = $val;
                }
                return $ret;
            } else {
                return array_map(function($row) use($fetch_mode){
                        return $this->fetchFor($row, $fetch_mode);
                    }, $rows);
            }
        } else {
            return array();
        }
    }

    public function fetch($fetch_mode=null)
    {
        $key = $this->getKey();
        $ret = isset(self::$results[$key][$this->cursor]) ? $this->fetchFor(self::$results[$key][$this->cursor], $fetch_mode): null;
        $this->cursor++;
        return $ret;
    }

    public function fetchFor($data, $fetch_mode)
    {
        if ( empty($data) || !isset($fetch_mode) || $fetch_mode == \PDO::FETCH_ASSOC ) {
            return $data;
        }
        switch( $fetch_mode ) {
        case \PDO::FETCH_NUM:
            return array_values($data);
        case \PDO::FETCH_BOTH:
            return array_merge($data, array_values($data));
        }
    }
    
    public static function setQueryResult($sql, $params, $rows)
    {
        $stmt = new self($sql);
        $stmt->execute($params);
        self::$results[$stmt->getKey()] = $rows;
    }
    
    public static function clear()
    {
        self::$results = array();
    }
}

