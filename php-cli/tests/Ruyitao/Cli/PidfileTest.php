<?php
namespace Ruyitao\Cli;

use Ruyitao\Cli\Pidfile;

/**
 * TestCase for Pidfile
 */
class PidfileTest extends \PHPUnit_Framework_TestCase
{

    function testIsPidAlive()
    {
        $pidfile = new Pidfile(__DIR__, 'tmp');
        $this->assertTrue($pidfile->isPidAlive(getmypid()));
    }
}
