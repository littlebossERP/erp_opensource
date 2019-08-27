<?php 
use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\message\helpers\CustomerTagHelper;
use eagle\modules\message\models\CustomerTags;
use eagle\modules\order\helpers\OrderTrackerApiHelper;


$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/message/customer_message.css");
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("orderList()", \yii\web\View::POS_READY);
$this->registerJs("$.initQtip()", \yii\web\View::POS_READY);//初始化qtipkey
$this->registerJs("CustomerTag.init()", \yii\web\View::POS_READY);//初始化qtipkey
$this->registerJs("CustomerTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);

$this->title = TranslateHelper::t('客服管理');
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\jui\JuiAsset']]);
?>
<div class="tracking-index col2-layout">
	
	<?= 
// 	$this->render('left_menu',['no_answer'=>$no_answer]);
	$this->render('new_menu',['no_answer'=>$no_answer]); 
	?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	   <form action="/message/all-customer/customer-list" method="GET">
	       <div>
	           <?php
//     	           $message_tag=["贵客"=>"贵客","好麻烦的"=>"好麻烦的"];
//     	           $accounts=["ebay账号"=>"ebay账号","速卖通帐号"=>"速卖通帐号"];
//     	           $countrys=["us"=>"US","CN"=>"CN","FR"=>"FR","JP"=>"JP"];
    	           $accounts=array();
    	           $message_tag=array();
//     	           $countrys=array();
    	           foreach ($account as $seller_id){
						if(in_array($seller_id->platform_source, array('amazon', 'cdiscount', 'customized','aliexpress','ebay','wish'))){
							if(isset($selleruserids[$seller_id->platform_source][$seller_id->seller_id])){
								$accounts[$seller_id->seller_id] = $selleruserids[$seller_id->platform_source][$seller_id->seller_id];
							}else{
								$accounts[$seller_id->seller_id]=$seller_id->seller_id;
							}							
						}else{
							$accounts[$seller_id->seller_id]=$seller_id->seller_id;
						}
    	           }
    	           if(!empty($tags)){
    	               foreach ($tags as $tag){
    	                   $message_tag[$tag->tag_id]=$tag->tag_name;
    	               }
    	           }
    	           
//     	           foreach ($nation as $code){
//     	               if($code->nation_code!=null){
//     	                   $countrys[$code->nation_code]=$code->nation_code;
//     	               }     
//     	           }
//     	           $handle=["0"=>"未处理","1"=>"已处理"];
    	       ?>
    	       <?=Html::dropDownList('message_tag',@$_REQUEST['message_tag'],$message_tag,['onchange'=>"message_tag_action($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;','prompt'=>'所有标签'])?>
    	       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>订单日期</span>&nbsp;&nbsp;
    	       <input type="text" id="customer_startdate" name="customer_startdate" value="<?php echo (!empty($save['customer_startdate']))?$save['customer_startdate']:null;?>" class="eagle-form-control" style="width:90px;" placeholder="起始时间">&nbsp;<?= TranslateHelper::t('到')?>
    	       <input type="text" id="customer_enddate" name="customer_enddate" value="<?php echo (!empty($save['customer_enddate']))?$save['customer_enddate']:null;?>" class="eagle-form-control" style="width: 90px;" placeholder="截止时间">
    	       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>订单数</span>&nbsp;&nbsp;
    	       <input type="text" id="order_min" name="order_min" value="<?php echo (!empty($save['order_min']))?$save['order_min']:null;?>" class="eagle-form-control" style="width:90px;" placeholder="最少订单数">&nbsp;<?= TranslateHelper::t('到')?>
    	       <input type="text" id="order_max" name="order_max" value="<?php echo (!empty($save['order_max']))?$save['order_max']:null;?>" class="eagle-form-control" style="width: 90px;" placeholder="最多订单数">
    	       &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span>订单金额</span>&nbsp;&nbsp;
    	       <input type="text" id="amount_min" name="amount_min" value="<?php echo (!empty($save['amount_min']))?$save['amount_min']:null;?>" class="eagle-form-control" style="width:90px;" placeholder="最低金额">&nbsp;<?= TranslateHelper::t('到')?>
    	       <input type="text" id="amount_max" name="amount_max" value="<?php echo (!empty($save['amount_max']))?$save['amount_max']:null;?>" class="eagle-form-control" style="width: 90px;" placeholder="最高金额">
    	       &nbsp;&nbsp;
    	       <input type="text" name="search" id="search" class="eagle-form-control" placeholder="最近订单号  客户姓名" value="<?php echo (!empty($save['search']))?$save['search']:null;?>">&nbsp;<input type="submit" value="搜索" class="btn btn-success btn-sm">
	       </div>
	       <?php 
    	   	   $listKeys = [];
    	   	   foreach ($customers as $customer){
					$oneKeys = [];
    	   	   		$oneKeys['seller_id'] = $customer['seller_id'];
    	   	   		$oneKeys['customer_id'] = $customer['customer_id'];
    	   	   		$oneKeys['platform_source'] = $customer['platform_source'];
    	   	   		$oneKeys['ticket_id'] = '';
    	   	   		$oneKeys['related_type'] = '';
    	   	   		$oneKeys['os_flag'] = $customer['os_flag'];
    	   	   		$oneKeys['msg_sent_error'] = $customer['msg_sent_error'];
    	   	   		$oneKeys['status'] = ($customer['os_flag']==1)?"remind":"ok";
    	   	   		$listKeys[] = $oneKeys;
    	   	   }
    	   	   $upOrDownText = base64_encode(json_encode($listKeys));
    	   	   echo "<input id='upOrDownText' type='hidden' value='$upOrDownText'>";
    	   ?>
	       <table class="table">
	       <thead>
    	       <tr>
    	           <th style="vertical-align: middle;"><input type="checkbox" id="chk_all"></th>
    	           <th style="text-align: left; vertical-align: middle; width:153px;">客户姓名</th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	           <?php 
//     	           echo Html::dropDownList('accounts',@$_REQUEST['accounts'],$accounts,['onchange'=>"account_action($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:140px;','prompt'=>'平台账户'])   	           
    	           ?>
        	            <span><?= (!empty($_GET['accounts'])) ? $_GET['accounts']: TranslateHelper::t('平台帐号')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="accounts" role="menu">
    							<li><?= TranslateHelper::t('平台帐号')?></li>
    							<?php 
    							if (!empty($accounts)){
    							foreach($accounts as $code=>$label):?>
    								<li<?php if (! empty($_GET['accounts'])) if ($_GET['accounts']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="accounts"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('平台帐号 ')?></option>
        					<?php foreach($accounts as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['accounts'])) if ($_GET['accounts']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>
        				
    	           </th>
    	           
    	           <th style="text-align: left; position: relative; vertical-align: middle;">
    	               <span><?= (!empty($_GET['countrys'])) ? $_GET['countrys']: TranslateHelper::t('国家')?></span>
    					<div class="btn-group ">
    						<a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
    							<span class="glyphicon glyphicon-menu-down"></span>
    						</a>
    						<ul class="dropdown-menu" data-selname="countrys" role="menu">
    							<li><?= TranslateHelper::t('国家')?></li>
    							<?php 
    							if (!empty($countrys)){
    							foreach($countrys as $code=>$label):?>
    								<li<?php if (! empty($_GET['countrys'])) if ($_GET['countrys']==$code) echo ' class="active" '?>><?= $label?></li>
    							<?php endforeach;
    							}?>
    						</ul>
    						
    					</div>
    				
        				<select name="countrys"  class="table_head_select"  style="display: none;">
        				
        					<option value=""><?= TranslateHelper::t('国家')?></option>
        					<?php foreach($countrys as $code=>$label):?>
        					<option value="<?= $code?>" <?php if (! empty($_GET['countrys'])) if ($_GET['countrys']==$code) echo " selected " ?>><?= $label?></option>
        					<?php endforeach;?>
        				</select>
    	           </th>
    	           <th style="text-align: left; vertical-align: middle;">订单总数量</th>
    	           <th style="text-align: left; vertical-align: middle;">订单总金额</th>
    	           <th style="text-align: left; vertical-align: middle;">最近订单号</th> 
    	           <th style="text-align: left; vertical-align: middle;">最近订单日期</th>
    	           <th style="text-align: left; vertical-align: middle;">最近消息日期(北京时间)</th>
    	           <th style="text-align: left; vertical-align: middle;">操作</th>
    	       </tr>
	       </thead>
    	   <tbody>
    	       <?php $num=1; foreach ($customers as $customer):?>
    	       <tr id="tr_customer_info_<?php echo $customer['id'];?>" data-customer-id="<?php echo $customer['id']; ?>" style="height:64px;"<?php echo $num%2==0?"class='striped-row'":null;$num++;?>> 	       
                    <?php
                        $flag_data=CustomerTagHelper::getALlTagDataByCustomerId($customer['id']);//查找相关用户的便签 
                        $all_flag=$flag_data['all_tag'];                //所有已设置标签
                        $selected_flag=$flag_data['all_select_tag_id'];//已选择的标签
                        if(!empty($customer['platform_source'])&&!empty($customer['customer_id']))
                        {
                            $customerArr=['source_buyer_user_id' => $customer['customer_id']];
                            $result=OrderTrackerApiHelper::getOrderList($customer['platform_source'],$customerArr);
                        }
                        $all_list=array();
                        if($result['success']==1){
                            $all_list=$result['orderArr']['data'];
                        }
                        $count=count($all_list);//订单条数
                        $customer['order_num']=$count;
                        echo $this->render('customer_tr_info',['customer'=>$customer,'all_flag'=>$all_flag,'selected_flag'=>$selected_flag,'accounts'=>$accounts]);
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
        <?php foreach ($customers as $customer):?>
            <div id="div_tag_<?php echo $customer['id']?>" name="div_add_tag" class="div_space_toggle" style="width:600px;"></div>
        <?php endforeach;?> 
	</div>
	
</div>
<script>
function message_tag_action(val){
	 $("form").submit();		   
	}
</script>