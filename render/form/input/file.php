<?php namespace render\form\input;

use yii\web\View;

class File extends \Render\Form\Base
{

	public function __construct(){
		call_user_func_array('parent::__construct', func_get_args());
		\Yii::$app->view->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
		\Yii::$app->view->registerJsFile(\Yii::getAlias('@web')."/js/batchImagesUploader.js", ['depends' => ['yii\bootstrap\BootstrapPluginAsset']]);
	}

	// public $validPattern = '^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$';

	function __toString(){
		$bg = $this->value?"background-image:url({$this->value})":"";
		$val = $this->value?explode('/',$this->value):[];
		return '<div class="iv-upload">
			<div class="view" style="display:'.($this->value?'block':'none').';'.$bg.'">
				<div class="remove"></div>
				<span class="iconfont icon-shanchu" confirm="确定要删除?"></span>
			</div>
			<div class="choose" style="display:'.($this->value?'none':'block').';">
				<input type="file" name="wholesale" id="'.$this->getId().'" />
				<div class="placeholder">
					<p>添加图片</p>
					<span class="iconfont icon-zengjia"></span>
				</div>
			</div>
			<p class="src">'.array_pop($val).'</p>
			<input type="hidden" name="'.$this->name.'" value="'.$this->value.'" />
		</div>';
	}

} 
	// http://littleboss-image.s3.amazonaws.com/1/20151217/20151217175935-84f3ccd.jpg