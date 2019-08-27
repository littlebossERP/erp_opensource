<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use eagle\assets\AppAsset;
use frontend\widgets\Alert;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);

use yii\jui\JuiAsset;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\MenuHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
JuiAsset::register($this);
$this->registerJsFile(\Yii::getAlias('@web')."/js/bootbox.min.js", ['depends' => ['yii\bootstrap\BootstrapAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js", ['position' => \yii\web\View::POS_END]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.css");


$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery-extension.class.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/layerjs/layer.js", ['depends' => ['yii\web\JqueryAsset']]);

if (isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 )
	$this->registerJsFile(\Yii::getAlias('@web')."/js/project/index_for_tracker.js", ['depends' => ['yii\web\JqueryAsset']]);

//设置全局js变量
$this->registerJs('var global = (function() { return this || (1,eval)("(this)"); }());global.baseUrl = "'.\Yii::getAlias('@web').'/";', \yii\web\View::POS_HEAD);


//bootbox信息弹出框多语言的设置。目前只支持繁体
$language = isset($_COOKIE['lan'])?$_COOKIE['lan']:'zh-cn';
$locale="zh_CN";
if ($language=="zh-hk") $locale="zh_TW";
$this->registerJs("bootbox.setDefaults({locale:'".$locale."'});", \yii\web\View::POS_END);
//多语言js加载
$this->registerJs('Translator = new Translate('. json_encode(TranslateHelper::getJsDictionary()).');', \yii\web\View::POS_END);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->head() ?>
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
