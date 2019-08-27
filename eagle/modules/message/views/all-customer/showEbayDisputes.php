<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use eagle\modules\message\helpers\ResolutionEbayHelper;
use common\helpers\Helper_Array;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/message/customer_message.css");
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/showEbayDisputes.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("showEbayDisputes.init();", \yii\web\View::POS_READY);
// $this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

?>

<div class="tracking-index col2-layout">

	<?= $this->render('left_menu',['no_answer'=>array()]) ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	   <form action="/message/all-customer/show-ebay-disputes" method="GET">
	       <div><!-- 
	           <?=Html::button('未关闭',['class'=>"btn-xs btn btn-primary",'onclick'=>"javascript:showTable();"])?>
	           <?=Html::button('已关闭',['class'=>"btn-xs btn btn-primary",'onclick'=>"javascript:showTable();"])?> -->
	           <a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>
	           
	           <div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;margin-left:30px;"<?php }?>>
	           	纠纷编号：<?=Html::textInput('caseid',@$_REQUEST['caseid'],['class'=>'eagle-form-control','id'=>'caseid'])?>
	           	SRN：<?=Html::textInput('srn',@$_REQUEST['srn'],['class'=>'eagle-form-control','id'=>'srn'])?>
	           	
	           	<?php 
	           		$disputesStatus = [
						'CASE_CLOSED_CS_RESPONDED'=>'eBay有通知',
						'CLOSED'=>'已关闭',
						'CS_CLOSED'=>'eBay已关闭',
						'EXPIRED'=>'过期',
						'MY_PAYMENT_DUE'=>'等待付款',
						'MY_RESPONSE_DUE'=>'等待回复',
						'OTHER'=>'其他',
						'OTHER_PARTY_CONTACTED_CS_AWAITING_RESPONSE'=>'对方等待eBay回复',
						'OTHER_PARTY_RESPONSE_DUE'=>'对方已回复',
						'PAID'=>'已付款',
						'WAITING_DELIVERY'=>'待收货',
						'YOU_CONTACTED_CS_ABOUT_CLOSED_CASE'=>'已向eBay申请关闭',
						'YOU_CONTACTED_CS_AWAITING_RESPONSE'=>'卖家等待eBay回复'
					];
	           		$disputesType = [
						'EBP_INR'=>'未收到',
						'EBP_SNAD'=>'描述不符',
						'CANCEL_TRANSACTION'=>'取消交易',
						'UPI'=>'未付款',
					];
	           	?>
	           	纠纷状态：<?=Html::dropDownList('case_status',@$_REQUEST['case_status'],$disputesStatus,['class'=>'do eagle-form-control','id'=>'case_status','prompt'=>'']);?>
	           	
	           	<br>
	                                    卖家账号:<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($selleruserids,'selleruserid','selleruserid'),['class'=>'do eagle-form-control','id'=>'selleruerid','prompt'=>'']);?>
	           	买家账号：<?=Html::textInput('buyeruserid',@$_REQUEST['buyeruserid'],['class'=>'eagle-form-control','id'=>'buyeruserid'])?>
	           	Item ID：<?=Html::textInput('itemid',@$_REQUEST['itemid'],['class'=>'eagle-form-control','id'=>'itemid'])?>
	           	纠纷类型：<?=Html::dropDownList('case_type',@$_REQUEST['case_type'],$disputesType,['class'=>'do eagle-form-control','id'=>'case_type','prompt'=>'']);?>
	           	
	           	<br>
	           	<div class="form-inline">
	        	<?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['createtime'=>'创建时间','modifytime'=>'最后修改时间','respondtime'=>'回复时间'],['class'=>'form-control input-sm','style'=>'width:90px;margin:0px'])?>
	        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'form-control','style'=>'width:130px','id'=>'startdate'])?>
		        	至
				<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'form-control','style'=>'width:130px;margin:0px','id'=>'enddate'])?>
	           
	           	<?=Html::submitButton('搜索',['class'=>"btn-xs",'id'=>'search'])?>
	    		<?=Html::button('重置',['class'=>"btn-xs",'onclick'=>"javascript:cleform();"])?>
	    		</div>
	           </div>
	           
	       </div>
	       <div style="margin-top:10px;">
	       		<?=Html::button('手动同步纠纷',['class'=>"btn-xs btn btn-primary",'id'=>'manual_syn_disputes'])?>
	       </div>
	       <table class="table" style="margin-top:10px;">
	       <thead>
    	       <tr>
    	           <th style="text-align: left; vertical-align: middle;"><?=TranslateHelper::t('纠纷编号'); ?></th>
    	           <th style="text-align: left; width:65px; vertical-align: middle;">SRN</th>
    	           <th style="text-align: left; width:170px;  position: relative; vertical-align: middle;"><?=TranslateHelper::t('纠纷类型'); ?></th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;"><?=TranslateHelper::t('买家'); ?></th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;"><?=TranslateHelper::t('账号名称'); ?></th>
    	           <th style="text-align: left; vertical-align: middle; width:155px;"><?=TranslateHelper::t('物品'); ?></th>
    	           <th style="text-align: left; position: relative; vertical-align: middle;"><?=TranslateHelper::t('创建时间'); ?></th>
    	           <th style="text-align: left; vertical-align: middle;"><?=TranslateHelper::t('系统更新时间'); ?></th>
    	           <th style="text-align: left; vertical-align: middle;"><?=TranslateHelper::t('状态'); ?></th>
    	           <th style="text-align: left; vertical-align: middle;"><?=TranslateHelper::t('操作'); ?></th>
    	       </tr>
	       </thead>
    	   <tbody>
    	   <?php foreach ($ebayDisputesList as $ebayDisputesone){
    	   	?>
    	   	<tr>
    	   		<td><?=$ebayDisputesone['caseid'] ?></td><td><?=$ebayDisputesone['order_source_srn'] ?></td>
    	   		<td>
    	   		<?php if(key_exists($ebayDisputesone['type'], ResolutionEbayHelper::$disputesType)):?>
    	   		<?=ResolutionEbayHelper::$disputesType[$ebayDisputesone['type']] ?>
    	   		<?php else:?>
    	   		<?=$ebayDisputesone['type'] ?>
    	   		<?php endif;?>
    	   		</td>
    	   		<td><?=$ebayDisputesone['buyeruserid'] ?></td>
    	   		<td><?=$ebayDisputesone['selleruserid'] ?></td>
    	   		<td><?=$ebayDisputesone['itemid'] ?></td>
    	   		<td><?=date('Y-m-d H:i',$ebayDisputesone['created_date']) ?></td>
    	   		<td><?php if(empty($ebayDisputesone['lastmodified_date'])){
						echo '';
					}else{ echo date('Y-m-d H:i',$ebayDisputesone['lastmodified_date']);} ?></td>
    	   		<td>
    	   		<?php if(key_exists($ebayDisputesone['status_value'], ResolutionEbayHelper::$disputesStatus)):?>
    	   		<?=ResolutionEbayHelper::$disputesStatus[$ebayDisputesone['status_value']] ?>
    	   		<?php else:?>
    	   		<?=$ebayDisputesone['status_value'] ?>
    	   		<?php endif;?>
    	   		</td>
    	   		<td>
    	   		<?php if (in_array($ebayDisputesone['type'],['EBP_INR','EBP_SNAD'])):?>
    	   		<a onclick="ShowDetailDisputes('<?=$ebayDisputesone['caseid'] ?>')" ><span data-hasqtip="25" aria-describedby="qtip-25">操作</span></a>
    	   		<?php endif;?>
    	   		</td>
    	   	</tr>
    	   	<?php 
    	   } ?>
    	   </tbody>
    	   </table>
	    </form>	
	    <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
            
        	</div>
	            <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
        </div>   
	</div>
