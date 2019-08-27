FPDF
====
[![Latest Stable Version](https://poser.pugx.org/itbz/fpdf/v/stable.png)](https://packagist.org/packages/itbz/fpdf)

Unofficial PSR-0 compliant version of the FPDF library


This is version 1.7 with some minor changes:

* the library is namespaced in fpdf. To create instance use

    $fpdf = new \fpdf\FPDF();

* directory structure follow the PSR-0 standard with src/ as root

* on error a RuntimeException is thrown instead on lib dramatically dying 

* constructor is renamed `__construct` instead of `FPDF`


Installing with composer
------------------------
The package exists in the packagist repository as `itbz/fpdf`.


FPDF_EXTENDED
-------------
This package also contains some extensions that break backwards compatibility.
To access the enhanced functinality use `FPDF_EXTENDED` instead of `FPDF`.

* FPDF_EXTENDED expects all input to be UTF-8 encoded. FPDF natively expects all
  input to be ISO-8859-1 encoded and recommends the use of utf8_decode() when
  working with utf-8 encoded strings.
* FPDF uses a somewath strange syntax for printing the total number of pages in
  the pdf. FPDF_EXTENDED defines two methods to handle this. `TotalPagesNo()`
  returns a string that will be replaced with total number of pages at output.
  `PaginationStr()` takes an optional delimiter (default '/') and retuns
  '{current page} / {total number of pages}'.
* Calling `AliasNbPages()` is no longer necessary.
* You may set an image path using `setImagePath()` and `image()` will be able to
  understand relative paths.
* FPDF_EXTENDED gracefuly handles missing font styles. If a font is only defined
  (added) for one style (eg. bold) and you try to use another (eg. italic) this
  FPDF_EXTENDED fallbacks to the defined style (eg. bold). Regular styles takes
  precedence.
* FPDF_EXTENDED defines `moveX()` and `moveY()` to move the cursor, in addition to
  FPDFs `setX()` and `setY()`.
* Subclasses of FPDF_EXTENDED may define `draw()`. Draw is called just before
  pdf is rendered. In this may actions can be taken just before pdf creation.
* FPDF_EXTENDED defines `GetPdf()` as a shorthand for outputing the pdf as a
  raw string.

* AS of version 1.7.2 FPDF_EXTENDED defines WriteXY() for writing to a specified
  position.

