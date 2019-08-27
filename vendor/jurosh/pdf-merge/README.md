# PDF Merge for PHP

PDF Merge library for PHP.

Install in composer:

```json
"jurosh/pdf-merge": "dev-master"
```

## Highlights

Pdf merging with modes portrait/landscape.

Tested in Laravel4 framework.

## Usage

```php
// Autoload classses...

// and we can do stuff
$pdf = new \Jurosh\PDFMerge\PDFMerger;

// add as many pdfs as you want
$pdf->addPDF('path/to/source/file.pdf', 'all', 'vertical')
  ->addPDF('path/to/source/file1.pdf', 'all')
  ->addPDF('path/to/source/file2.pdf', 'all', 'horizontal');

// call merge, output format `file`
$pdf->merge('file', 'path/to/export/dir/file.pdf');
```