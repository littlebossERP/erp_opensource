<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;
use eagle\modules\app\apihelpers\AppApiHelper;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\widgets\SizePager;
// $puid = \Yii::$app->user->identity->getParentUid();
?>
<style>
p{
	color:rgb(160,0,0);
	text-align:center;
	font-size:40px;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('_ebay_leftmenu',['active'=>'定时队列']);?>
	<div class="content-wrapper" >
		<div class="ebaydisbale" >
			<p>暂停使用，如有疑问请联系客服</p>
		</div>
	</div>
</div>


