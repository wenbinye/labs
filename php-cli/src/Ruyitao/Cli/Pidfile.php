<?php
namespace Ruyitao\Cli;

/**
 * 使用 pid 文件确保单实例进程
 *
 * 使用示例：
 * <code>
 *   $pidfile = new Pidfile(sys_get_temp_dir(), $procName);
 *   if ( $pidfile->isAlreadyRunning() ) {
 *       echo "$procName is running.\n";
 *       exit;
 *   }
 * </code>
 *
 * @package ruyitao.phplib
 */
class Pidfile
{
    private $_file;
    private $_running;
    private $_pid;

    /**
     * 创建 pid 文件
     * @param string $dir Pid 文件目录，必须确保进程有权限创建文件
     * @param string $name Pid 文件名。默认为当前进程 php 文件名
     */
    public function __construct($dir, $name=null)
    {
        if ( null === $name ) {
            $name = basename($_SERVER['PHP_SELF']);
        }
        $this->_file = "$dir/$name.pid";
        if ( file_exists($this->_file) ) {
            $pid = trim(file_get_contents($this->_file));
            if ( $this->isPidAlive($pid) ) {
                $this->_running = true;
                $this->_pid = $pid;
            }
        }

        if ( !$this->_running ) {
            $pid = getmypid();
            if ( false === file_put_contents($this->_file, $pid) ) {
                throw new \RuntimeException(
                    "Cannot write to pid file '{$this->_file}'"
                );
            }
        }
    }

    public function isPidAlive($pid)
    {
        if ( function_exists('posix_kill') ) {
            return posix_kill($pid, 0);
        } else {
            system("kill -0 $pid", $ret);
            return $ret == 0;
        }
    }
    
    public function __destruct()
    {
        if ( (!$this->_running) && file_exists($this->_file) ) {
            unlink($this->_file);
        }
    }

    /**
     * @return bool 是否已经存在其它运行的进程实例
     */
    public function isAlreadyRunning()
    {
        return $this->_running;
    }

    /**
     * @return int|null 其它运行的进程实例 pid
     */
    public function getRunningPid()
    {
        return $this->_pid;
    }
}
