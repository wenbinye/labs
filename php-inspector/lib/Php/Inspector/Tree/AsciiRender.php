<?php
namespace Php\Inspector\Tree;

class AsciiRender extends BaseRender
{
    public function render($return=false)
    {
        if ( $return ) {
            ob_start();
        }
        $this->rendInternal($this->tree, 0, '');
        if ( $return ) {
            return ob_get_clean();
        }
    }

    private function rendInternal($node, $level, $indent, $isLast=false)
    {
        // var_export(array($node->getId(), $level, $indent, $isLast));
        $children = array_values($node->getChildren());
        $num = count($children)-1;
        echo $indent, ($level > 0 ? ' ' . ($isLast ? '`' : '|') . '-' : ''),
            ($num==-1 ? ' ' : '[-] '), $node, "\n";
        if ( $level > 0 ) {
            $indent .= ($isLast ? '   ' : ' | ');
        }
        foreach ( $children as $i => $child ) {
            $this->rendInternal($child, $level+1, $indent, $i==$num);
        }
    }
}
