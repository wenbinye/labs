<?php
use Ruyitao\Cli\BaseCommand;

class Say extends BaseCommand
{
    protected $description = 'Print greetting message';
    
    public function run()
    {
        echo "Hello, ".(empty($this->arguments) ? 'world' : $this->arguments[0]) ."!\n";
    }
}
