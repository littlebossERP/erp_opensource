PDFMerger 安装

由于windows 本地测试composer require "jurosh/pdf-merge",composer update "jurosh/pdf-merge"均不能单独 安装插件到当前项目
composer intall 则要更新一些其他package （而且更新途中有报错）
所以通过

composer global require "jurosh/pdf-merge" 这种将插件安装在全局目录里面 

然后 到全局vendor 复制插件代码
itbz 和 jurosh 整个文件夹复制到项目vendor 底下

然后打开全局vendor/composer 下面的installed.json
复制新增部分 到 项目的vendor/composer 下的installed.json 里面

"jurosh/pdf-merge" 则是这部分
{
	"name": "itbz/fpdf",
	"version": "1.7.2",
	"version_normalized": "1.7.2.0",
	"source": {
		"type": "git",
		"url": "https://github.com/hanneskod/fpdf.git",
		"reference": "06d02a7bf227a62d37691a4e7e9c3a35efcb41c3"
	},
	"dist": {
		"type": "zip",
		"url": "https://api.github.com/repos/hanneskod/fpdf/zipball/06d02a7bf227a62d37691a4e7e9c3a35efcb41c3",
		"reference": "06d02a7bf227a62d37691a4e7e9c3a35efcb41c3",
		"shasum": ""
	},
	"require": {
		"php": ">=5.3.0"
	},
	"time": "2013-12-27 14:18:15",
	"type": "library",
	"installation-source": "dist",
	"autoload": {
		"psr-0": {
			"fpdf": "src/"
		}
	},
	"notification-url": "https://packagist.org/downloads/",
	"license": [
		"no usage restriction"
	],
	"description": "Unofficial PSR-0 compliant version of the FPDF library",
	"homepage": "http://www.fpdf.org/"
},
{
	"name": "itbz/fpdi",
	"version": "1.6.1",
	"version_normalized": "1.6.1.0",
	"source": {
		"type": "git",
		"url": "https://github.com/hanneskod/fpdi.git",
		"reference": "3189f4662a5bbd3838309368d9fc7f5a31c08a6f"
	},
	"dist": {
		"type": "zip",
		"url": "https://api.github.com/repos/hanneskod/fpdi/zipball/3189f4662a5bbd3838309368d9fc7f5a31c08a6f",
		"reference": "3189f4662a5bbd3838309368d9fc7f5a31c08a6f",
		"shasum": ""
	},
	"require": {
		"itbz/fpdf": "~1.7",
		"php": ">=5.3.0"
	},
	"require-dev": {
		"hanneskod/classtools": "~1.0",
		"nikic/php-parser": "~1.0",
		"phpunit/phpunit": "4.*",
		"symfony/finder": "~2.3",
		"tecnickcom/tcpdf": "^6.2.12"
	},
	"suggest": {
		"tecnickcom/tcpdf": "FPDI supports TCPDF as a replacement for FPDF"
	},
	"time": "2016-03-02 19:45:05",
	"type": "library",
	"installation-source": "dist",
	"autoload": {
		"psr-4": {
			"fpdi\\": "src/"
		}
	},
	"notification-url": "https://packagist.org/downloads/",
	"license": [
		"MIT"
	],
	"description": "Unofficial PSR-4 compliant version of the FPDI library"
},
{
	"name": "jurosh/pdf-merge",
	"version": "1.0.0",
	"version_normalized": "1.0.0.0",
	"source": {
		"type": "git",
		"url": "https://github.com/jurosh/php-pdf-merge.git",
		"reference": "64cf4edda347239e2864fd3f777f503650f20215"
	},
	"dist": {
		"type": "zip",
		"url": "https://api.github.com/repos/jurosh/php-pdf-merge/zipball/64cf4edda347239e2864fd3f777f503650f20215",
		"reference": "64cf4edda347239e2864fd3f777f503650f20215",
		"shasum": ""
	},
	"require": {
		"itbz/fpdi": "^1.5",
		"php": ">=5.3.0"
	},
	"time": "2015-08-04 21:25:40",
	"type": "library",
	"installation-source": "dist",
	"autoload": {
		"classmap": [
			"src/Jurosh/PDFMerge/PDFMerger.php",
			"src/Jurosh/PDFMerge/PDFObject.php"
		]
	},
	"notification-url": "https://packagist.org/downloads/",
	"license": [
		"MIT"
	],
	"authors": [
		{
			"name": "Juraj Husar",
			"email": "jurosh@jurosh.com",
			"homepage": "http://jurosh.com"
		}
	],
	"description": "PHP PDF Merger",
	"homepage": "https://github.com/jurosh/php-pdf-merge",
	"keywords": [
		"merge",
		"pdf"
	]
}

auto load 的相关文件则不复制而是通过如下命令更新auto load文件
项目目录下>composer dump-autoload --optimize 

此命令大概会更新
autoload_classmap.php，autoload_namespaces.php，autoload_psr4.php，autoload_files.php以及ClassLoader.php 5个文件 

autoload_real.php 并不是这个命令修改的，但我用一个时间戳生成一个 md5值修改了其中的类名和一个方法名
最后修改 项目vendor下的autoload.php 触发autoload_real.php里面的类 完成插件安装


