<?php
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->registerJsFile($baseUrl."js/project/tracking/station_letter.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJs("StationLetter.templateList=".json_encode($data['listTemplate']).";" , \yii\web\View::POS_READY);
$this->registerJs("StationLetter.defaultTemplate=".json_encode($data['defaultTemplate']).";" , \yii\web\View::POS_READY);
$this->registerJs("StationLetter.init();" , \yii\web\View::POS_READY);

?>
<style>

</style>

<div class="tracking-index col2-layout">
	<?= $this->render('_menu') ?>
	<div class="content-wrapper" >
	<?= $this->render('_station_letter', [ 'data'=>$data
				]) ?>
	</div> 
</div>