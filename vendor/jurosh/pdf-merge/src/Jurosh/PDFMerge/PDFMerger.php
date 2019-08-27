<?php namespace Jurosh\PDFMerge;

use Exception;
use fpdi\FPDI;
use fpdf\FPDF;

/**
 * Basic merging of PDF files into one file
 *
 * TODO: check newer https://github.com/tecnickcom/TCPDF
 */
class PDFMerger {

    /**
     * @var type Array of PDFObject-s
     */
    private $_files;

    /**
     * Add a PDF for inclusion in the merge with a valid file path.
     * Params are defined like array:  
     *  'pages' => '...',
     *  'orientation' => 'vertical / horizontal'
     * 
     * Pages should be formatted: 1,3,6, 12-16.
     * @param $filepath
     * @param $param
     * @return PDFMerger
     */
    public function addPDF($filepath, $pages = 'all', $orientation = 'vertical') {
        if (file_exists($filepath)) {
            $file = new PdfObject;
            
            if (strtolower($pages) != 'all') {
                $file->pages = $this->_rewritepages($pages);
            }
            
            $file->orientation = $orientation;
            $file->path = $filepath;

            $this->_files[] = $file;
        } else {
            throw new Exception("Could not locate PDF on '$filepath'");
        }

        return $this;
    }

    /**
     * Merge it.
     * @param $outputmode
     * @param $outputname
     * @return PDF
     */
    public function merge($outputmode = 'browser', $outputpath = 'newfile.pdf') {
        if (!isset($this->_files) || !is_array($this->_files)) {
            throw new Exception("No PDFs to merge.");
        }

        $fpdi = new FPDI;

        // merger operations
        /* @var $file PdfObject */
        foreach ($this->_files as $file) {
            $filename = $file->path;
            $filepages = $file->pages;

            $count = $fpdi->setSourceFile($filename);

            //add the pages
            if ($filepages == 'all') {
                for ($i = 1; $i <= $count; $i++) {
                    $template = $fpdi->importPage($i);
                    $size = $fpdi->getTemplateSize($template);

                    $fpdi->AddPage($file->getOrientationCode(), array($size['w'], $size['h']));
                    $fpdi->useTemplate($template);
                }
            } else {
                foreach ($filepages as $page) {
                    if (!$template = $fpdi->importPage($page)) {
                        throw new Exception("Could not load page '$page' in PDF '$filename'. Check that the page exists.");
                    }
                    $size = $fpdi->getTemplateSize($template);

                    $fpdi->AddPage('P', array($size['w'], $size['h']));
                    $fpdi->useTemplate($template);
                }
            }
        }

        //output operations
        $mode = $this->_switchmode($outputmode);

        if ($mode == 'S') {
            return $fpdi->Output($outputpath, 'S');
        } else {
            if ($fpdi->Output($outputpath, $mode) == '') {
                return true;
            } else {
                throw new Exception("Error outputting PDF to '$outputmode'.");
                return false;
            }
        }
    }

    /**
     * FPDI uses single characters for specifying the output location. Change our more descriptive string into proper format.
     * @param $mode
     * @return Character
     */
    private function _switchmode($mode) {
        switch (strtolower($mode)) {
            case 'download':
                return 'D';
                break;
            case 'browser':
                return 'I';
                break;
            case 'file':
                return 'F';
                break;
            case 'string':
                return 'S';
                break;
            default:
                return 'I';
                break;
        }
    }

    /**
     * Takes our provided pages in the form of 1,3,4,16-50 and creates an array of all pages
     * @param $pages
     * @return unknown_type
     */
    private function _rewritepages($pagesParam) {
        $pages = str_replace(' ', '', $pagesParam);
        $part = explode(',', $pages);

        //parse hyphens
        foreach ($part as $i) {
            $ind = explode('-', $i);

            if (count($ind) == 2) {
                $x = $ind[0]; //start page
                $y = $ind[1]; //end page

                if ($x > $y) {
                    throw new Exception("Starting page, '$x' is greater than ending page '$y'.");
                    return false;
                }

                //add middle pages
                while ($x <= $y) {
                    $newpages[] = (int) $x;
                    $x++;
                }
            } else {
                $newpages[] = (int) $ind[0];
            }
        }
        return $newpages;
    }

}