</div>


<!-- Modal 手动同步纠纷modal-->
<div class="modal fade" id="myMessage" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form  enctype="multipart/form-data"?>">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
						&times;
					</button>
					<h4 class="modal-title" id="myModalLabel">
						同步纠纷
					</h4>
				</div>
				<div class="modal-body">
					<div>
						<div>
							请选择需要同步账号：
						</div>
						<div style="margin-top: 10px; margin-bottom: 2px;">
							<select id="ebay_user">
								<?php foreach( $ebay_user as $uid){?>
									<option name="<?= $uid['selleruserid']?>"><?= $uid['selleruserid']?></option>
								<?php }?>
							</select>
						</div>
					</div>
					<div>
						开启纠纷时间段:<br>

						开始时间:<input type="text" id="open_startdate" name="open_startdate" class="eagle-form-control" style="width:90px;" value=<?=date('Y-m-d',time()-15*24*3600);?>><br>
						结束时间:<input type="text" id="open_enddate" name="open_startdate" class="eagle-form-control" style="width:90px;" value=<?=date('Y-m-d',time());?>>

					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
					<button type="button" class="btn btn-primary" onclick="manualSync($('#open_startdate').val(),$('#open_enddate').val(),$('#ebay_user').val())"> 提交</button>
				</div>
			</div>
		</div>
	</form>
</div>




