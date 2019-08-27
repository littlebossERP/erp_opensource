<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace eagle\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        // '//at.alicdn.com/t/font_1449476058_1903462.css',
        'css/iconfont.css',
        'css/site.css?v=1463121766',
        'css/stylesheets/plugins/select2.min.css',
        'css/stylesheets/style.css?v=1460000598',
    ];
    public $js = [
        // 'public/javascripts/jquery/jquery.cookie.js',
        'js/jquery/jquery.mousewheel.min.js',
        'js/lib/select2/select2.full.js',
        'js/public.js?v=1507878023',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    	'yii\bootstrap\BootstrapPluginAsset',
    ];
    
  	public function init()
    {
        parent::init();
     //   if(isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 && 
     //   isset(\Yii::$app->params["currentEnv"]) && "production" == \Yii::$app->params["currentEnv"]){// dzt20150523
      //  	$this->css = ['css/site_for_tracker.css'];
      //  }
      // ;
    }
}
