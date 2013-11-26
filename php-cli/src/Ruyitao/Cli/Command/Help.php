<?php
namespace Ruyitao\Cli\Command;

use Ruyitao\Cli\BaseCommand;
use Ruyitao\Cli\MultipleCommand;

class Help extends BaseCommand
{
    protected $command;
    protected $description = 'show this help';

    public function run()
    {
        if ( isset($this->command) ) {
            $this->showCommandUsage();
        } elseif ( empty($this->arguments) ) {
            $this->showUsage();
        } else {
            $name = array_shift($this->arguments);
            $this->command = $this->getRunner()->loadCommand($name);
            if ( !$this->command ) {
                echo "Command {$name} not found\n\n";
                $this->showUsage();
            } else {
                if ( ($this->command instanceof MultipleCommand)
                     && isset($this->arguments[0]) ) {
                    $this->command->setAction($this->arguments[0]);
                }
                $this->showCommandUsage();
            }
        }
    }
    
    public function getCommand()
    {
        return $this->command;
    }

    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }

    protected function align($lines, $indent="\t")
    {
        $len = max(array_map(function($line) { return strlen($line[0]); }, $lines));
        foreach ( $lines as $line ) {
            printf("\t%-{$len}s    %s\n", $line[0], $line[1]);
        }
    }
    
    public function showUsage()
    {
        $script = $this->getRunner()->getScript();
        echo "Usage:\n\t{$script} command [options]\n";
    
        echo "\nAvailable commands:\n";
        $this->align(array_map(function($command) {
                    return array($command->getName(), $command->getDescription());
                }, $this->getRunner()->loadAllCommands()));
    }

    protected function showCommandUsage()
    {
        $script = $this->getRunner()->getScript();
        $command = $this->command;
        echo "Usage:\n\t{$script} ", $command->getUsage(), "\n";
        if ( $command instanceof MultipleCommand && !$command->getAction() ) {
            echo "\nAvailable subcommands:\n";
            $this->align(array_map(function($action) use($command){
                        return array($action, $command->getActionDescription($action));
                    }, $command->getAllActions()));
        } else {
            $options = $command->getOptions();
            if ( !empty($options) ) {
                echo "\nAvaliable options:\n";
                $this->align(array_map(function($option) {
                            return array(
                                sprintf('-%s, --%s', $option[0], $option[1]),
                                isset($option[3]) ? $option[3] : ''
                            );
                        }, $options));
            }
        }
    }
}
