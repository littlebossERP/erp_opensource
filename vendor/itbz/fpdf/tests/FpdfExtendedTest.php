<?php
namespace fpdf;

require_once __DIR__ . "/../src/fpdf/FPDF.php";
require_once __DIR__ . "/../src/fpdf/FPDF/EXTENDED.php";

class FpdfExtendedTest extends \PHPUnit_Framework_TestCase
{

    function testPagesAdded()
    {
        $pdf = new FPDF_EXTENDED();
        $pdf->AddPage();
        $this->assertSame(1, $pdf->PagesAdded());
        $pdf->AddPage();
        $this->assertSame(2, $pdf->PagesAdded());
    }


    function testTotalPagesNo()
    {
        $pdf = new FPDF_EXTENDED();
        $pdf->AliasNbPages('FOOBAR');
        $this->assertSame('FOOBAR', $pdf->TotalPagesNo());
    }


    function testPaginationStr()
    {
        $pdf = new FPDF_EXTENDED();
        $pdf->AliasNbPages('FOOBAR');
        $pdf->AddPage();
        $this->assertSame('1/FOOBAR', $pdf->PaginationStr());
    }


    function testMoveX()
    {
        $pdf = new FPDF_EXTENDED();
        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->moveX(100);
        $x += 100;
        $this->assertSame($x, $pdf->getX());
        $this->assertSame($y, $pdf->getY());

        $pdf->moveX(-50);
        $x -= 50;
        $this->assertSame($x, $pdf->getX());
        $this->assertSame($y, $pdf->getY());
    }


    function testMoveY()
    {
        $pdf = new FPDF_EXTENDED();
        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->moveY(100);
        $y += 100;
        $this->assertSame($x, $pdf->getX());
        $this->assertSame($y, $pdf->getY());

        $pdf->moveY(-50);
        $y -= 50;
        $this->assertSame($x, $pdf->getX());
        $this->assertSame($y, $pdf->getY());
    }

}
