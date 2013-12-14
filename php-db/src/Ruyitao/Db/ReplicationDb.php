<?php
namespace Ruyitao\Db;

class ReplicationDb
{
    private $master;
    private $slaves;

    private $readConn;
    private $writeConn;
    
    public function __construct($options)
    {
        if ( !isset($options['master']) || !isset($options['slaves']) ) {
            throw \InvalidArgumentException("Database connection parameter should has master and slaves");
        }
        $this->master = $options['master'];
        $this->slaves = $options['slaves'];
    }

    public function getReadConnection()
    {
        if ( $this->writeConn ) {
            return $this->writeConn;
        }
        if ( !$this->readConn ) {
            $config = $this->getSlaveConfig();
            $this->readConn = $this->createConnection($config);
            if ( $config == $this->master ) {
                $this->writeConn = $this->readConn;
            }
        }
        return $this->readConn;
    }
    
    protected function getSlaveConfig()
    {
        $nOfZero = 0;
        $total = 0;
        $weightArr = array();
        foreach ( $this->slaves as $i => $config ) {
            $w = floatval(isset($config['weight']) ? $config['weight'] : 0);
            if ( $w == 0 ) {
                $nOfZero++;
            }
            $total += $w;
            $weightArr[$i] = $w;
        }
        if ( $total < 1 ) {
            $weight = (1-$total)/$nOfZero;
            foreach ( $weightArr as $i => $w ) {
                if ( $w == 0 ) {
                    $weightArr[$i] = $weight;
                }
            }
        } elseif ( $total > 1 ) {
            foreach ( $weightArr as $i => $w ) {
                $weightArr[$i] = $w/$total;
            }
        }

        $rand = mt_rand()/mt_getrandmax();
        $total = 0;
        $index = count($this->slaves)-1;
        foreach ( $weightArr as $i => $w ) {
            $total += $w;
            if ( $total >= $rand ) {
                $index = $i;
                break;
            }
        }
        $config = $this->slaves[$index];
        unset($config['weight']);
        return array_merge($this->master, $config);
    }

    public function getWriteConnection()
    {
        if ( !$this->writeConn ) {
            $this->writeConn = $this->createConnection($this->options['master']);
        }
        return $this->writeConn;
    }

    protected function createConnection($conf)
    {
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
}
