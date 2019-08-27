<?php
namespace fpdi;

/**
 * @runTestsInSeparateProcesses
 */
class IntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function createPDF(FPDI $fpdi, $filename)
    {
        $fpdi->setSourceFile(__DIR__ . '/A.pdf');

        $template = $fpdi->importPage(1);
        $size = $fpdi->getTemplateSize($template);
        $fpdi->AddPage('P', array($size['w'], $size['h']));
        $fpdi->useTemplate($template);

        $template = $fpdi->importPage(1);
        $fpdi->AddPage('P', array($size['w'], $size['h']));
        $fpdi->useTemplate($template);

        file_put_contents(
            $filename,
            $fpdi->Output('', 'S')
        );
    }

    /**
     * Merge PDF using FPDF
     */
    public function testFPDF()
    {
        $filename = __DIR__ . '/FPDF_AA.pdf';
        @unlink($filename);

        $fpdi = new FPDI;

        $this->assertInstanceOf('\fpdf\FPDF', $fpdi);
        $this->assertNotInstanceOf('\TCPDF', $fpdi);

        $this->createPDF($fpdi, $filename);
        $this->assertTrue(file_exists($filename), "$filename should be created");
    }

    /**
     * Merge PDF using TCPDF
     */
    public function testTCPDF()
    {
        $filename = __DIR__ . '/TCPDF_AA.pdf';
        @unlink($filename);

        // Force autoloading of TCPDF
        new \TCPDF;
        $fpdi = new FPDI;

        $this->assertNotInstanceOf('\fpdf\FPDF', $fpdi);
        $this->assertInstanceOf('\TCPDF', $fpdi);

        $this->createPDF($fpdi, $filename);
        $this->assertTrue(file_exists($filename), "$filename should be created");
    }

    /**
     * Since the code is namespaced the creation of new spl exceptions must refer
     * to the global namespace. This must be changed throughout the codebase.
     * This test asserts that the converting script coveres this issue.
     */
    public function testThrowSplException()
    {
        $this->setExpectedException('\InvalidArgumentException');
        new pdf_parser(__DIR__ . '/this-file-does-not-exists.foobar');
    }
}
