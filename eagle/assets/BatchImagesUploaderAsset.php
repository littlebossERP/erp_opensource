<?php

namespace eagle\assets;

use yii\web\AssetBundle;

class BatchImagesUploaderAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/batchImagesUploader.css',
    ];
    public $js = [
    	'js/ajaxfileupload.js',
    	'js/batchImagesUploader.js',
    ];
    public $depends = [
    	'yii\web\JqueryAsset',
    ];
}

?>