# FPDI - Free PDF Document Importer

[![Packagist Version](https://img.shields.io/packagist/v/itbz/fpdi.svg?style=flat-square)](https://packagist.org/packages/itbz/fpdi)
[![Build Status](https://img.shields.io/travis/hanneskod/fpdi/master.svg?style=flat-square)](https://travis-ci.org/hanneskod/fpdi)
[![Dependency Status](https://img.shields.io/gemnasium/hanneskod/fpdi.svg?style=flat-square)](https://gemnasium.com/hanneskod/fpdi)
[![Reference Status](https://www.versioneye.com/php/itbz:fpdi/reference_badge.svg?style=flat)](https://www.versioneye.com/php/itbz:fpdi/references)

Unofficial [PSR-4](http://www.php-fig.org/psr/psr-4/) compliant version of the
[FPDI](http://www.setasign.com/products/fpdi/about/) library with some minor
changes.

The library is namespaced in fpdi. To create instance use:

```php
$fpdi = new \fpdi\FPDI();
```
> NOTE that since version 1.5.3 FPDI is officially available via composer and
> [github](https://github.com/Setasign/FPDI). However this requires some
> additional setup and is not psr-4 compliant.
>
> Since this fork is namespaced it is possible to install both the official and
> the namespaced versions in the same project, if needed.

Installing
-----------
Install using [composer](https://getcomposer.org/). For historical reasons the
package exists in packagist as `itbz/fpdi`. From project root use

    composer require itbz/fpdi:~1.0

Support for TCPDF
-----------------
To use with TCPDF version `1.5.2-patch1` or later must be used, due to a
conversion error in earlier versions.

Contributing
------------
See the [CONTRIBUTING](CONTRIBUTING.md) file.

Build from source
-----------------
Converting from setasign source is power by [phing](https://www.phing.info/).
See [build.xml](build.xml) for concrete instructions. To execute a build from
the command line use

    $ phing

Tests are run using

    $ phing test

Possibly followed by

    $ phing cleanup

License
-------
The MIT License (MIT)

Copyright (c) 2015 Setasign - Jan Slabon, https://www.setasign.com

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
