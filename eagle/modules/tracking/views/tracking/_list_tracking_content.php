<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\helpers\Url;
use yii\data\Sort;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\RedisHelper;

if (empty($_GET['platform']))
	$sortAttr = ['order_id','track_no','seller_id','create_time','update_time','status','ship_by','stay_days','delivery_fee','notified_seller'];
else
	$sortAttr = ['order_id','track_no','seller_id','ship_out_date','update_time','status','ship_by','stay_days','delivery_fee','notified_seller'];
$sort = new Sort(['attributes' =>$sortAttr]);

$parcel_label_mapping = [
//'all_parcel'=>TranslateHelper::t('全部包裹'),
//'normal_parcel'=>TranslateHelper::t('正常包裹'),
'shipping_parcel'=>TranslateHelper::t('运输途中'),
'no_info_parcel'=>TranslateHelper::t('查询不到'),
'suspend_parcel'=>TranslateHelper::t('延迟查询'),
//'exception_parcel'=>TranslateHelper::t('异常包裹'),
'rejected_parcel'=>TranslateHelper::t('异常退回'),
'ship_over_time_parcel'=>TranslateHelper::t('运输过久'),
'arrived_pending_fetch_parcel'=>TranslateHelper::t('到达待取'),
'delivery_failed_parcel'=>TranslateHelper::t('投递失败'),
'unshipped_parcel'=>TranslateHelper::t('无法交运'),
'received_parcel'=>TranslateHelper::t('已签收'),
"unregistered_parcel"=>TranslateHelper::t("无挂号"),
"expired_parcel"=>TranslateHelper::t("过期物流号"),
"received_parcel"=>TranslateHelper::t("成功签收"),
"platform_confirmed_parcel"=>TranslateHelper::t("买家已确认"),
"ignored_parcel"=>TranslateHelper::t("忽略(不再查询)"),
"quota_insufficient"=>TranslateHelper::t("配额不足"),
];

$menu_platform = (!empty($_GET['platform'])?strtolower($_GET['platform']):"");
$menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");
//$puid = \Yii::$app->user->identity->getCurrentPuid ();
$puid = \Yii::$app->user->identity->getParentUid ();

$divTagHtml = "";
$div_event_html = "";
//$IsShowProgressBar = TRUE;
$distinct_account_list = [];
//dash-board自动打开判断
 
$next_show =  RedisHelper::RedisGet('Tracker_DashBoard',"user_$puid".".next_show");
$show=true;
if(!empty($next_show)){
	if(time()<strtotime($next_show))
		$show=false;
}
/*
if(!empty($_REQUEST))
	$show=false;
*/
if($show)
	$this->registerJs("showDashBoard(1);" , \yii\web\View::POS_READY);
 
?>

<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>

