<?php
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\SysCarrierCustom;
$controller = yii::$app->controller->id;
$action = yii::$app->controller->action->id;
$is_custom = isset($_GET['is_custom'])?$_GET['is_custom']:0;
?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar" style="width:205px;">
	<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>
	<div class="sidebarLv1Title">
		<div>
		<span class="glyphicon glyphicon-th-list" style="margin: 3px 5px 0px 0px;color:#00CCFF;"></span>
			<?= TranslateHelper::t('订单')?>
		</div>
	</div>	
	<ul class="ul-sidebar-one">
	<?php $c = OdOrder::findBySql('SELECT count(*) as c from od_order_v2 where order_status = 300 and is_manual_order = 0')->asArray()->one();?>
		<li class="ul-sidebar-li<?=$controller == 'default' && $action =='index'?' active':''?>"><a href="<?= Url::to(['/carrier/default/index'])?>"><font><?= TranslateHelper::t('可操作订单')?></font><?= (0)?"":"<span class=\"badge\">".$c['c']."</span>";?></a></li>
	</ul>
	<div class="sidebarLv1Title">
		<div>
		<span class="glyphicon glyphicon-random" style=" margin: 5px 5px 0px 0px;color:#00CCFF;"></span>
			<?= TranslateHelper::t('物流操作状态')?>
		</div>
	</div>	
	<ul class="ul-sidebar-one">
	<?php 
	$count = OdOrder::findBySql('SELECT count(*) as c,carrier_step from od_order_v2 where order_status = 300 and is_manual_order = 0 GROUP BY carrier_step')->asArray()->all();
	if (count($count)>0){
		$result = Helper_Array::toHashmap($count, 'carrier_step','c');
	}else {
		$result = array();
	}
	$a = isset($result[0])?$result[0]:0;
	$b = isset($result[4])?$result[4]:0;
	$waitingupload = $a+$b;
	$waitingdispatch = isset($result[1])?$result[1]:0;
	$waitinggettrackingno=isset($result[2])?$result[2]:0;
	$waitingprint=isset($result[3])?$result[3]:0;
	$carriercomplete=isset($result[6])?$result[6]:0;
	?>
		<li class="ul-sidebar-li<?=($controller == 'default' && $action =='waitingupload')?' active':''?>"><a href="<?= Url::to(['/carrier/default/waitingupload'])?>"><font><?= TranslateHelper::t('待上传至物流商')?></font><?= (0)?"":"<span class=\"badge\">".(string)$waitingupload."</span>";?></a></li>
		<li class="ul-sidebar-li<?=($controller == 'default' && $action =='waitingdispatch')?' active':''?>"><a href="<?= Url::to(['/carrier/default/waitingdispatch'])?>"><font><?= TranslateHelper::t('待交运')?></font><?= (0)?"":"<span class=\"badge\">".(string)$waitingdispatch."</span>";?></a></li>
		<li class="ul-sidebar-li<?=($controller == 'default' && $action =='waitinggettrackingno')?' active':''?>"><a href="<?= Url::to(['/carrier/default/waitinggettrackingno'])?>"><font><?= TranslateHelper::t('待获取物流号')?></font><?= (0)?"":"<span class=\"badge\">".(string)$waitinggettrackingno."</span>";?></a></li>
		<li class="ul-sidebar-li<?=($controller == 'default' && $action =='waitingprint')?' active':''?>"><a href="<?= Url::to(['/carrier/default/waitingprint'])?>"><font><?= TranslateHelper::t('待打印物流单')?></font><?= (0)?"":"<span class=\"badge\">".(string)$waitingprint."</span>";?></a></li>
		<li class="ul-sidebar-li<?=($controller == 'default' && $action =='carriercomplete')?' active':''?>"><a href="<?= Url::to(['/carrier/default/carriercomplete'])?>"><font><?= TranslateHelper::t('物流已完成')?></font><?= (0)?"":"<span class=\"badge\">".(string)$carriercomplete."</span>";?></a></li>
	</ul>
	<div class="sidebarLv1Title">
		<div>
		<span class="glyphicon glyphicon-cog" style="margin: 3px 5px 0px 0px; color:#00CCFF;"></span>
			<?= TranslateHelper::t('常规物流基础设置')?>
		</div>
	</div>	
	<?php $countCarrier=SysCarrier::find()->count();?>
	<?php $countAccount=SysCarrierAccount::find()->count();?>
	<?php $countService=SysShippingService::find()->where(['is_custom'=>0])->count();?>
	<?php $countRule=MatchingRule::find()->count();?>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=$controller == 'carrier' && ($action =='list'||$action =='')?' active':''?>"><a href="<?= Url::to(['/carrier/carrier/list'])?>"><font><?= TranslateHelper::t('物流商列表')?></font><?= (0)?"":"<span class=\"badge\">".$countCarrier."</span>";?></a></li>
		<li class="ul-sidebar-li<?=$controller == 'carrieraccount' && ($action =='index'||$action =='create')?' active':''?>"><a href="<?= Url::to(['/carrier/carrieraccount/index'])?>"><font><?= TranslateHelper::t('物流账号管理')?></font><?= (0)?"":"<span class=\"badge\">".$countAccount."</span>";?></a></li>
		<li class="ul-sidebar-li<?=$controller == 'shippingservice' && (($is_custom ==0&&$action =='create')||$is_custom ==0&&$action =='index')?' active':''?>"><a href="<?= Url::to(['/carrier/shippingservice/index','is_custom'=>0])?>"><font><?= TranslateHelper::t('运输服务管理')?></font><?= (0)?"":"<span class=\"badge\">".$countService."</span>";?></a></li>
		<li class="ul-sidebar-li<?=$controller == 'match' && (($action =='index'&&$is_custom ==0)||($action =='edit'&&$is_custom ==0))?' active':''?>">
			<a href="<?= Url::to(['/carrier/match/index' ,'is_custom'=>0])?>">
				<font><?= TranslateHelper::t('运输服务匹配规则管理')?></font>
				<?= (0)?"":"<span class=\"badge\">".$countRule."</span>";?>
			</a>
		</li>
		<li class="ul-sidebar-li<?=$controller == 'carrier' && ($action =='carrier-print-set-list')?' active':''?>"><a href="<?= Url::to(['/carrier/carrier/carrier-print-set-list'])?>"><font><?= TranslateHelper::t('高仿打印设置')?></font></a></li>
	</ul>
	<?php $countCarrierCustom=SysCarrierCustom::find()->count();?>
	<?php $countServiceCustom=SysShippingService::find()->where(['is_custom'=>1])->count();?>
	<div class="sidebarLv1Title">
		<div>
		<span class="glyphicon glyphicon-cog" style="margin: 3px 5px 0px 0px; color:#00CCFF;"></span>
			<?= TranslateHelper::t('自定义物流基础设置')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=$controller == 'carrier' && ($action =='listcustom')?' active':''?>"><a href="<?= Url::to(['/carrier/carrier/listcustom','is_custom'=>1])?>"><font><?= TranslateHelper::t('自定义物流商列表')?></font><?= (0)?"":"<span class=\"badge\">".$countCarrierCustom."</span>";?></a></li>
		<li class="ul-sidebar-li<?=$controller == 'shippingservice' && ($is_custom ==1|| $action =='createcustom')?' active':''?>"><a href="<?= Url::to(['/carrier/shippingservice/index','is_custom'=>1])?>"><font><?= TranslateHelper::t('自定义运输服务管理')?></font><?= (0)?"":"<span class=\"badge\">".$countServiceCustom."</span>";?></a></li>
		<li class="ul-sidebar-li<?=$controller == 'match' && (($action =='index'&&$is_custom ==1)||($action =='edit'&&$is_custom ==1))?' active':''?>">
			<a href="<?= Url::to(['/carrier/match/index','is_custom'=>1])?>">
				<font><?= TranslateHelper::t('运输服务匹配规则管理')?></font>
				<?= (0)?"":"<span class=\"badge\">".$countRule."</span>";?>
			</a>
		</li>
		<li class="ul-sidebar-li<?=$controller == 'carriercustomtemplate' && $action =='index'?' active':''?>">
			<a href="<?= Url::to(['/carrier/carriercustomtemplate/index'])?>">
				<font><?= TranslateHelper::t('自定义打印模版')?></font>
			
			</a>
		</li>
		<li class="ul-sidebar-li<?=$controller == 'trackingnumber' && ($action =='list')?' active':''?>"><a href="<?= Url::to(['/carrier/trackingnumber/list'])?>"><font><?= TranslateHelper::t('自定义物流号库')?></font></a></li>
	</ul>
</div>
