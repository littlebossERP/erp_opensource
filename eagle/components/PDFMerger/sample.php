<?php
include 'PDFMerger.php';

$pdf = new PDFMerger;



$pdf->addPDF(realpath('D:\wamp\www\coomao\link\trackcodepaf\A4\LK052409705CN.pdf'))
	
	->addPDF('D:\wamp\www\coomao\link\trackcodepaf\A4\LK052826088CN.pdf', 'all')
	
	->merge();
?>	
