<?php
namespace fpdidev;

use hanneskod\classtools\Transformer\Writer;
use hanneskod\classtools\Transformer\Action\NodeStripper;
use hanneskod\classtools\Transformer\Action\CommentStripper;
use hanneskod\classtools\Transformer\Action\NamespaceWrapper;
use hanneskod\classtools\Transformer\Action\NameResolver;
use hanneskod\classtools\Transformer\Action\NamespaceCrawler;

class FpdiWriter extends Writer
{
    public function __construct(NodeTraverser $traverser = null)
    {
        parent::__construct($traverser);
        $this->apply(new NodeStripper('Expr_Include'));
        $this->apply(new CommentStripper);
        $this->apply(new NamespaceWrapper('fpdi'));
        $this->apply(new NameResolver);
        $this->apply(new NamespaceCrawler(['', '\fpdf'], ['\fpdi']));
        $this->apply(new TcpdfResolver());
    }
}
