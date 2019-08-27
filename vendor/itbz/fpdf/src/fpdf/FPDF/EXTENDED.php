<?php
/**
 *
 * Copyright (c) 2011, Hannes Forsgård
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software to use, copy, modify, distribute, sublicense, and/or sell
 * copies of the software, and to permit persons to whom the software is
 * furnished to do so.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED.
 *
 * @author Hannes Forsgård <hannes.forsgard@gmail.com>
 *
 * @package fpdf
 *
 */
namespace fpdf;


/**
 *
 * FPDF extension class.
 *
 * @package fpdf
 *
 */
class FPDF_EXTENDED extends FPDF
{
    /**
     *
     * Current count of pages.
     *
     * @var int $nrOfPages
     *
     */
    private $nrOfPages = 0;

    /**
     *
     * Path to search for images in.
     *
     * @var string $imagePath
     *
     */
    private $imagePath = "";

    /**
     *
     * Keep track of added fonts.
     *
     * @var array $addedFonts
     *
     */
    private $addedFonts = array();
    
    /**
     *
     * Set standard margin, orientation, units used and paper size
     *
     * @param int $margin Marginsize in user units.
     *
     * @param char $orientation Default page orientation. 'P' for portrait or
     * 'L' for landscape
     *
     * @param string $unit User units. 'pt' for points, 'mm' for millimeters,
     * 'cm' for centimetera or 'in' for inches
     *
     * @param string|array $format The size used for pages. 'A3', 'A4', 'A5',
     * 'Letter' or 'Legal'. May also be an array of height and width specified
     * in user units.
     *
     */
    public function __construct(
        $margin = 20,
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4'
    ) {
        parent::__construct($orientation, $unit, $format);
        $this->AliasNbPages('{{{nb}}}');
        $this->SetMargins($margin, $margin);
        $this->SetAutoPageBreak(TRUE, $margin);
    }

    /**
     *
     * Adds a new page to the document.
     *
     * Extends FPDF by keeping track of number of pages added.
     *
     * @param string $orientation Page orientation. 'P' for portrait or 'L' for
     * landscape. The default value is the one passed to the constructor.
     *
     * @param string $format The size used for pages. 'A3', 'A4', 'A5',
     * 'Letter' or 'Legal'. May also be an array of height and width specified
     * in user units. The default value is the one passed to the constructor.
     *
     * @return void
     *
     */
    public function AddPage($orientation = '', $format = '')
    {
        $this->nrOfPages++;
        parent::AddPage($orientation, $format);
    }

    /**
     *
     * Get the current number of pages added with AddPage().
     *
     * Note that this number will increase as you add more pages. Should not be
     * used to print the total number of pages in document. For this use
     * TotalPagesNo().
     *
     * @return int Number of pages currently in document
     *
     */
    public function PagesAdded()
    {
        return $this->nrOfPages;
    }

    /**
     *
     * Shorthand to get total number of pages in pdf
     *
     * @return string Returns a string that will be replaced with the total
     * number of pages when pdf is rendered
     *
     */
    public function TotalPagesNo()
    {
        return $this->AliasNbPages;
    }

    /**
     *
     * Shorthand to get current page/total pages.
     *
     * @param string $delim Delimiter used between current and page number and
     * total pages number.
     *
     * @return string Returns a string that will be replaced with current page
     * number, then delimiter, then the total number of pages.
     *
     */
    public function PaginationStr($delim = '/')
    {
        return $this->PageNo() . $delim . $this->TotalPagesNo();
    }

    /**
     *
     * Increase the abscissa of the current position.
     *
     * @param int $x
     *
     * @return void
     *
     */
    public function moveX($x)
    {
        $posX = $this->GetX();
        $posX += $x;
        $this->SetX($posX);
    }

    /**
     *
     * Increase the ordinate of the current position.
     *
     * @param int $y
     *
     * @return void
     *
     */
    public function moveY($y)
    {
        $posX = $this->GetX();
        $posY = $this->GetY();
        $posY += $y;
        $this->SetXY($posX, $posY);
    }

    /**
     *
     * Wrapper to solve utf-8 issues.
     *
     * @param string $title
     *
     * @param bool $isUTF8 Defaults to TRUE.
     *
     * @return void
     *
     */
    public function SetTitle($title, $isUTF8 = TRUE)
    {
        parent::SetTitle($title, $isUTF8);
    }