<style>
#dash-board-enter{
	position: fixed;
	bottom: 30px;
	left: 0px;
	width: 34px;
	height: 56px;
	padding: 3px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 0px 5px 5px 0px;	
	cursor: pointer;
}
.17track-trackin-info-win .modal-dialog{
	height:80%;
}
.modal-header{
	height: inherit;
}
</style>
<form
	action="<?= Url::to(['/tracking/tracking/'.yii::$app->controller->action->id])?>"
	method="GET">

	<div>
		<?php
		echo "<span style='color:red;font-size: 16px;' >自2018年9月30日起物流跟踪助手功能将直接由17track提供，届时小老板ERP将停止提供该功能服务。欢迎各位卖家继续在17track平台使用物流追踪服务。为您带来不便，敬请谅解。<a href='https://user.17track.net/zh-cn/register?gb=%23role%3D2' target='_blank'>点击注册17TRACK</a></span>";
		?>
		<br>
		<select name="sellerid" class="eagle-form-control">
			<option value=""><?= TranslateHelper::t('平台账号 ')?></option>
			<?php 
			if (!empty($account_data)){
			foreach($account_data as $row):?>
				<?php if (!in_array($row['name'],$distinct_account_list)):
				?>
				<option value="<?=($row['platform']=='amazon')?$row['merchant_id']:$row['name']?>"  <?php if (! empty($_GET['sellerid'])) if ($_GET['sellerid']==$row['name'] || (!empty($row['merchant_id']) && $_GET['sellerid']==$row['merchant_id'])) echo " selected " ?>><?= $row['name'].((isset($row['store_name'])?'【'.$row['store_name'].'】':''))?></option>
				<?php 
					$distinct_account_list [] = $row['name'];
				endif;
				?>
			<?php endforeach;
			}?>
		</select> 
	
			<select name="is_handled" class="eagle-form-control">
				<option value="" <?= (empty($_GET['is_handled'])?" selected ":"")?>><?= TranslateHelper::t('是否处理')?></option>
				<option value="Y"
					<?php if (! empty($_GET['is_handled'])) if ($_GET['is_handled']=='Y') echo " selected " ?>><?= TranslateHelper::t('已处理')?></option>
				<option value="N"
					<?php if (! empty($_GET['is_handled'])) if ($_GET['is_handled']=='N') echo " selected " ?>><?= TranslateHelper::t('未处理')?></option>
			</select> 
			
			<select name="is_remark" class="eagle-form-control">
				<option value="" <?= (empty($_GET['is_remark'])?" selected ":"")?>><?= TranslateHelper::t('是否备注')?></option>
				<option value="Y"
					<?php if (! empty($_GET['is_remark'])) if ($_GET['is_remark']=='Y') echo " selected " ?>><?= TranslateHelper::t('有备注')?></option>
				<option value="N"
					<?php if (! empty($_GET['is_remark'])) if ($_GET['is_remark']=='N') echo " selected " ?>><?= TranslateHelper::t('无备注')?></option>
			</select> 
			
			<select name="is_send" class="eagle-form-control">
				<option value="" <?= (empty($_GET['is_send'])?" selected ":"")?>><?= TranslateHelper::t('当前状态下是否发信')?></option>
				<option value="Y"
					<?php if (! empty($_GET['is_send'])) if ($_GET['is_send']=='Y') echo " selected " ?>><?= TranslateHelper::t('已发信')?></option>
				<option value="N"
					<?php if (! empty($_GET['is_send'])) if ($_GET['is_send']=='N') echo " selected " ?>><?= TranslateHelper::t('未发信')?></option>
			</select> 
			
			
			<select name="is_has_tag" class="eagle-form-control" style="width: 110px">
				<option value="" <?= (empty($_GET['is_has_tag'])?" selected ":"")?>><?= TranslateHelper::t('有无标签')?></option>
				<?php 
				if (!empty($AllTagData)){
				foreach($AllTagData as $row):?>
					<option value="<?= $row['tag_id']?>"  <?php if (! empty($_GET['is_has_tag'])) if ($_GET['is_has_tag']==$row['tag_id']) echo " selected " ?>><?= $row['tag_name']?></option>
				<?php endforeach;
				}?>
			</select> 
			
				<input type="hidden" name="pos" value="<?= (empty($_GET['pos'])?"":$_GET['pos']);?>">
				<input type="text" id="startdate" class="eagle-form-control"
					name="startdate"
					value="<?= (empty($_GET['startdate'])?"":$_GET['startdate']);?>">
    	<?= TranslateHelper::t('到')?>
    	<input type="text" id="enddate" class="eagle-form-control"
					name="enddate"
					value="<?= (empty($_GET['enddate'])?"":$_GET['enddate']);?>"> <input
					type="hidden" id="parcel_classification"
					name="parcel_classification"
					value="<?= (empty($_GET['parcel_classification'])?"":$_GET['parcel_classification']);?>">
					
		<input type="text" id="stay_days" class="eagle-form-control" name="stay_days" value="<?= (empty($_GET['stay_days'])?"":$_GET['stay_days']);?>"  placeholder="<?= TranslateHelper::t('停留天数')?>"><span qtipkey="tracker_stay_days_search"></span>
		
		<input type="text" id="total_days" class="eagle-form-control" name="total_days" value="<?= (empty($_GET['total_days'])?"":$_GET['total_days']);?>"  placeholder="<?= TranslateHelper::t('总运输天数')?>"><span qtipkey="tracker_total_days_search"></span>
				<input type="hidden" id="platform" name="platform"
					value="<?= (empty($_GET['platform'])?"":$_GET['platform']);?>"> 
				<!-- 
				<input type="text" id='txt_search' class="eagle-form-control"
					name="txt_search"
					value="<?= (empty($_GET['txt_search'])?"":$_GET['txt_search']);?>" placeholder="<?= TranslateHelper::t('物流号 订单号')?>">
			<button type="submit" class="search-mini-btn">
				<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
	    	</button>
	    	 -->
	    	 <div class="div-input-group">
	    	<div class="input-group">
		      <input type="text" id='txt_search' name="txt_search"  class="form-control" placeholder="<?= TranslateHelper::t('物流号 订单号')?>" value="<?= (empty($_GET['txt_search'])?"":$_GET['txt_search']);?>">
		      <span class="input-group-btn">
		        <button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-search" aria-hidden="true"></span></button>
		      </span>
		    </div><!-- /input-group -->
		    </div>
					
    	</div>
    	<div id="div_btn_bar">
    	<!--  
				<a class="btn-xs"
					href="<?= Url::to(['/tracking/tracking/index'])?>"> <span
					class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
    		<?= TranslateHelper::t('手动录入')?>
    	</a>
    	-->
    	<?php if(isset($_GET['parcel_classification'])){?>
    	<button type="button" class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.SetIgnoreCarriers()"
					qtipkey="ignore_auto_update_carrier">
					<span class="glyphicon glyphicon-ban-circle" aria-hidden="true"></span>
    		<?= TranslateHelper::t('忽略物流')?>
    	</button>
    	<?php }?>
    	
		<button type="button" class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.MarkHandled()"
					qtipkey="tracker_mark_handled_button">
					<span class="egicon-ok-blue" aria-hidden="true"></span>
    		<?= TranslateHelper::t('标记已处理')?>
    	</button>

		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.BatchDelTrack()">
					<span class="egicon-trash" aria-hidden="true"></span>
    		<?= TranslateHelper::t('删除')?>
    	</button>
    	
		<?php if (empty($_GET['parcel_classification']) || $_GET['parcel_classification']!=='quota_insufficient' ){ ?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="import_file.ShowExcelImport()">
					<span class="egicon-export" aria-hidden="true"></span>
    		<?= TranslateHelper::t('Excel导入')?>
    	</button>
		<?php } ?>
		
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.exportExcel()">
					<span class="egicon-export" aria-hidden="true"></span>
    		<?= TranslateHelper::t('查询结果导出Excel')?>
    	</button>
    	
    	<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="StationLetter.batchShowMessageBox()" 
					qtipkey="tracker_batch_send_message_button">
					<span class="egicon-envelope" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量发信')?>
    	</button>
    	<?php if (! empty($_GET['parcel_classification']) && $_GET['parcel_classification']=='unshipped_parcel' ):?>
    	<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchUpdateUnshipParcel()">
					<span class="egicon-refresh" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量更新无法交运物流信息')?>
    	</button>
    	<?php endif;?>
    	<?php if (! empty($_GET['parcel_classification']) && ($_GET['parcel_classification']=='unshipped_parcel' || $_GET['parcel_classification']=='no_info_parcel' || $_GET['parcel_classification']=='rejected_parcel' || $_GET['parcel_classification']=='ship_over_time_parcel' || $_GET['parcel_classification']=='arrived_pending_fetch_parcel' || $_GET['parcel_classification']=='delivery_failed_parcel')){?>
    	<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchUpdateTrackRequest()">
					<span class="egicon-refresh" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量立即刷新')?>
    	</button>
    	<?php }?>
		<?php if (! empty($_GET['parcel_classification']) && $_GET['parcel_classification']!='shipping_parcel'  && empty($_GET['pos']) && $_GET['parcel_classification']!='ignored_parcel' && $_GET['parcel_classification']!='quota_insufficient'):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchMarkShipping()">
					<span class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量移到运输途中')?>
    	</button>
		<?php endif;?>
		
		<?php if (! empty($_GET['parcel_classification']) && $_GET['parcel_classification']=='quota_insufficient'):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchQuotaInsufficeientReSearch()"
					qtipkey="quota_insufficient_re_search">
					<span class="egicon-refresh" aria-hidden="true"></span>
    		<?= TranslateHelper::t('重新查询')?>
    	</button>
		<?php endif;?>
		
		<?php if (! empty($_GET['parcel_classification']) && $_GET['parcel_classification']!='completed_parcel'  && empty($_GET['pos'])):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchMarkCompleted()">
					<span class="glyphicon glyphicon-ok-circle" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量移到已完成')?>
    	</button>
		<?php endif;?>
		
		<?php if (! empty($_GET['parcel_classification']) && ($_GET['parcel_classification']=='no_info_parcel' || $_GET['parcel_classification']=='unshipped_parcel')  && empty($_GET['pos'])):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchSetCarrierType()" 
					qtipkey="tracker_batch_set_carrier_type">
					<span class="egicon-binding" aria-hidden="true" style="margin-bottom:2px;"></span>
    		<?= TranslateHelper::t('指定查询的渠道')?>
    	</button>
		<?php endif;?>
		<?php if (! empty($_GET['parcel_classification']) && empty($_GET['pos']) && $_GET['parcel_classification']!='ignored_parcel'):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchMarkIgnore()">
					<span class="iconfont icon-ignore_search" aria-hidden="true"></span>
    		<?= TranslateHelper::t('批量忽略(不再查询)')?>
    	</button>
    	<?php endif;?>
    	
    	<?php if (! empty($_GET['parcel_classification']) && empty($_GET['pos']) && $_GET['parcel_classification']=='ignored_parcel'):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.ignoredReSearch()">
					<span class="egicon-refresh" aria-hidden="true"></span>
    		<?= TranslateHelper::t('恢复查询')?>
    	</button>
    	<?php endif;?>
    	
    	<?php if (!empty($_GET['pos'])):?>
		<button type="button"  class="btn-xs btn-transparent font-color-1"
					onclick="ListTracking.batchMarkIsSent()">
					<span class="egicon-envelope" aria-hidden="true"></span>
    		<?= TranslateHelper::t('标记为已发当前提醒')?>
    	</button>
		<?php endif;?>
		
		<button type="button"  class="btn btn-info font-color-1" onclick="ListTracking.showQuotaInfo()">
    		<?= TranslateHelper::t('查看配额信息')?>
    	</button>
    </div>
    <?php if($IsShowProgressBar):?>
    <div class="row div_progress">
				
				<div class="col-xs-8">
					<small> <p class='text-right font-color-2'><?= TranslateHelper::t('系统正在努力从平台同步订单/物流号 并且 物流查询中，请稍候:');?>(<span class="progress_p">0/0</span>)</p></small>
				</div>
				<div class="col-xs-4">
					<div id="div_show_progress_bar">
						<div class="progress noBottom">
							<div
								class="progress-bar progress-bar-success progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
							<div
								class="progress-bar progress-bar-danger progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
							<div
								class="progress-bar progress-bar-primary progress-bar-striped active"
								style="width: 0%">
								<span></span>
							</div>
						</div>
						
					</div>
				</div>
			</div>
    <?php endif;?>

		<!-- Table -->
		<table class="table">
			<thead>
			<tr>
				<th><input type="checkbox" id="chk_all"></th>
				<th qtipkey="tracker_tracking_no"  ><?= $sort->link('track_no',['label'=>TranslateHelper::t('物流号 ')])?></th>
				<th qtipkey="tracker_order_id" ><?= $sort->link('order_id',['label'=>TranslateHelper::t('订单号 ')])?></th>
				<!-- 
				<th>
				 
				<select name="sellerid" class="table_head_select">
					<option value=""><?= TranslateHelper::t('平台账号 ')?></option>
					<?php 
					if (!empty($account_data)){
					foreach($account_data as $row):?>
						<option value="<?= $row['name']?>"  <?php if (! empty($_GET['sellerid'])) if ($_GET['sellerid']==$row['name']) echo " selected " ?>><?= $row['name']?></option>
					<?php endforeach;
					}?>
				</select> 
				</th>
				 -->
				<th  style="width: 90px;position: relative;">
				
					<span><?= (!empty($_GET['to_nations'])) ? $_GET['to_nations']: TranslateHelper::t('国家')?></span>
					
					<div class="btn-group ">
						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
							<span class="glyphicon glyphicon-menu-down"></span>
						</a>
						<ul class="dropdown-menu" data-selname="to_nations" role="menu">
							<li><?= TranslateHelper::t('国家')?></li>
							<?php 
							if (!empty($country_list)){
							foreach($country_list as $code=>$label):?>
								<li<?php if (! empty($_GET['to_nations'])) if ($_GET['to_nations']==$code) echo ' class="active" '?>><?= $label.'-'.$code?></li>
							<?php endforeach;
							}?>
						</ul>
						
					</div>
				
				<select name="to_nations"  class="table_head_select"  style="display: none;">
				
					<option value=""><?= TranslateHelper::t('国家 ')?></option>
					<?php foreach($country_list as $code=>$label):?>
					<option value="<?= $code?>" <?php if (! empty($_GET['to_nations'])) if ($_GET['to_nations']==$code) echo " selected " ?>><?= $label?></option>
					<?php endforeach;?>
				</select>
				
				
				</th>
			<?php if (empty($_GET['platform'])){?>
			<th style="width: 100px;"><?=TranslateHelper::t('订单付款日期 ')?></th>
			<?php }else{?>
			<th style="width: 90px;"><?= $sort->link('ship_out_date',['label'=>TranslateHelper::t('订单日期 ')])?></th>
			<?php }?>
			<th  style="width: 90px;" qtipkey="tracker_update_time" <?= $sort->link('update_time',['label'=>TranslateHelper::t('更新时间')])?></th>
				
				<th qtipkey="tracker_ship_by"  style="position: relative;">
					<span><?= (!empty($_GET['ship_by'])) ? $_GET['ship_by']: TranslateHelper::t('物流商')?></span>
					
					<div class="btn-group ">
						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
							<span class="glyphicon glyphicon-menu-down"></span>
						</a>
						<ul class="dropdown-menu" data-selname="ship_by" role="menu">
							<li><?= TranslateHelper::t('物流商')?></li>
							<?php 
							if (!empty($using_carriers)){
							foreach($using_carriers as $row):?>
								<li<?php if (! empty($_GET['ship_by'])) if ($_GET['ship_by']==$row) echo ' class="active" '?>><?= $row?></li>
							<?php endforeach;
							}?>
						</ul>
						
					</div>
					
					<select name="ship_by" class="table_head_select" style="display: none;">
						<option value=""><?= TranslateHelper::t('物流商')?></option>
						<?php 
						if (!empty($using_carriers)){
						foreach($using_carriers as $row):?>
							<option value="<?= $row?>"  <?php if (! empty($_GET['ship_by'])) if ($_GET['ship_by']==$row) echo " selected " ?>><?= $row?></option>
						<?php endforeach;
						}?>
					</select> 
				</th>
				<th style="width: 100px;position: relative;"  qtipkey="tracker_status"  >
					<span><?= (!empty($_GET['select_parcel_classification'])) ? $parcel_label_mapping[$_GET['select_parcel_classification']]: TranslateHelper::t('包裹状态')?></span>
					
					<div class="btn-group ">
						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
							<span class="glyphicon glyphicon-menu-down"></span>
						</a>
						<ul class="dropdown-menu" data-selname="select_parcel_classification" role="menu">
							<li><?= TranslateHelper::t('包裹状态')?></li>
							<?php 
							if (!empty($parcel_label_mapping)){
							foreach($parcel_label_mapping as $parcel_name=>$label):?>
								<li<?php if (! empty($_GET['select_parcel_classification'])) if ($_GET['select_parcel_classification']==$parcel_name) echo ' class="active" '?>><?= $label?></li>
							<?php endforeach;
							}?>
						</ul>
						
					</div>
				
				
					<select name="select_parcel_classification" class="table_head_select"  style="display: none;">
						<option value=""><?= TranslateHelper::t('包裹状态')?></option>
						<?php 
						if (!empty($parcel_label_mapping)){
						foreach($parcel_label_mapping as $parcel_name=>$label):?>
							<option value="<?= $parcel_name?>"  <?php if (! empty($_GET['select_parcel_classification'])) if ($_GET['select_parcel_classification']==$parcel_name) echo " selected " ?>><?= $label?></option>
						<?php endforeach;
						}?>
					</select> 
				</th>
				<!-- liang 2015-07-10 start-->
				<th style="width: 90px;position: relative;"  qtipkey="tracker_stay_days" >
					<?= $sort->link('stay_days',['label'=>TranslateHelper::t('停留时间 ')])?>
				</th>
				<!-- liang 2015-07-10 end-->
				<!--  <th><?= $sort->link('notified_seller',['label'=>TranslateHelper::t('已发送提醒邮件 ')])?></th>-->
				<th style="white-space: nowrap;"><?= TranslateHelper::t('操作')?> <span class="egicon-question-sign-blue"></span></th>
			</tr>
			</thead>
			<tbody>
		<?php $rowIndex = 1;?>
		<?php foreach($TrackingData['data'] as $oneTracking):?>
		
		<?php 
		$divTagHtml .= '<div id="div_tag_'.$oneTracking['id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
		
		echo "<tr id=\"tr_info_".$oneTracking['track_no']."\" data-track-id=\"".$oneTracking['id']."\" track_no=\"".$oneTracking['track_no']."\"".(($rowIndex++ % 2) == 0 ? " class='striped-row'" : "")." >";
		$isPlatform = (isset($_GET['platform']));
		$result =  TrackingHelper::generateQueryTrackingInfoHTML($oneTracking,$isPlatform);
		//$remark = json_decode($oneTracking['remark'],true);
		echo $result[$oneTracking['track_no']];
		echo "</tr>";
		
		$div_event_html .= "<div id='div_more_info_".$oneTracking['id']."' class='div_more_tracking_info div_space_toggle'>";
		
			
		$all_events_str = "";
			
		$all_events_rt = TrackingHelper::generateTrackingEventHTML([$oneTracking['track_no']],[],true);
		if (!empty($all_events_rt[$oneTracking['track_no']])){
			$all_events_str = $all_events_rt[$oneTracking['track_no']];
		
		
		}
			
		$div_event_html .=  $all_events_str;
		
		$div_event_html .= "</div>";
		
		/*khcomment20150610 跟psd模式 
		echo "<tr>".
					"<td colspan='".(count($sortAttr)+2) ."'  valign='top' class= 'td_space_toggle'>".
						"<div class='row div_space_toggle'> <div class='col-xs-9'>".
						"<div id='div_more_info_".$oneTracking['track_no']."' class='div_more_tracking_info'>";
						
							
							$all_events_str = "";
							
							$all_events_rt = TrackingHelper::generateTrackingEventHTML([$oneTracking['track_no']]);
							if (!empty($all_events_rt[$oneTracking['track_no']])){
								$all_events_str = $all_events_rt[$oneTracking['track_no']];
								
								
							}
							
							echo  $all_events_str;
							//echo "<div class=\"clearfix\" > <input class='btn btn-default' value='".TranslateHelper::t('翻译成中文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$oneTracking['track_no']."','zh-cn')\"/>   </div>";
							echo "</div> </div>";
							echo "<div class='col-xs-3'>";
							echo TrackingHelper::generateRemarkHTML($oneTracking['remark']);
							echo "</div>";
							echo "</div>".
					"</td>";
		echo "</tr>";
		*/
		endforeach;?>
		</tbody>
		</table>

</form>

<?php
//exit(print_r($TrackingData,true));
if(! empty($TrackingData['pagination'])):?>
<div >

    <div class="btn-group" >
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $TrackingData['pagination'],'options'=>['class'=>'pagination']]);?>
    
	</div>
	<?= \eagle\widgets\SizePager::widget(['pagination'=>$TrackingData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
</div>
<?php endif;?>


<dl class="qtip-operation-msg">
	<dt><span class="egicon-refresh"></span><small class="font-color-2"><?= TranslateHelper::t('刷新')?></small></dt>
	<dt><span class="egicon-eye"></span><small class="font-color-2"><?= TranslateHelper::t('查看物流')?></small></dt>
	<dt><span class="egicon-notepad"></span><small class="font-color-2"><?= TranslateHelper::t('订单详情')?></small></dt>
	<dt><span class="egicon-memo-blue"></span><small class="font-color-2"><?= TranslateHelper::t('物流备注 ')?></small></dt>
	<dt><span class="egicon-envelope"></span><small class="font-color-2"><?= TranslateHelper::t('发送站内信')?></small></dt>
	<dt><span class="egicon-trash"></span><small class="font-color-2"><?= TranslateHelper::t('删除')?></small></dt>
	<dt><span class="egicon-ok-blue"></span><small class="font-color-2"><?= TranslateHelper::t('标记已处理')?></small></dt>
</dl>

<?= $divTagHtml;?>
<?= $div_event_html;?>

<div class="" id="dash-board-enter" onclick="showDashBoard(0)" style="background-color:#374655;color: white;" title="展开dash-board">展开物流监控面板</div>

<div class="dash-board"></div>
<div class="report-no-info-win"></div>
<div class="17track-trackin-info-win"></div>
<div class="set-carrier-type-win"></div>
<div class="set-ignore-carriers-win" style="display: none"></div>

<script type="text/javascript">
function showDashBoard(autoShow){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/user-dash-board?autoShow='+autoShow, 
		success: function (result) {
			$.hideLoading();
			$("#dash-board-enter").toggle();
			bootbox.dialog({
				className : "dash-board",
				title: Translator.t('Tracker物流跟踪'),
				message: result,
				closeButton:false,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开物流监控面板失败'));
			return false;
		}
	});
}

function reportTrackerNoInfo(id){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/report-no-info?id='+id, 
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "report-no-info-win",
				title: Translator.t('物流反馈'),
				message: result,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开提交界面失败,后台返回异常'));
			return false;
		}
	});
}

function ignoreTrackingNo(id){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/ignore-tracking-no?id='+id,
		dataType:'json',
		success: function (result) {
			if(result.success===true){
				bootbox.alert({
		            message: '操作成功',  
		            callback: function() {  
		            	ListTracking.refreshTrackingTr(id);
		            },  
				});
			}else{
				bootbox.alert(result.message);
				return false;
			}
			$.hideLoading();
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function iframe_17Track(num, obj){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/show17-track-tracking-info?num='+num,
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "17track-trackin-info-win",
				title: Translator.t('17track查询结果'),
				message: result,
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function doTrack(num) {
    if(num===""){
        alert("Enter your number."); 
        return;
    }
    YQV5.trackSingle({
        YQ_ContainerId:"YQContainer",       //必须，指定承载内容的容器ID。
        YQ_Height:400,      //可选，指定查询结果高度，最大高度为800px，默认撑满容器。
        YQ_Fc:"0",       //可选，指定运输商，默认为自动识别。
        YQ_Lang:"zh-cn",       //可选，指定UI语言，默认根据浏览器自动识别。
        YQ_Num:num     //必须，指定要查询的单号。
    });
}
</script>
