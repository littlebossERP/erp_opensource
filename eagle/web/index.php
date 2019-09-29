<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');


require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');
// 加载phpHtmlRender类库
require(__DIR__ . '/../../render/all.php');



$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-local.php'),
    require(__DIR__ . '/../config/main.php'),
    require(__DIR__ . '/../config/main-local.php')
);

$vendorDir = dirname(dirname(__DIR__)) . '/vendor'; 

$application = new yii\web\Application($config);
Yii::$classMap['HTML2PDF'] = $vendorDir . '/html2pdf/html2pdf.class.php'; // 在启动yii的之前加上这个类
Yii::$classMap['TCPDF'] = $vendorDir . '/tcpdf/tcpdf.php'; // 在启动yii的之前加上这个类
//beforeRequestHandle
//\Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST,eagle\helpers\EventHandlerHelper::beforeRequestHandle());
//\Yii::$app->on(\yii\base\Application::EVENT_BEFORE_ACTION,eagle\helpers\EventHandlerHelper::beforeActionHandle());

$application->run();




