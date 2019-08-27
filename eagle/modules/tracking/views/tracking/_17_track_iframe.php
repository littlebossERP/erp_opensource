<?php
use eagle\modules\util\helpers\TranslateHelper;

if(empty($num)){
	echo "运单号缺失！";
}else{
?>
<div id="YQContainer" class="div_more_tracking_info"></div>
<?php
}
$this->registerJs('OrderCommon.doTrack(\''.$num.'\');' , \yii\web\View::POS_READY);
/*
17track 外部调用说明
http://www.17track.net/zh-cn/externalcall/single
*/
?>
