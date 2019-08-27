<?php
/**
 * 只包含 js 和css的布局
 * 
 */


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
JuiAsset::register($this);
$this->registerJsFile(\Yii::getAlias('@web')."/js/bootbox.min.js", ['depends' => ['yii\bootstrap\BootstrapAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/translate.js", ['position' => \yii\web\View::POS_END]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery-extension.class.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.css");

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
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <SCRIPT type="text/javascript">
    function changeLanguage(key){
   	 	$.get('?r=site/change-language',{'lan':key}, function(result){
			window.location.reload();
		});
    }
    </SCRIPT>
    
    <STYLE type="text/css">
    
	</STYLE>
	
</head>
<body>


    <?php $this->beginBody() ?>
    <?= $content ?>
    
	<!-- loading elements begin -->
    <div id="background" class="background" style="display: none; "></div> 
	<div id="progressBar" class="progressBar" style="display: none; "><?= TranslateHelper::t('数据加载中，请稍等...') ?></div> 
    <!-- loading elements end -->
    
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
