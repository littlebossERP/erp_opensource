<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$tmp_js_version = '2.12';
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/carrier.js?v=".$tmp_js_version);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shipping.js?v=".$tmp_js_version);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/index.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/address.js?v=".$tmp_js_version);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/shippingRules.js?v=".$tmp_js_version);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/warehouseconfig/overseawarehouselist.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/trackwarehouse.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/insertTrack.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/trackwarehouse/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/custom.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/excelFormat.js");
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/custom/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/carrierconfig/custom/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/carrierconfig/custom/excelFormat.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/bootstrap.min.js");

$this->registerCssFile($baseUrl."css/configuration/carrierconfig/switch.css");

if(!empty($open_carriers)){
	$codes_key = isset($_GET['codes_key'])?$_GET['codes_key']:$codes_key;
	$open = @$customCarrier['is_used'];
	$carrier_type = $customCarrier['carrier_type'];
}

$isset = false;

$this->registerJsFile($baseUrl."js/project/util/select_country.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
.alert {
    margin-bottom: 5px;
}

.right_content_sys {
  padding: 0px 30px 30px 170px;
  -webkit-flex: 1;
  flex: 1;
}
.right_content_sys .block {
  margin-top: 20px;
  margin-bottom: 20px;
}
.right_content_sys .block.middle {
  text-align: center;
}
.right_content_sys h3 {
  margin: 20px 0;
  font-weight: bold;
  padding-left: 6px;
  border-left: 3px solid #01bdf0;
}

.nav-pills > li.active > a, .nav-pills > li.active > a:hover, .nav-pills > li.active > a:focus {
    color: #fff;
    background-color: #01bdf0;
}

.partDIV{
	font-family: 'Applied Font Regular', 'Applied Font';
	font-size:13px;
	height:25px;
	line-height:1.5;
	padding:2px 10px;
	color:black;
}
.partDIV>div{
	float:left;
	margin:0 12px 5px 0;
	height:25px;
}
.partDIV>div input{
	height:22px;
 	line-height:1.5;
 	width:128px;
}
.partDIV select{
	height:22px;
	line-height:22px;
	width:128px;
}
#searchBtn{
 	width:100px;
	padding-top:2px;
}
.showtable{
	border-collapse: collapse;
	border: 1px solid #797979;
	font-size: 13px;
    color: rgb(51, 51, 51);
}
.showtable>thead>tr>th{
	text-align:center;
	background:white;
	border: 1px solid #797979;
}
.showtable>tbody>tr>td{
	text-align:center;
	border: 1px solid #797979;
}
.btn-up{
	width:30%;
}
.btn-down{
	width:30%;
}
.btn-null{
	width:30%;
}
.index_li_a{
	font-size: 13px;
	font-weight: bold;
}
.nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus    {
	color: #337ab7;
}
.nav-tabs > li > a{
	color: #999;
}

.p0 {
    padding: 0;
}
.p5 {
    padding: 5px;
}
.pTop11 {
    padding-top: 11px;
}
.mTop4 {
    margin-top: 4px;
}
.mTop10 {
    margin-top: 10px;
}
.mLeft10 {
    margin-left: 10px;
}
.mLeft15 {
    margin-left: 15px;
}
.mLeft20 {
    margin-left: 20px;
}
.mLeft30 {
    margin-left: 30px;
}
.bgColor5 {
    background-color: #eee;
}
.bgColor8 {
    background-color: #ccc;
}
.bgColor9 {
    background-color: #66cc00;
}
.liMousover {
    cursor: pointer;
}
.caretRight, .caretDown {
    width: 40px;
    height: auto;
    border: none;
    color: #428bca;
}
.caretDown {
    margin-left: 2px;
    cursor: pointer;
}
.display{
	display:none;
}
.pull-right {
    float: right!important;
}
.myj-hide {
    display: none;
}

</style>

<?php echo $this->render('../leftmenu/_leftmenu');?>

<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>
    
<?= Html::hiddenInput('ThisType','apiCarrier')?>

