<?php
namespace fpdidev;

use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;
use PhpParser\Node\Scalar\String;

class TcpdfResolver extends NodeVisitorAbstract
{
    public function leaveNode(Node $node) {
        if ($node instanceof String) {
            return new String(preg_replace('/^TCPDF/', '\TCPDF', $node->value));
        }
    }
}
