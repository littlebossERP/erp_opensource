<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace eagle\assets;
use yii\web\AssetBundle;

/**
 * This asset bundle provides the [jquery javascript library](http://jquery.com/)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class PublicAsset extends AssetBundle
{
    // public $sourcePath = '@web/js';
    public $js = [
        'js/jquery/jquery.tmpl.min.js',
        'js/public.js?v=1507878023',
    ];
    public $depends = [
    	'yii\web\JqueryAsset'
    ];
}