<input type="hidden" name="tab_active" id="search_tab_active" value='<?=$tab_active ?>'>
<input type="hidden" id="search_carrier_code" value='<?=@$_REQUEST['tcarrier_code'] ?>'>
<div>
	<!-- tab panes start -->
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist" style="height:42px;">
			<li role="presentation" class="<?php echo $tab_active==='apicarrier' ? 'active' : '';?> sys">
				<a class='tablist_class index_li_a' value='' href="#apicarrier" role="tab" data-toggle="tab" data="apicarrier" >系统对接物流商</a>
			</li>
			<li role="presentation" class="<?php echo $tab_active==='oversea' ? 'active' : '';?> self">
				<a class='tablist_class index_li_a' value='self' href="#oversea" role="tab" data-toggle="tab" data="oversea" >海外仓</a>
			</li>
			<li role="presentation" class="<?php echo $tab_active==='customtracking' ? 'active' : '';?> self">
				<a class='tablist_class index_li_a' value='self' href="#customtracking" role="tab" data-toggle="tab" data="customtracking" >自定义物流商</a>
			</li>
			<li role="presentation" class="<?php echo $tab_active==='trackwarehouse' ? 'active' : '';?> self">
				<a class='tablist_class index_li_a' value='self' href="#trackwarehouse" role="tab" data-toggle="tab" data="trackwarehouse">号码池</a>
			</li>
		</ul>
		<!-- Tab panes -->
		<div class="tab-content">
			<!-- 系统对接物流商 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='apicarrier' ? 'active' : '';?>" id="apicarrier">
				 <div style='margin-top:5px;'><span>搜索内容:&nbsp;</span><input id='search' type='text' onkeypress="if(event.keyCode==13) {search();return false;}">&nbsp;<button id='searchbutton' type='button' onclick='search()' class='iv-btn btn-search title-button'>搜索</button></div>
				<div class="col-xs-12 p0" id="agentBody" style="font-size: 13px;">
					<?php
							foreach ($openCarrierArr as $carrier_code => $carrier_name){
					?>
				  		<div class="col-xs-12 bgColor5 mTop10 p5">
							<div class="pull-left pTop11 liMousover logisticsName searchitem" name="<?=$carrier_code ?>">
								<span class="caretData caretRight" style="">+ 展开</span><span class="caretData caretDown" style="display:none;">- 收起</span>
								<span class="mLeft10" style="display:inline-block;width:150px;"><?=$carrier_name['carrier_name']?></span>	
							</div>
							<div class="pull-left pTop11" id="agentAuthDiv440">
								<span class="mLeft10 <?php echo $carrier_name['is_active']!=1?'bgColor8':'bgColor9' ?> p5"><?php echo $carrier_name['is_active']!=1?'未授权':'已授权'  ?></span>
							</div>
							<div class="pull-left mLeft20" style="width: 170px;">
								<button class="iv-btn btn-important mTop4" type="button" onclick='
								<?php echo $carrier_name["is_active"]==0?'openCarrier("'.$carrier_code.'")':($carrier_name["is_active"]==1?'$.openModal("/configuration/carrierconfig/editaccount",{id:"",code:"'.$carrier_code.'"},"新建物流账号","post")':'$.openModal("/configuration/carrierconfig/newaccount",{id:"",code:"'.$carrier_code.'"},"新建物流账号","post")'); ?>
								'><?php echo $carrier_name['is_active']==0?'启用授权':'添加授权' ?></button>
								<?php if($carrier_name['is_active']==1){?>
									<button class="iv-btn btn-important mTop4" type="button" onclick="closeCarrier('<?=$carrier_code ?>');">取消授权</button>
								<?php }?>
							</div>
							<?php
							if(!empty($carrier_name['help_url'])){
							?>
							<div class="pull-left mLeft30" style="line-height:34px;width:90px">
								<a target="_blank" href='<?=$carrier_name['help_url'] ?>'>查看授权帮助</a>
							</div>
							<?php 
							}
							?>
							<div class="pull-right mLeft15" style="line-height:34px;width:<?=(in_array($carrier_code,['lb_CNE','lb_yide'])) ? 110 : 80 ?>px;height:32px;">
							<?php 
									if(isset($carrier_name['carrierContactArr']['qq']) && !empty($carrier_name['carrierContactArr']['qq'])){

										if(in_array($carrier_code,['lb_CNE','lb_yide'])){
// 											echo 'QQ:'.$carrier_name['carrierContactArr']['qq'];
											?>
											QQ: <a target="_blank" href="http://wpa.qq.com/msgrd?v=3&amp;uin=<?php echo $carrier_name['carrierContactArr']['qq']; ?>&amp;site=qq&amp;menu=yes"><?=$carrier_name['carrierContactArr']['qq'] ?></a>
											<?php 
										}else{
											if(isset($carrier_name['carrierContactArr']['qqtype']) && $carrier_name['carrierContactArr']['qqtype']==1){
											?>
											<a target="_blank" href="http://crm2.qq.com/page/portalpage/wpa.php?uin=<?php echo $carrier_name['carrierContactArr']['qq']; ?>&amp;aty=1&amp;a=0&amp;curl=&amp;ty=1"><img style="vertical-align: middle;margin-top: -4px;" border="0" src="/images/qqtalk.png">QQ交谈</a>
											<?php }else{ ?>
											<a target="_blank" href="http://wpa.qq.com/msgrd?v=3&amp;uin=<?php echo $carrier_name['carrierContactArr']['qq']; ?>&amp;site=qq&amp;menu=yes"><img style="vertical-align: middle;margin-top: -4px;" border="0" src="/images/qqtalk.png">QQ交谈</a>
											<?php }
										}
									} ?>
							</div>
							<div class="pull-right mLeft15" style="line-height:34px;">
							<?php 
									if(isset($carrier_name['carrierContactArr']['telContact']) && !empty($carrier_name['carrierContactArr']['telContact'])){
										?>电话：<?php echo $carrier_name['carrierContactArr']['telContact']; ?>
							<?php 	} ?>
							</div>
							<div class="pull-right" style="line-height:34px;">
							<?php 
									if(isset($carrier_name['carrierContactArr']['pickupAddress']) && !empty($carrier_name['carrierContactArr']['pickupAddress'])){
										?>揽收城市：<?php echo $carrier_name['carrierContactArr']['pickupAddress']; ?>
							<?php 	} ?>
							</div>
							<!-- 广告位 -->
						</div>
						<div class="col-xs-12 p0 mTop10 myj-hide" id="syscarrier_show_div_<?=$carrier_code ?>"></div>
						<?php }?>
				</div>
			</div>
			<!-- 海外仓 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='oversea' ? 'active' : '';?>" id="oversea">
				<div style='margin-top:5px;'><span>搜索内容:&nbsp;</span><input id='search2' type='text' onkeypress="if(event.keyCode==13) {search(2);return false;}">&nbsp;<button id='searchbutton' type='button' onclick='search(2)' class='iv-btn btn-search title-button'>搜索</button></div>
				<div class="col-xs-12 p0" id="agentBody" style="font-size: 13px;">
					<?php //print_r($warehouseIdNameMap);die;
						if (!empty($warehouseIdNameMap)){ 
							foreach ($warehouseIdNameMap as $warehouse_code => $warehouse_name){
					?>
				  		<div class="col-xs-12 bgColor5 mTop10 p5" style="<?=!empty($_REQUEST['search_carrier_code_post']) && $_REQUEST['search_carrier_code_post'] != $warehouse_name['carrier_code'] ? "display:none" : "" ?>">
							<div class="pull-left pTop11 liMousover overseaName searchitem2" name="<?php echo $warehouse_name['carrier_code'].'_'.$warehouse_name['third_party_code']; ?>" data="<?php echo $warehouse_name['warehouse_id']; ?>">
								<span class="caretData caretRight" style="">+ 展开</span><span class="caretData caretDown" style="display:none;">- 收起</span>
								<span class="mLeft10" style="display:inline-block;width:230px;"><?=$warehouse_name['carrier_name']?></span>	
							</div>
							<div class="pull-left pTop11" id="agentAuthDiv440">
								<span class="mLeft10 <?php echo $warehouse_name['is_active']!="1"?'bgColor8':'bgColor9' ?> p5"><?php echo $warehouse_name['is_active']!="1"?'未授权':'已授权'  ?></span>
							</div>
							<div class="pull-left mLeft20" style="width: 170px;">
								<button class="iv-btn btn-important mTop4" type="button" onclick='
								<?php echo $warehouse_name["is_active"]=="0"?'closeWarehouse("'.$warehouse_name['warehouse_id'].'","Y","'.$warehouse_name['carrier_code'].'_'.$warehouse_name['third_party_code'].'")':($warehouse_name["is_active"]=="1"?'$.openModal("/configuration/warehouseconfig/add-or-edit-orversea-carrier-account",{type:"add",carrier_code:"'.$warehouse_name['carrier_code'].'",warehouse_id:"'.$warehouse_name['warehouse_id'].'",third_party_code:"'.$warehouse_name['third_party_code'].'"},"添加物流账号","get")':'$.openModal("/configuration/carrierconfig/newaccount",{id:"",code:"'.$warehouse_name['carrier_code'].':'.$warehouse_name['third_party_code'].'",type:"1",oversea:"1",hidwarehouse:"'.$warehouse_name['carrier_name'].'"},"新建海外仓","post")'); ?>
								'><?php echo $warehouse_name['is_active']=="0"?'启用授权':'添加授权' ?></button>
								<?php  if($warehouse_name['is_active']=="1"){
									echo '<button class="iv-btn btn-important mTop4" type="button" onclick="closeWarehouse(\''.$warehouse_name['warehouse_id'].'\',\'N\',\''.$warehouse_name['carrier_code'].'_'.$warehouse_name['third_party_code'].'\');">取消授权</button>';
								} ?>
							</div>
							<!-- 广告位 
							<div class="pull-left mLeft30" style="line-height:34px;width:90px">
								<a target="_blank">查看授权帮助</a>
							</div>-->
							<div class="pull-right" style="line-height:34px;width:80px;height:32px;">
							<?php 
									if(isset($warehouse_name['carrierContactArr']['qq']) && !empty($warehouse_name['carrierContactArr']['qq'])){
										?>
										<a target="_blank" href="http://wpa.qq.com/msgrd?v=3&amp;uin=<?php echo $warehouse_name['carrierContactArr']['qq']; ?>&amp;site=qq&amp;menu=yes"><img style="vertical-align: middle;margin-top: -4px;" border="0" src="/images/qqtalk.png">QQ交谈</a>
							<?php 	} ?>
							</div>
							<div class="pull-right mLeft15" style="line-height:34px;width:130px;height:32px;">
							<?php 
									if(isset($warehouse_name['carrierContactArr']['telContact']) && !empty($warehouse_name['carrierContactArr']['telContact'])){
										?>电话：<?php echo $warehouse_name['carrierContactArr']['telContact']; ?>
							<?php 	} ?>
							</div>
							<div class="pull-right" style="line-height:34px;height:32px;">
							<?php 
									if(isset($warehouse_name['carrierContactArr']['pickupAddress']) && !empty($warehouse_name['carrierContactArr']['pickupAddress'])){
										?>揽收城市：<?php echo $warehouse_name['carrierContactArr']['pickupAddress']; ?>
							<?php 	} ?>
							</div>
							<!-- 广告位 -->
						</div>
						<div class="col-xs-12 p0 mTop10 myj-hide" id="show_div_<?php echo $warehouse_name['carrier_code'].'_'.$warehouse_name['third_party_code']; ?>"></div>
						<?php }}?>
				</div>
			</div>
			<!-- 自定义物流商 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='customtracking' ? 'active' : '';?>" id="customtracking">
				<div style='margin-top:5px;'><span>搜索内容:&nbsp;</span><input id='search3' type='text' onkeypress="if(event.keyCode==13) {search(3);return false;}">&nbsp;<button id='searchbutton' type='button' onclick='search(3)' class='iv-btn btn-search title-button'>搜索</button></div>
				<div class="col-xs-12 p0" id="agentBody" style="font-size: 13px;">
				<?= Html::submitButton('添加物流账号', ['style'=>'position: relative;top:5px;','class'=>'iv-btn btn-search title-button','onclick'=>"$.openModal('/configuration/carrierconfig/newaccount',{id:'',code:''},'新建物流商','post')"])?>
					<?php
							foreach ($custom_carriers as $custom_carrier_code => $custom_carrier_name){
					?>
				  		<div class="col-xs-12 bgColor5 mTop10 p5">
							<div class="pull-left pTop11 liMousover customName2 searchitem3" name="<?=$custom_carrier_name['carrier_code'] ?>" id="<?=$custom_carrier_name['carrier_code'] ?>" data="<?=$custom_carrier_name['carrier_type']?>">
								<span class="caretData caretRight" style="">+ 展开</span><span class="caretData caretDown" style="display:none;">- 收起</span>
								<span class="mLeft10" style="display:inline-block;width:150px;"><?=$custom_carrier_name['carrier_name']?></span>	
							</div>
							<div class="pull-left pTop11" id="agentAuthDiv440">
								<span class="mLeft10 <?php echo $custom_carrier_name['is_used']!=1?'bgColor8':'bgColor9' ?> p5"><?php echo $custom_carrier_name['is_used']!=1?'未授权':'已授权'  ?></span>
							</div>
							<div class="pull-left mLeft20" style="width: 170px;">
								<?php if($custom_carrier_name['is_used']!=1){ ?>
								<button class="iv-btn btn-important mTop4" type="button" onclick="openOrCloseCustomCarrier('<?=$custom_carrier_name['carrier_code'] ?>',1)">启用授权</button>
								<?php } ?>
								<?php if($custom_carrier_name['is_used']==1){ ?>
								<button class="iv-btn btn-important mTop4" type="button" onclick="openOrCloseCustomCarrier('<?=$custom_carrier_name['carrier_code'] ?>',0)">取消授权</button>
								<?php } ?>
							</div>
							<!-- 广告位 
							<div class="pull-left mLeft30" style="line-height:34px;width:90px">
								<a target="_blank">查看授权帮助</a>
							</div>-->
							<div class="pull-right" style="line-height:34px;width:80px;height:32px;">
							</div>
							<div class="pull-right mLeft15" style="line-height:34px;width:130px;height:32px;">
							</div>
							<div class="pull-right" style="line-height:34px;height:32px;">
							</div>
							<!-- 广告位 -->
						</div>
						<div class="col-xs-12 p0 mTop10 myj-hide" id="excelcarrier_show_div_<?=$custom_carrier_name['carrier_code'] ?>"></div>
						<?php }?>
				</div>
			</div>
			<!-- 号码池 -->
			<div role="tabpanel" class="tab-pane <?php echo $tab_active==='trackwarehouse' ? 'active' : '';?>" id="trackwarehouse">
				<div>
					<form id='searchFORM' action='/configuration/carrierconfig/index?tab_active=trackwarehouse' method='post'>
						<div class="partDIV">
							<div><b>跟踪号：</b><?= Html::textinput('tracking_number',@$data['tracking_number'],['class'=>'iv-input','id'=>'carrier_name'])?></div>
							<div><b>自定义物流商：</b><?= Html::dropdownlist('carrier_name',@$data['carrier_name'],@$carriers,['class'=>'iv-input','id'=>'carrier_name'])?></div>
							<div><b>自定义运输服务：</b><?= Html::dropdownlist('shipping_method_name',@$data['shipping_method_name'],@$methods,['class'=>'iv-input','id'=>'carrier_name'])?></div>
							<div><b>分配状态：</b><?= Html::dropdownlist('is_used',@$data['is_used'],$status,['class'=>'iv-input','id'=>'carrier_name'])?></div>
						</div>
						<div class="partDIV">
							<div><b>创建日期：</b>
								<input class="iv-input" type="date" name="create_timeStart" placeholder="开始时间" max="create_timeEnd" value="<?= @$data['create_timeStart']?>" /> ~ 
								<input class="iv-input" type="date" name="create_timeEnd" placeholder="结束时间" min="create_timeStart" value="<?= @$data['create_timeEnd']?>" />
							</div>
							<div><b>分配日期：</b>
								<input class="iv-input" type="date" name="use_timeStart" placeholder="开始时间" max="use_timeEnd" value="<?= @$data['use_timeStart']?>" /> ~ 
								<input class="iv-input" type="date" name="use_timeEnd" placeholder="结束时间" min="use_timeStart" value="<?= @$data['use_timeEnd']?>" />
							</div>
							<div><b>订单号：</b><?= Html::textinput('order_id',@$data['order_id'],['class'=>'iv-input','id'=>'carrier_name'])?></div>
							<div><?= Html::input('submit','','筛选',['class'=>'iv-btn btn-search btn-spacing-middle','id'=>'searchBtn'])?></div>
						</div>
					</form>
					<div class="col-xs-12" style="margin:7px 0">
						<a class="iv-btn btn-search" onclick="$.modal({url:'/configuration/carrierconfig/insert-track?style=0',method:'get',data:{}},'添加跟踪号',{footer:false,inside:false}).done(function($modal){$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});});">添加跟踪号</a>
					</div>
					<div>
						<table class="table text-center showtable" style="table-layout:fixed;line-height:50px; margin:0;word-break:break-all; word-wrap:break-all;">
							<thead>
								<tr>
									<th><?= TranslateHelper::t('跟踪号')?></th>
									<th><?= TranslateHelper::t('自定义物流商')?></th>
									<th><?= TranslateHelper::t('自定义运输服务')?></th>
									<th><?= TranslateHelper::t('分配状态')?></th>
									<th><?= TranslateHelper::t('创建人')?></th>
									<th><?= TranslateHelper::t('创建日期')?></th>
									<th><?= TranslateHelper::t('分配日期')?></th>
									<th><?= TranslateHelper::t('订单号')?></th>
									<th><?= TranslateHelper::t('操作')?></th>
								</tr>
							</thead>
							<tbody>
								<?php 
									if(!empty($table['data']))
									foreach ($table['data'] as $row){
										$statu = ($row['is_used'])?'已分配':'未分配';
										$creT = empty($row['create_time'])?'':date ('Y-m-d H:i:s',@$row['create_time']);
										$useT = empty($row['use_time'])?'':date ('Y-m-d H:i:s',@$row['use_time']);
								?>
								<tr data="<?= $row['id']?>">
									<td data='no'><?= TranslateHelper::t(@$row['tracking_number'])?></td>
									<td><?= TranslateHelper::t(@$row['carrier_name'])?></td>
									<td><?= TranslateHelper::t(@$row['shipping_method_name'])?></td>
									<td><?= TranslateHelper::t($statu)?></td>
									<td><?= TranslateHelper::t(@$row['user_name'])?></td>
									<td><?= TranslateHelper::t($creT)?></td>
									<td><?= TranslateHelper::t($useT)?></td>
									<td><?= TranslateHelper::t(@$row['order_id'])?></td>
									<td>
										<?php if(!$row['is_used']){?>
										<a class="btn btn-xs setused">标记已分配</a>
										<?php }?>
										<a class="btn btn-xs del">删除</a>
									</td>
								</tr>
								<?php }?>
							</tbody>
						</table>
						
						<?php if($table['pagination']):?>
						<div id="pager-group">
						    <?= \eagle\widgets\SizePager::widget(['pagination'=>$table['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 , 500 ) , 'class'=>'btn-group dropup']);?>
						    <div class="btn-group" style="width: 49.6%; text-align: right;">
						    	<?=\yii\widgets\LinkPager::widget(['pagination' => $table['pagination'],'options'=>['class'=>'pagination']]);?>
							</div>
							</div>
						<?php endif;?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- table panes end -->
</div>