    /**
     *
     * Wrapper to solve utf-8 issues.
     *
     * @param string $subject
     *
     * @param bool $isUTF8 Defaults to TRUE.
     *
     * @return void
     *
     */
    public function SetSubject($subject, $isUTF8 = TRUE)
    {
        parent::SetSubject($subject, $isUTF8);
    }

    /**
     *
     * Wrapper to solve utf-8 issues.
     *
     * @param string $author
     *
     * @param bool $isUTF8 Defaults to TRUE.
     *
     * @return void
     *
     */
    public function SetAuthor($author, $isUTF8 = TRUE)
    {
        parent::SetAuthor($author, $isUTF8);
    }

    /**
     *
     * Wrapper to solve utf-8 issues.
     *
     * @param string $keywords
     *
     * @param bool $isUTF8 Defaults to TRUE.
     *
     * @return void
     *
     */
    public function SetKeywords($keywords, $isUTF8 = TRUE)
    {
        parent::SetKeywords($keywords, $isUTF8);
    }

    /**
     *
     * Wrapper to solve utf-8 issues.
     *
     * @param string $creator
     *
     * @param bool $isUTF8 Defaults to TRUE.
     *
     * @return void
     *
     */
    public function SetCreator($creator, $isUTF8 = TRUE)
    {
        parent::SetCreator($creator, $isUTF8);
    }

    /**
     *
     * Print text in cell. Solves utf-8 issues.
     *
     * Prints a cell (rectangular area) with optional borders, background color
     * and character string. The upper-left corner of the cell corresponds to
     * the current position. The text can be aligned or centered. After the
     * call, the current position moves to the right or to the next line. It is
     * possible to put a link on the text. 
     *
     * If automatic page breaking is enabled (witch is it by default) and the
     * cell goes beyond the limit, a page break is done before outputting.
     *
     * @param int $width Cell width. If 0, the cell extends up to the right
     * margin.
     *
     * @param int $height Cell height. Default value: 0.
     *
     * @param string $txt String to print. Default value: empty string.
     *
     * @param string|int $border Indicates if borders must be drawn around the
     * cell. The value can be either a number: 0 for no border, 1 for a frame.
     * Or a string containing some or all of the following characters (in any
     * order): 'L' for left, 'T' for top, 'R' for right or 'B' for bottom.
     *
     * @param int $ln Indicates where the current position should go after the
     * call. Possible values are: 0 - to the rigth, 1 - to the beginning of the
     * next line or 2 - below.
     *
     * @param char $align Allows to center or align the tex. 'L', 'C' or 'R'.
     *
     * @param bool $fill Indicates if the cell background must be painted (TRUE)
     * or transparent (FALSE). Default value: FALSE.
     *
     * @param string|identifier $link URL or identifier returned by AddLink().
     *
     * @return void
     *
     */
    public function Cell(
        $width,
        $height = 0,
        $txt = '',
        $border = 0,
        $ln = 0,
        $align = '',
        $fill = FALSE,
        $link = ''
    ) {
        $txt = utf8_decode($txt);
        parent::Cell($width, $height, $txt, $border, $ln, $align, $fill, $link);
    }

    /**
     *
     * Prints a character string. Solves utf-8 issues.
     *
     * The origin is on the left of the first character, on the baseline. This
     * method allows to place a string precisely on the page, but it is usually
     * easier to use Cell(), MultiCell() or Write() which are the standard
     * methods to print text.
     *
     * @param int $x Abscissa of the origin.
     *
     * @param int $y Ordinate of the origin.
     *
     * @param string $txt String to print.
     *
     * @return void
     *
     */
    public function Text($x, $y, $txt)
    {
        $txt = utf8_decode($txt);
        parent::Text($x, $y, $txt);        
    }

    /**
     *
     * Print text from the current position.
     *
     * Fix positioning errors when using non-english characters (eg. åäö).
     *
     * When the right margin is reached (or the \n character is met) a line
     * break occurs and text continues from the left margin. Upon method exit,
     * the current position is left just at the end of the text. 
     *
     * @param string $lineHeight Line height.
     *
     * @param string $txt String to print.
     *
     * @param string|identifier $link URL or identifier returned by AddLink().
     *
     * @return void
     *
     * @todo Fix positioning hack..
     *
     */
    public function Write($lineHeight, $txt, $link = '')
    {
        parent::Write($lineHeight, $txt, $link);
        // Uggly hack to help fix positions
        $specChars = preg_replace("/[^åäöÅÄÖ]/", '', $txt);
        $specChars = strlen($specChars)*1.75;
        if ( $specChars ) $this->moveX($specChars*-1);
    }

