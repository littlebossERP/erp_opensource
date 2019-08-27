<?php 
use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\widgets\LinkPager;
use eagle\modules\message\helpers\CustomerTagHelper;
// print_r($connect);

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/message/customer_message.css");
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJs("orderList()", \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJs("CustomerTag.init()", \yii\web\View::POS_READY);//初始化qtipkey
$this->registerJs("CustomerTag.initTicket()", \yii\web\View::POS_READY);//初始化qtipkey
$this->registerJs("CustomerTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
// print_r($letters);exit();

$this->title = TranslateHelper::t('客服管理');
?>
<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<div class="tracking-index col2-layout">

	<?= 
// 	$this->render('left_menu',['no_answer'=>$no_answer]) 
	$this->render('new_menu',['no_answer'=>$no_answer]);
	?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	   <form action="/message/all-customer/show-letter" method="GET">
	   	   <?php 
				$message_tag=array();
   	   			if(!empty($tags)){
   	   				foreach ($tags as $tag){
   	   					$message_tag[$tag->tag_id]=$tag->tag_name;
   	   				}
   	   			}
	   	   ?>
	   	   <?=Html::dropDownList('message_tag',@$_REQUEST['message_tag'],$message_tag,['onchange'=>"message_tag_action($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;float:left;margin-right:10px;','prompt'=>'所有标签'])?>
	       <div>最后消息发生于&nbsp;
	           <?php
//     	           $account=["ebay"=>"ebay","速卖通帐号"=>"速卖通帐号"];
                   if($platform != ''&&$platform=='cdiscount'){//对cd的单类型有点不一样
                       $type=["O"=>"Your claims","Q"=>"Orders questions"];
                       $session_type = [
                           'PackageNotReceived'=>'PackageNotReceived',
                           'IncompletePackage'=>'IncompletePackage',
                           'MissingAccessories'=>'MissingAccessories',
                           'DamagedProduct'=>'DamagedProduct',
                           'WrongProductReference'=>'WrongProductReference',
                           'WrongRefundAmount'=>'WrongRefundAmount',
                           'RefundNotReceived'=>'RefundNotReceived',
                           'ProductNotWorking'=>'ProductNotWorking',
                           'Other'=>'Other',
                           'ProductCanceling'=>'ProductCanceling',
                           'OrderCanceling'=>'OrderCanceling',
                           'WishToWithdraw'=>'WishToWithdraw',
                           'OrderNotShipped'=>'OrderNotShipped',
                       ];
                       $session_status = [
                           'Open'=>'Open',
                           'Closed'=>'Closed',
                           'NotProcessed'=>'NotProcessed',
                       ];
                       $read=["0"=>"未回复","1"=>"已回复","2"=>"未回复(异常状态)"];
                   }else{
                       $type=["P"=>"商品","O"=>"订单","S"=>"系统平台"];
                       $read=["0"=>"未回复","1"=>"已回复"];
                   }
    	           
    	           $account=array();
    	           foreach ($accounts as $seller_id){
    	               $account[$seller_id->seller_id]=$seller_id->seller_id;
    	               
    	               if(in_array($seller_id->platform_source, array('amazon', 'cdiscount', 'customized','aliexpress','ebay','wish'))){
    	               	if(isset($selleruserids[$seller_id->platform_source][$seller_id->seller_id])){
    	               		$account[$seller_id->seller_id] = $selleruserids[$seller_id->platform_source][$seller_id->seller_id];
    	               	}else{
    	               		$account[$seller_id->seller_id]=$seller_id->seller_id;
    	               	}
    	               }else{
    	               	$account[$seller_id->seller_id]=$seller_id->seller_id;
    	               }
    	           }
    	           
    	       ?>
    	       
    	       <input type="text" id="letterstartdate" name="letterstartdate" class="eagle-form-control" style="width:90px;" value="<?php echo (!empty($save['letter_startdate']))?$save['letter_startdate']:null;?>" placeholder="起始时间">&nbsp;<?= TranslateHelper::t('到')?>
    	       <input type="text" id="letterenddate" name="letterenddate" class="eagle-form-control" style="width: 90px;" value="<?php echo (!empty($save['letter_enddate']))?$save['letter_enddate']:null;?>" placeholder="截止时间">
    	       <input type="text" size='30' name="letter_search" id="letter_search" class="eagle-form-control" value="<?php echo (!empty($save['search']))?$save['search']:null;?>" placeholder="查找用户名或用户帐号或订单号或SKU">&nbsp;<input type="submit" value="搜索" class="btn btn-success btn-sm">
	           <input type="hidden" name="select_platform" value="<?php echo !empty($_REQUEST['select_platform'])?$_REQUEST['select_platform']:null;?>">
	           <input type="hidden" name="select_type" value="<?php echo !empty($_REQUEST['select_type'])?$_REQUEST['select_type']:null;?>">
	           <input type="hidden" name="selected_type" value="<?php echo !empty($_REQUEST['selected_type'])?$_REQUEST['selected_type']:null;?>">
	       
	       </div>
	       <!-- 批量操作按钮 -->
		   <div>
			<button type="button" class="btn-xs btn-transparent" onclick="CustomerTag.batchUpateToHasRead()" style="border:1px solid;margin-bottom:10px;font-size:14px;">
				<span class="egicon-reply-msg" aria-hidden="true" style="color:red;height:16px"></span>
				<?= TranslateHelper::t('批量标记为已读')?>
			</button>
		   </div>
		   <?php 
    	   	   $listKeys = [];
    	   	   foreach ($letters as $letter){
					$oneKeys = [];
    	   	   		$oneKeys['seller_id'] = $letter['seller_id'];
    	   	   		$oneKeys['customer_id'] = $letter['buyer_id'];
    	   	   		$oneKeys['platform_source'] = $letter['platform_source'];
    	   	   		$oneKeys['ticket_id'] = $letter['ticket_id'];
    	   	   		$oneKeys['related_type'] = $letter['related_type'];
    	   	   		$oneKeys['os_flag'] = '0';
    	   	   		$oneKeys['msg_sent_error'] = '';
    	   	   		$oneKeys['status'] = '';
    	   	   		$listKeys[] = $oneKeys;
    	   	   }
    	   	   $upOrDownText = base64_encode(json_encode($listKeys));
    	   	   echo "<input id='upOrDownText' type='hidden' value='$upOrDownText'>";
    	   ?>
	       <table class="table">
	       <thead>
    	       <tr>
    	           <th style="text-align: left; vertical-align: middle;"><input type="checkbox" id="chk_all" ><label for="chk_all">会话编号</label></th>
    	           <th style="text-align: left; width:215px; vertical-align: middle;">最后信息</th>
    	           <th style="text-align: left; width:170px;  position: relative; vertical-align: middle;">
    	               <span><?= (!empty($_GET['type'])) ? $type[$_GET['type']]: TranslateHelper::t('相关内容')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="type" role="menu">
    							<li><?= TranslateHelper::t('相关内容')?></li>
    							<?php 
    							if (!empty($type)){
    							foreach($type as $code=>$label):?>
    								<li<?php if (! empty($_GET['type'])) if ($_GET['type']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="type"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('相关内容 ')?></option>
        					<?php foreach($type as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['type'])) if ($_GET['type']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>
        				
    	           </th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	                <span><?= (!empty($_GET['account'])) ? $_GET['account']: TranslateHelper::t('平台账户')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="account" role="menu">
    							<li><?= TranslateHelper::t('平台账户')?></li>
    							<?php 
    							if (!empty($account)){
    							foreach($account as $code=>$label):?>
    								<li<?php if (! empty($_GET['account'])) if ($_GET['account']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="account"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('平台账户')?></option>
        					<?php foreach($account as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['account'])) if ($_GET['account']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>     
    	         
    	           </th>
    	           <th style="text-align: left; vertical-align: middle; width:155px;">发言人</th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	               <span><?= (!empty($_GET['read'])) ? $read[$_GET['read']]: TranslateHelper::t('是否回复')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="read" role="menu">
    							<li><?= TranslateHelper::t('是否回复')?></li>
    							<?php 
    							if (!empty($read)){
    							foreach($read as $code=>$label):?>
    								<li<?php if (! empty($_GET['read'])) if ($_GET['read']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="read"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('是否回复')?></option>
        					<?php foreach($read as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['read'])) if ($_GET['read']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>     
    	         
    	           </th>
    	           <th style="text-align: left; vertical-align: middle;">最后消息时间</th>
    	           <?php if($platform != ''&&$platform=='cdiscount'):?>
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	                <span><?= (!empty($_GET['session_type'])) ? $_GET['session_type']: TranslateHelper::t('ClaimType')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="session_type" role="menu">
    							<li><?= TranslateHelper::t('ClaimType')?></li>
    							<?php 
    							if (!empty($session_type)){
    							foreach($session_type as $code=>$label):?>
    								<li<?php if (! empty($_GET['session_type'])) if ($_GET['session_type']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="session_type"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('ClaimType')?></option>
        					<?php foreach($session_type as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['session_type'])) if ($_GET['session_type']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>
    	           </th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	               <span><?= (!empty($_GET['session_status'])) ? $_GET['session_status']: TranslateHelper::t('Status')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="session_status" role="menu">
    							<li><?= TranslateHelper::t('Status')?></li>
    							<?php 
    							if (!empty($session_status)){
    							foreach($session_status as $code=>$label):?>
    								<li<?php if (! empty($_GET['session_status'])) if ($_GET['session_status']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="session_status"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('Status')?></option>
        					<?php foreach($session_status as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['session_status'])) if ($_GET['session_status']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>
    	           </th>
    	           <?php endif;?>
    	           <th style="text-align: left; vertical-align: middle;">操作</th> 
    	       </tr>
	       </thead>
    	   <tbody>
    	        <?php $num=1; foreach ($letters as $letter):?>
                <tr id="letter_no_<?php echo $letter['ticket_id']?>" style="height:64px;" data-id="<?php echo $letter['ticket_id']?>" 
                <?php 
                if($num%2==0&&$letter['has_read']!=0){
                    echo "class='striped-row'";
                }else if($num%2==0&&$letter['has_read']==0){
                    echo "class='striped-row big-font-weight'";
                }else if($num%2!=0&&$letter['has_read']==0){
                    echo "class='big-font-weight'";
                }else{
                    echo null;
                }
                $num++;
                ?>>

                <?php
                    $flag_data=CustomerTagHelper::getALlTagDataByTicketMessageId($letter['ticket_id']);//查找相关用户的便签
                    $all_flag=$flag_data['all_tag'];                //所有已设置标签
                    $selected_flag=$flag_data['all_select_tag_id'];//已选择的标签

                    echo $this->render('ticket_message_tr_info',['letter'=>$letter,'all_flag'=>$all_flag,'selected_flag'=>$selected_flag,'accounts'=>$account,'selleruserids'=>$selleruserids]);
                ?>
                
                </tr>
                <?php endforeach;?>
    	   </tbody>
    	   </table>
	    </form>	
	    <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
            
        	</div>
	            <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
        </div>
    <?php foreach ($letters as $letter):?>
        <div id="div_tag_<?=$letter['ticket_id']?>" name="div_add_tag" class="div_space_toggle" style="width:600px;"></div>
    <?php endforeach;?>  
	</div>
</div>
<div class="17track-trackin-info-win"></div>
<script>
function message_tag_action(val){
	$("form").submit();		   
}
function iframe_17Track(num){
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