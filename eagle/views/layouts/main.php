<?php
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use eagle\assets\AppAsset;
use eagle\widgets\Alert;

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

$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.js", ['depends' => ['eagle\assets\PublicAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/jquery.qtip.min.css");

//加载jquery验证框架
$this->registerJsFile(\Yii::getAlias('@web')."/js/bootstrapValidator.min.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/css/bootstrapValidator.min.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery-extension.class.js?v=1.1", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery-extension2.js", ['depends' => ['eagle\assets\PublicAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/index.js", ['depends' => ['eagle\assets\PublicAsset']]);


//设置全局js变量
$this->registerJs('var global = (function() { return this || (1,eval)("(this)"); }());global.baseUrl = "'.\Yii::getAlias('@web').'/";', \yii\web\View::POS_HEAD);


//bootbox信息弹出框多语言的设置。目前只支持繁体
$language = isset($_COOKIE['lan'])?$_COOKIE['lan']:'zh-cn';
$locale="zh_CN";
if ($language=="zh-hk") $locale="zh_TW";
$this->registerJs("bootbox.setDefaults({locale:'".$locale."'});", \yii\web\View::POS_END);
//多语言js加载
$this->registerJs('Translator = new Translate('. json_encode(TranslateHelper::getJsDictionary()).');', \yii\web\View::POS_END);


// dzt20171031 for页面debug log
$this->registerJs('$("#show-debug-log").html($("#debug-log").html());$("#debug-log").remove();', \yii\web\View::POS_END);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="renderer" content="webkit">
    
    <?= Html::csrfMetaTags() ?>
    <?php if(empty($this->title)) $this->title = "小老板-全免费外贸电商ERP";?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <SCRIPT type="text/javascript">
    function changeLanguage(key){
   	 	$.get('?r=site/change-language',{'lan':key}, function(result){
			window.location.reload();
		});
    }
    </SCRIPT>
    
</head>
<body>
    <?php $this->beginBody() ?>
        <div id="show-debug-log"></div>
        
    <?php $t1 = eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();?>
	<?php echo $this->render("new/nav_app"); //菜单?>
	
	<?php 
    $t2 = eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
    eagle\modules\util\helpers\SysBaseInfoHelper::addFrontDebugLog("render new/nav_app t=".($t2-$t1));
    ?>
    <!-- <div class="wrap container-fluid"> -->

        <div id="page-content">  <!-- style="margin-top:0px"  -->
        
        	<?= Alert::widget() ?>
        	<?= $content ?>
        	<?php if(defined('_IS_USE_LEFTMENU')){
        		echo "</main></div></div>"; 	// 三个div，main,.right_content,.flex-row
        	} ?>
        </div>
    <!--  </div>  -->
    
	<!-- loading elements begin -->
    <div id="background" class="background" style="display: none; "></div> 
    <div id="longprogressBar" class="longprogressBar" style="display: none; "><?= TranslateHelper::t('数据加载，请稍等20S左右...') ?></div>
	<div id="progressBar" class="progressBar" style="display: none; "><?= TranslateHelper::t('数据加载中，请稍等...') ?></div> 
    <!-- loading elements end -->
    
	<!-- <footer class="footer"> -->
	<?php if(!Yii::$app->user->isGuest):?>
    <footer class="footer text-center">
        <?= TranslateHelper::t('Copyright © 2020 UAB Glocash Payment All Rights Reserved')?>&nbsp;&nbsp;<a href="http://www.beian.miit.gov.cn" style="color: #FFFFFF; text-decoration: none;">沪ICP备15025693号-4</a>
    </footer>
	<?php endif;?>
    <div id="over-lay">
    	<div id="loading">
    		<span class="iconfont icon-dengdai18 rotate"></span>
    	</div>
    </div>

    <div id="debug-log" class=hidden>
    <?= eagle\modules\util\helpers\SysBaseInfoHelper::printFrontDebugLog();?>
    </div>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
