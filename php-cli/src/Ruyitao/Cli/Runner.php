<?php
namespace Ruyitao\Cli;

class Runner
{
    private $namespaces;
    private $commands;
    private $script;
    private $options = array(
        'debug' => false
    );

    public function __construct($namespaces=null, $options=null)
    {
        if ( isset($namespaces) ) {
            $this->registerNamespaces($namespaces);
        }
        if ( isset($options) ) {
            $this->setOptions($options);
        }
        $this->registerCommand('help', __NAMESPACE__ . '\Command\Help');
    }

    public function getScript()
    {
        if ( !$this->script ) {
            $this->script = $_SERVER['argv'][0];
        }
        return $this->script;
    }

    public function setScript($script)
    {
        $this->script = $script;
        return $this;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function setOption($name, $value)
    {
        $this->option[$name] = $value;
        return $this;
    }

    public function setOptions($options)
    {
        if ( is_array($options) ) {
            foreach ( $options as $name => $value ) {
                $this->setOption($name, $value);
            }
        }
        return $this;
    }
    
    public function registerNamespaces($namespaces)
    {
        if ( is_array($namespaces) ) {
            foreach ( $namespaces as $namespace => $path ) {
                $this->registerNamespace($namespace, $path);
            }
        }
        return $this;
    }
    
    /**
     * 注册名字空间前缀
     * @param string $namespace command 类名字空间前缀
     * @param string $path command 类路径
     */
    public function registerNamespace($namespace, $path)
    {
        $this->namespaces[$namespace] = $path;
        return $this;
    }

    /**
     * 注册命令对应的类
     * @param string $command command 名
     * @param string $class command 类
     */
    public function registerCommand($command, $class)
    {
        $this->commands[$command] = $class;
        return $this;
    }
    
    public function run()
    {
        $this->getCommand()->run();
    }

    public function getCommand()
    {
        global $argv;
        $argc = count($argv);
        if ( $argc <= 1 ) {
            $this->usage(0);
        }
        $args = array();
        for ( $i=1; $i<$argc; $i++ ) {
            if ( $argv[$i]{0} != '-' ) {
                $args[] = $argv[$i];
            } else {
                break;
            }
        }
        $offset = $i;
        if ( empty($args) ) {
            $this->usage(0);
        }
        $name = array_shift($args);
        $command = $this->loadCommand($name, $args);
        if ( !$command ) {
            $this->usage(1, "Command '$name' not found!\n");
        }
        if ( $command instanceof MultipleCommand ) {
            if ( empty($args) ) {
                $command->usage(1, "Subcommand is required!");
            }
            $action = array_shift($args);
            if ( !$command->hasAction($action) ) {
                $command->usage(1, "Command {$action} not found!");
            }
            $command->setAction($action);
        }
        $command->setArguments($args);
        $getopt = new Getopt($command->getOptions());
        $getopt->parse(array_slice($argv, $offset));
        $command->setGetopt($getopt);
        return $command;
    }

    protected function createCommand($command_class)
    {
        $command = new $command_class($this);
        $command->initialize();
        return $command;
    }
    
    public function loadCommand($name, $args=null)
    {
        if ( isset($this->commands[$name]) ) {
            $command_class = $this->commands[$name];
        } else {
            $class_name = ucfirst($name);
            foreach ( $this->namespaces as $namespace => $path ) {
                $file = "$path/$class_name.php";
                if ( file_exists($file) ) {
                    $command_class = "$namespace\\$class_name";
                    if ( !class_exists($command_class, false) ) {
                        require($file);
                    }
                    break;
                }
            }
        }
        if ( isset($command_class) ) {
            return $this->createCommand($command_class);
        }
    }

    public function loadAllCommands()
    {
        $commands = array();
        foreach ( $this->commands as $name => $command_class ) {
            $commands[] = $this->createCommand($command_class);
        }
        foreach ( $this->namespaces as $namespace => $path ) {
            $dh = opendir($path);
            if ( !$dh ) {
                $this->log("The directory '$path' does not exists!");
            } else {
                while ( ($file=readdir($dh)) !== false ) {
                    if ( strpos($file, '.php') !== false && is_file($path.'/'.$file) ) {
                        $command_class = $namespace . '\\' . substr($file, 0, -4);
                        if ( !class_exists($command_class) ) {
                            require($path.'/'.$file);
                        }
                        $commands[] = $this->createCommand($command_class);
                    }
                }
            }
        }
        $sort = array();
        foreach ( $commands as $command ) {
            $name = $command->getName();
            if ( isset($sort[$name]) ) {
                $this->log(sprintf("Command is seen twice, first is %s, second is %s", get_class($sort[$name]), get_class($command)));
            } else {
                $sort[$name] = $command;
            }
        }
        ksort($sort, SORT_STRING);
        return array_values($sort);
    }
    
    public function usage($exit=null, $desc=null)
    {
        if ( $desc ) {
            echo $desc, "\n\n";
        }
        $this->loadCommand('help')->run();
        if ( isset($exit) ) {
            exit($exit);
        }
    }

    public function log($msg)
    {
        if ( $this->getOption('debug') ) {
            error_log($msg);
        }
    }
}
