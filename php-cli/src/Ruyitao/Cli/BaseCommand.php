<?php
namespace Ruyitao\Cli;

/**
 * 定义一个命令行任务
 *
 * 命令行任务调用形式为：
 *  script command arg1 arg2 ... argn -opt1 opt1_val -opt2 ... -optn [optn_val]
 */
abstract class BaseCommand
{
    protected $runner;
    protected $arguments;
    protected $getopt;

    /** 以下是继承命令需要设置的属性 */
    /**
     * @var array 命令行可选参数
     * 每个选项包含：short_opt, long_opt, type, description
     * type 为：Getopt::NO_ARGUMENT, Getopt::REQUIRED_ARGUMENT, Getopt::OPTIONAL_ARGUMENT
     */
    protected $options;
    protected $requiredArguments;
    /**
     * @var string 命令描述
     */
    protected $description;

    public function __construct($runner)
    {
        $this->setRunner($runner);
    }
    
    public function initialize()
    {
    }

    abstract public function run();

    public function getRunner()
    {
        return $this->runner;
    }

    public function setRunner($runner)
    {
        $this->runner = $runner;
        return $this;
    }

    public function getName()
    {
        $parts = explode('\\', get_class($this));
        return lcfirst($parts[count($parts)-1]);
    }
    
    public function getArguments()
    {
        return $this->arguments;
    }
    
    public function setArguments($args)
    {
        $required_args = $this->getRequiredArguments();
        if ( !empty($required_args) ) {
            if ( count($args) < count($required_args) ) {
                $missing_args = array_slice($required_args, count($args));
                $err_desc = "Missing required arguments " . implode(', ', array_map(function($name) {
                            return "'$name'";
                        }, $missing_args));
                $this->usage(1, $err_desc);
            }
        }
        $this->arguments = $args;
        return $this;
    }

    public function getRequiredArguments()
    {
        return $this->requiredArguments;
    }
    
    public function getOptions()
    {
        return $this->options;
    }

    public function getGetopt()
    {
        return $this->getopt;
    }

    public function setGetopt($getopt)
    {
        $this->getopt = $getopt;
        return $this;
    }
    
    public function getParam($name)
    {
        return $this->getopt->getOption($name);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getUsage()
    {
        $usage = $this->getName();
        $args = $this->getRequiredArguments();
        if ( !empty($args) ) {
            $usage .= ' ' . implode(' ', $args);
        }
        $options = $this->getOptions();
        if ( !empty($options) ) {
            $usage .= ' [options]';
        }
        return $usage;
    }
    
    public function usage($exit=null, $desc=null)
    {
        if ( $desc ) {
            echo $desc, "\n\n";
        }
        $this->getRunner()->loadCommand('help')
            ->setCommand($this)
            ->run();
        if ( isset($exit) ) {
            exit($exit);
        }
    }
}