    /**
     * Write to position
     *
     * @param  string $x
     * @param  string $y
     * @param  string $line
     * @param  string $txt 
     * @param  string|identifier $link URL or identifier returned by AddLink().
     * @return void
     */
    public function WriteXY($x, $y, $line, $txt, $link = '')
    {
        $this->SetXY($x, $y);
        $this->Write($line, $txt, $link);
    }

    /**
     *
     * Set image path. Enables image() to understand relative paths.
     *
     * @param string $path
     *
     * @return void
     *
     */
    public function setImagePath($path)
    {
        $this->imagePath = realpath($path);
    }

    /**
     *
     * Output an image.
     *
     * @param string $file Path or URL of the image. May be relative to
     * path set using setImagePath()
     *
     * @param int $x Abscissa of the upper-left corner. If not specified or
     * equal to NULL, the current abscissa is used.
     *
     * @param int $y Ordinate of the upper-left corner. If not specified or
     * equal to NULL, the current ordinate is used; moreover, a page break is
     * triggered first if necessary (in case automatic page breaking is enabled)
     * and, after the call, the current ordinate is moved to the bottom of the
     * image.
     *
     * @param int $width Width of the image in the page.
     *
     * @param int $height Height of the image in the page. 
     *
     * @param string $type JPG|JPEG|PNG|GIF
     *
     * @param string|identifier $link URL or identifier returned by AddLink().
     *
     * @return void
     *
     */
    public function Image(
        $file,
        $x = NULL,
        $y = NULL,
        $width = 0,
        $height = 0,
        $type = '',
        $link = ''
    ) {
        $absolute = $this->imagePath . DIRECTORY_SEPARATOR . $file;
        if (!is_readable($file) && is_readable($absolute)) {
            $file = $absolute;
        }
        parent::Image($file, $x, $y, $width, $height, $type, $link);
    }

    /**
     *
     * Import a TrueType or Type1 font and make it available.
     *
     * @param string $family
     *
     * @param string $style 'B', 'I' or 'IB'
     *
     * @param string $file The font definition file. By default, the name is
     * built from the family and style, in lower case with no space.
     *
     * @return void
     *
     */
    public function AddFont($family, $style = '', $file = '')
    {
        parent::AddFont($family, $style, $file);
        if (!isset($this->addedFonts[$family])) {
            $this->addedFonts[$family] = array();
        }
        $this->addedFonts[$family][] = $style;
    }

    /**
     *
     * Sets the font used to print character strings.
     *
     * @param string $family Family font. It can be either a name defined by
     * AddFont() or one of the standard families (case insensitive): Courier,
     * Helvetica or Arial, Times, Symbol or ZapfDingbats.
     *
     * @param string $style 'B', 'I', 'U' or any combination.
     *
     * @param int $size Font size in points. The default value is the current
     * size. If no size has been specified since the beginning of the document,
     * the value taken is 12.
     *
     * @return void
     *
     */
    public function SetFont($family, $style = '', $size = 0)
    {
        $style = strtoupper($style);
        
        // U is not handled by AddFont(), hence needs special treatment
        $addU = '';
        if (strpos($style, 'U') !== FALSE) {
            $addU = 'U';
            $style = str_replace('U', '', $style);
        }
        
        if (isset($this->addedFonts[$family])) {
            if (!in_array($style, $this->addedFonts[$family]) ) {
                // Requested style is missing
                if (in_array('', $this->addedFonts[$family])) {
                    // Using no style
                    $style = '';
                } else {
                    // Use first added style
                    $style = $this->addedFonts[$family][0];
                }
            }
        }    

        $style = $style.$addU;
        parent::SetFont($family, $style, $size);
    }

    /**
     *
     * Send the document to a given destination
     *
     * @param string $name The name of the file. If not specified, the document
     * will be sent to the browser (destination I) with the name doc.pdf.
     *
     * @param char $dest Destination where to send the document. It can take one
     * of the following values: 'I' - send the file inline to the browser.
     * 'D' - send to the browser and force a file download with the name given
     * by name. 'F' - save to a local file with the name given by name (may
     * include a path). 'S' - return the document as a string. name is ignored.
     *
     * @return string
     *
     */
    public function Output($name = '', $dest = '')
    {
        $this->draw();
        return parent::Output($name, $dest);
    }

    /**
     *
     * Shorthand for direct string output
     *
     * @return string Raw PDF
     *
     */
    public function GetPdf()
    {
        return $this->Output('', 'S');
    }

    /**
     *
     * Perform actions just before Output
     *
     * @return void
     *
     */
    protected function draw(){}

}
