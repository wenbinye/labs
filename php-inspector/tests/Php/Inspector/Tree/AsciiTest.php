<?php
namespace Php\Inspector\Tree;

use Php\Inspector\TreeNode;
use Php\Inspector\Tree\Ascii;

class AsciiTest extends \PHPUnit_Framework_TestCase
{
    function testRender()
    {
        TreeNode::find('baz');
        $node = TreeNode::find('bar');
        $node->setParent(TreeNode::find('foo'));
        $tree = new Ascii(TreeNode::getRoot());
        $buff = $tree->render(true);
        echo $buff, "\n";
        $expect = <<<EOF
[-] ROOT
 |- baz
 `-[-] foo
    `- bar

EOF;
        $this->assertEquals($expect, $buff);
    }
}
