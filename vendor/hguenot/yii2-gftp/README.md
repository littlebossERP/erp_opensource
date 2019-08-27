GFtp
====
GFtp is a FTP extension for [YII 2 Framework](http://www.yiiframework.com).

It contains 2 main component :

* `\gftp\FtpComponent` : A Yii component used to manage FTP connection and navigation (encapsulates PHP ftp method).
* `\gftp\FtpWidget` : A widget which can be used to display FTP folder content and allow FTP server browsing.

It supports FTP protocol and FTP over SSL protocol.
SFTP support is provider by [Yii2-gsftp](https://github.com/hguenot/yii2-gsftp) extension.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist hguenot/yii2-gftp "*"
```

or add

```
"hguenot/yii2-gftp": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Here is a basic usage of GFtp extension. 

* Create an FTP application component (in your Web config file)

```php
return [
	// [...]
	'components'=>[
		// [...]
		'ftp' => [
			'connectionString' => 'ftp://user:pass@host:21',
			'driverOptions' =>  [
				'timeout' => 120,
				'passive' => false
			]
		]
	],
	// [...]
];
```

* You can user either a connection string where protocol could be ftp or ftps or directly set `protocol`, `user`, 
  `pass`, `host` and `port` properties :  

```php
return [
	// [...]
	'components'=>[
		// [...]
		'ftp' => [
			'class' => '\gftp\FtpComponent',
			'driverOptions' =>  [
				'class' => '\gftp\drivers\SftpProtocol',
				'user' => 'me@somewhere.otrb',
				'pass' => 'PassW0rd',
				'host' => 'ftp.somewhere.otrb',
				'port' => 2121,
				'timeout' => 120,
				'passive' => false
			]
		]
	],
	// [...]
];
```

* Use component

```php
$files = $gftp->ls();
$gftp->chdir('images');
```

More complete example : 

```php
public function actionExample() { 
    $remote_file = '/data/users.txt'; 
    $local_file = '/tmp/users.load'; 
    $mode = 'FTP_ASCII'; 
    $asynchronous = false; 
    $file = Yii::$app->ftp->get($remote_file, $local_file, $mode, $asynchronous); 
    // [...]
} 
```

* Display ftp content in a Widget :

```php
use gftp\FtpWidget;
echo FtpWidget::widget();
```

* If no FTP(S) connection is passed to the widget, it needs an application component named `'ftp'`. But you can pass an
 FtpComponent directly : 

```php
use \gftp\FtpWidget;
echo FtpWidget::widget([
	'ftp' => \Yii::$app->get('otrb')
]);
```

or 

```php
use \gftp\FtpWidget;
use \gftp\FtpComponent;

echo FtpWidget::widget([
	'ftp' => new FtpComponent([
			'driverOptions' =>  [
				'class' => '\gftp\drivers\SftpProtocol',
				'user' => 'me@somewhere.otrb',
				'pass' => 'PassW0rd',
				'host' => 'ftp.somewhere.otrb',
				'port' => 2121,
				'timeout' => 120,
				'passive' => false
			]
	]);
]);
```


