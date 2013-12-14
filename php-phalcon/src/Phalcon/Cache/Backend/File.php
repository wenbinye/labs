<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;

class File extends Backend
{
	public $directoryLevel=0;
    private $_cacheDir;
	private $_gcProbability=100;
    private $_gced;

    public function setOption($key, $val)
    {
        if ( $key == 'cacheDir' ) {
            $dir = $val;
            if ( !is_dir($dir) && !mkdir($dir, 0777, true) ) {
                die("Cannot create cache directory '{$dir}'");
            }
            $this->_cacheDir = $val;
        } else {
            parent::setOption($key, $val);
        }
    }

    protected function setValue($key, $value, $lifetime)
    {
		if(!$this->_gced && mt_rand(0,1000000)<$this->_gcProbability)
		{
			$this->gc();
			$this->_gced=true;
		}
		if($lifetime<=0)
			$lifetime=31536000; // 1 year
		$lifetime+=time();

		$cacheFile=$this->getCacheFile($key);
		if($this->directoryLevel>0)
			@mkdir(dirname($cacheFile),0777,true);
		if(@file_put_contents($cacheFile,$value,LOCK_EX)!==false)
		{
			@chmod($cacheFile,0777);
			return @touch($cacheFile,$lifetime);
		}
		else
			return false;
    }

	protected function getCacheFile($key)
	{
		if($this->directoryLevel>0)
		{
			$base=$this->_cacheDir;
			for($i=0;$i<$this->directoryLevel;++$i)
			{
				if(($prefix=substr($key,$i+$i,2))!==false)
					$base.=DIRECTORY_SEPARATOR.$prefix;
			}
			return $base.DIRECTORY_SEPARATOR.$key;
		}
		else
			return $this->_cacheDir.DIRECTORY_SEPARATOR.$key;
	}

    protected function getValue($key)
	{
		$cacheFile=$this->getCacheFile($key);
		if(($time=@filemtime($cacheFile))>time())
			return @file_get_contents($cacheFile);
		elseif($time>0)
			@unlink($cacheFile);
		return false;
	}

    public function delete($key)
    {
		$cacheFile=$this->getCacheFile($key);
		return @unlink($cacheFile);
    }

	public function gc($expiredOnly=true,$path=null)
	{
		if($path===null)
			$path=$this->_cacheDir;
		if(($handle=opendir($path))===false)
			return;
		while(($file=readdir($handle))!==false)
		{
			if($file[0]==='.')
				continue;
			$fullPath=$path.DIRECTORY_SEPARATOR.$file;
			if(is_dir($fullPath))
				$this->gc($expiredOnly,$fullPath);
			elseif($expiredOnly && @filemtime($fullPath)<time() || !$expiredOnly)
				@unlink($fullPath);
		}
		closedir($handle);
	}
}
