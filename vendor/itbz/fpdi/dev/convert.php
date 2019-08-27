<?php
namespace fpdidev;

header("Content-Type: text/plain");
error_reporting(-1);
ini_set('display_errors', '1');

include __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\Finder\Finder;

$header = <<<EOF
<?php
/**
 * This file is part of FPDI
 *
 * @package   FPDI
 * @copyright Copyright (c) 2015 Setasign - Jan Slabon (http://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 * @version   1.6.1
 */

/**
* PLEASE NOTE THAT THIS FILE IS PROCESSED PROGRAMMATICALLY FOR THE itbz\\fpdi
* RELEASE BUG REPORTS AND SUGGESTED CHANGES SHOULD BE DIRECTED TO SETASIGN
* DIRECTLY BUGS RELATED TO THIS CONVERSION CAN BE REPORTED AT
* https://github.com/hanneskod/fpdi/issues
*/


EOF;

$converter = new Converter(
    (new Finder)->files()->in($argv[1])->name('*.php'),
    $header
);

$converter->convert(function($fname, $content) use ($argv) {
    $fname = $argv[2] . DIRECTORY_SEPARATOR . $fname;
    echo "Converting <$fname>\n";
    file_put_contents($fname, $content);
});

echo "Conversion done!\n";
