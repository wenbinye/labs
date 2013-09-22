<?php
namespace Php\Inspector\Tree;

use Php\Inspector\Tree;
use Php\Inspector\Tree\AsciiRender;

class AsciiRenderTest extends \PHPUnit_Framework_TestCase
{
    function testRender()
    {
        $tree = new Tree;
        $tree->find('baz');
        $node = $tree->find('bar');
        $node->setParent($tree->find('foo'));
        $tree = new AsciiRender($tree);
        $buff = $tree->render(true);
        $expect = <<<EOF
[-] ROOT
 |- baz
 `-[-] foo
    `- bar

EOF;
        $this->assertEquals($expect, $buff);
    }
}
