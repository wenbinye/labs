<?php
namespace Ruyitao\Cli;

abstract class MultipleCommand extends BaseCommand
{
    protected $action;
    protected $actionDescriptions;

    public function run()
    {
        if ( !isset($this->action) ) {
            $this->showHelp(1);
        }
        call_user_func(array($this, $this->action. 'Action'));
    }
    
    public function getOptions()
    {
        if ( isset($this->action, $this->options[$this->action]) ) {
            return $this->options[$this->action];
        }
    }

    public function getRequiredArguments()
    {
        if ( isset($this->action, $this->requiredArguments[$this->action]) ) {
            return $this->requiredArguments[$this->action];
        }
    }

    public function getUsage()
    {
        if ( !$this->action ) {
            return $this->getName() . ' subcommand';
        } else {
            $usage = $this->getName() . ' ' . $this->action;
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
    }
    
    public function hasAction($name)
    {
        return method_exists($this, $name . 'Action');
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($name)
    {
        if ( $this->hasAction($name) ) {
            $this->action = $name;
        }
        return $this;
    }

    public function getAllActions()
    {
        $excludes = array('get', 'set', 'has');
        $actions = array();
        foreach ( get_class_methods($this) as $name ) {
            if ( preg_match('/^(\w+)Action$/', $name, $match) && !in_array($match[1], $excludes) ) {
                $actions[] = $match[1];
            }
        }
        return $actions;
    }

    public function getActionDescription($name)
    {
        return isset($this->actionDescriptions[$name]) ? $this->actionDescriptions[$name] : '';
    }
}
