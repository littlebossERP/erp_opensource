<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;

$this->title = TranslateHelper::t('平台授权');
$this->params['breadcrumbs'][] = $this->title;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->registerJsFile($baseUrl."js/project/platform/ebayAccountsList.js?v=1.01");
$this->registerJsFile($baseUrl."js/project/platform/amazonAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/aliexpressAccountList.js?v=1.04");
$this->registerJsFile($baseUrl."js/project/platform/WishAccountsList.js?v=1.02");
$this->registerJsFile($baseUrl."js/project/platform/dhgateAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/CdiscountAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/LazadaAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/EnsogoAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/PriceMinisterAccounts.js");
$this->registerJsFile($baseUrl."js/project/platform/BonanzaAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/RumallAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/neweggAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/PaypalAccounts.js");
$this->registerJsFile($baseUrl."js/project/platform/customizedAccounts.js");
$this->registerJsFile($baseUrl."js/project/platform/Al1688AccountList.js?v=1.04");
$this->registerJsFile($baseUrl."js/project/platform/ShopeeAccountsList.js?v=1.02");
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$uid=\Yii::$app->subdb->getCurrentPuid();
 
?>
<style>
.table td,.table th{
	text-align: left;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
}

.platform-name{
	color:#f0ad4e;
}

.view-and-set-sync .modal-dialog {
	width: 1050px;
	margin-top: 15%;
}

.platform-logo{
	height: 40px;
    position: relative;
    top: 10px;
}
</style>


<div class="platform-platform-all-platform-account-binding col1-layout" style="margin-left: 30px;margin-right:30px;">
	<?php 
	$isMainAccount = UserApiHelper::isMainAccount();
	//子账号没有权限授权，20170614_lrq
	if(!$isMainAccount){?>
		<div class="bind_tip alert alert-warning" style="margin-top: 5px;">
			<span>注意：子账号没有平台授权的权限！</span>
		</div>
    <?php return;}?>
	<div class="content-wrapper" >
	
		<div class="">
			<div class="bind_tip alert alert-warning" style="margin-top: 5px;">
				<span>注意：需要登录平台才能授权绑定的账号，请注意避免关联（请用注册地IP绑定，且不同账号绑定时要用注册地不相同IP），比如eBay账号的绑定！</span>
			</div>
			<div class="platform_selecter" style="padding-bottom:20px;display: inline-block;">
				<div style="float:left;font:bold 14px SimSun,Arial">请勾选您的销售平台：</div>
				<div style="float:left;width:80%">
				<?php foreach (OdOrder::$orderSource as $key=>$value){?>
				 
				<div style="float:left;width:130px;">
					<input type="checkbox" class="select_<?= $key;?>" id="select_<?= $key;?>" value="<?= $key;?>" <?=(!empty($platform[$key]) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])==$key))?'checked':''?> >
					<label for="select_<?= $key;?>" style="padding-right:10px; margin-right:10px;"><?= $value;?></label>
				</div>
				 
				<?php }?>
					<div style="float:left;width:130px;">
					    <input type="checkbox" class="select_paypal" id="select_paypal" value="paypal" <?=(!empty($platform['paypal']))?'checked':''?> >
        				<label for="select_paypal" style="padding-right:10px; margin-right:10px;">paypal</label>
					</div>
					<div style="float:left;">
					    <input type="checkbox" class="select_1688" id="select_1688" value="1688" <?=(!empty($platform['1688']))?'checked':''?> >
        				<label for="select_1688" style="padding-right:10px; margin-right:10px;">1688</label>
					</div>
				<?php 
					//未完待续
				?>
				</div>
			</div>
			<div class="">
				
				<div class="amazon-account-list" style="display:<?=(!empty($amazonUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='amazon'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="amazon" src="/images/platform_logo/amazon.png" class="platform-logo" style="background-color: black;">
						<!--  
						<strong>
						<span class="platform-name">Amazon</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.amazonAccountsList.openNewWindow()"><?= TranslateHelper::t('添加授权') ?></a>
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_516.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('Marketplace列表') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($amazonUserList as $amazonUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$amazonUser['store_name'] ?></td>
					            <?php if( $amazonUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td style="width:20%;"><?=$amazonUser['countryList'] ?></td>
					            <td><?=$amazonUser['next_time'] ?></td>
					            <td>
					                <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.amazonAccountsList.openReauthWindow(this,'<?=$amazonUser['amazon_uid'] ?>');"><?= TranslateHelper::t('重新授权') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('amazon',<?=$amazonUser['amazon_uid'] ?>,'<?=$amazonUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.amazonAccountsList.unbindAmazonAccount(<?=$amazonUser['amazon_uid'] ?>,'<?=$amazonUser['store_name'] ?>');"><?= TranslateHelper::t('解除绑定') ?> | </a>
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				
				<div class="ebay-account-list" style="display:<?=(!empty($ebayUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='ebay'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="ebay" src="/images/platform_logo/ebay.png" class="platform-logo">
						<!-- 
						<strong>
						<span class="platform-name">eBay</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						<!--  -->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuAdd(0)"><?= TranslateHelper::t('添加授权') ?></a>
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_272.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
						<!-- 
						<span class="text-danger">默认最多绑定10个ebay账号，如有需要请联系客户增加。</span>
						 -->
					</div>
					
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?> <span class="label label-warning"><?= TranslateHelper::t('别名') ?></span></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('授权过期时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        
				        foreach($ebayUserList as $ebayUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$ebayUser['selleruserid'] ?> <?php echo empty($ebayUser['store_name'])?"":"<span class='label label-warning'>".$ebayUser['store_name']."</span>"?></td>
					            <?php if( $ebayUser['item_status'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            
						            <?php if( $ebayUser['listing_status'] == '1'){
						            	 
						            	echo ' <p class="text-success">
							            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>'
							            .TranslateHelper::t('刊登开启') .
							            '</p>';
						            }else{
						            	echo ' <p class="text-muted">
							            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> '
							            .TranslateHelper::t('刊登关闭').'</p>';
						            }?>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            
					            	<?php if( $ebayUser['listing_status'] == '1'){
						            	 
						            	echo ' <p class="text-success">
							            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>'
							            .TranslateHelper::t('刊登开启') .
							            '</p>';
						            }else{
						            	echo ' <p class="text-muted">
							            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> '
							            .TranslateHelper::t('刊登关闭').'</p>';
						            }?>
					            </td>
					            <?php endif;?>
					            <td style="width:15%;"><?=$ebayUser['expiration_time'] ?>
					            <?php if (!empty($ebayUser['listing_expiration_time'])){
					            	echo "<br>";
					            	//'Y-m-d H:i:s'
					            	echo date('Y-m-d',$ebayUser['listing_expiration_time']).'<span class="label label-info" style="margin-left: 5px;">'.TranslateHelper::t('刊登').'</span>' ;
					            }?>
					            </td>
					            <td><?=$ebayUser['next_time'] ?>
					            <?= (empty($ebayUser['DevAcccountID']) || $ebayUser['DevAcccountID']==150)?'<br><span style="color:red">账号绑定过期，请重新绑定！</span>':'' ?>
								<?= (! empty($ebayUser['error_message']) )?'<br><span style="color:red">'.$ebayUser['error_message'].'</span>':'' ?>
					            </td>
					            <td>
					            	<?php if( $ebayUser['item_status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="set(0,'<?=$ebayUser['ebay_uid'] ?>','item_status');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="set(1,'<?=$ebayUser['ebay_uid'] ?>','item_status');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('ebay',<?=$ebayUser['ebay_uid'] ?>,'<?=$ebayUser['selleruserid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<!-- -->
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuDelete(<?=$ebayUser['ebay_uid'] ?>);"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
					            	 
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuAdd('<?=$ebayUser['selleruserid'] ?>')"><?= TranslateHelper::t('重新绑定') ?>  </a>
					            	<?php if ($ebayUser['listing_status'] != '1'):?>
					            	<!-- 
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuListingAdd('<?=$ebayUser['selleruserid'] ?>')">|<?= TranslateHelper::t('刊登绑定') ?>  </a>
					            	-->
					            	<?php endif;?>
					            	<?php if( $ebayUser['listing_status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.listSyncSet(0,'<?=$ebayUser['ebay_uid'] ?>','item_status');">| <?= TranslateHelper::t('刊登关闭') ?>  </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.listSyncSet(1,'<?=$ebayUser['ebay_uid'] ?>','item_status');">| <?= TranslateHelper::t('刊登开启') ?>  </a>
						            <?php endif;?>
						            
						            | <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.accountAlias('<?=$ebayUser['ebay_uid'] ?>')"><span><?= TranslateHelper::t('设置别名') ?></span>  </a>
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="paypal-account-list" style="display:<?=(!empty($paypalUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='paypal'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="paypal" src="/images/paypal/paypal_logo.svg" class="platform-logo">
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PaypalAccountsList.addPaypalAccount()"><?= TranslateHelper::t('添加授权') ?></a>
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_234.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th width="20%"><?=TranslateHelper::t('Paypal账号') ?></th>
							<th width="15%"><?=TranslateHelper::t('创建时间') ?></th>
							<th width="20%"><?=TranslateHelper::t('修改时间') ?></th>
							<th width="20%"><?=TranslateHelper::t('是否以Paypal地址覆盖ebay订单地址') ?></th>
							<th width="20%"><?= TranslateHelper::t('操作')?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        
				        foreach($paypalUserList as $paypalUser):?>
			             <tr>
				            <td><?=$rowIndex ?></td>
			                <td><?=$paypalUser['paypal_user'] ?></td>
				            <td><?=date('Y-m-d H:i:s',$paypalUser['create_time']) ?></td>
				            <td><?=date('Y-m-d H:i:s',$paypalUser['update_time']) ?></td>
				            <td><?=$paypalUser['overwrite_ebay_consignee_address'] ?></td>
				            <td>
				            	<?php if($paypalUser['overwrite_ebay_consignee_address']=='Y'){ ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PaypalAccountsList.switchOverwriteEbay(<?=$paypalUser['ppid'] ?>,'N')"><?= TranslateHelper::t('取消paypal地址覆盖ebay地址') ?> | </a>
								<?php }else{ ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PaypalAccountsList.switchOverwriteEbay(<?=$paypalUser['ppid'] ?>,'Y')"><?= TranslateHelper::t('用paypal地址覆盖ebay地址') ?> | </a>
								<?php } ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PaypalAccountsList.delPaypalAccount(<?=$paypalUser['ppid'] ?>)"><?= TranslateHelper::t('删除绑定') ?></a>
				            </td>
				        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="aliexpress-account-list" style="display:<?=(!empty($aliexpressUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='aliexpress'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="aliexpress" src="/images/platform_logo/aliexpress.png" class="platform-logo">
						<strong>
						<!-- 
						<span class="platform-name">AliExpress</span>
						-->
						<?= TranslateHelper::t('(目前只支持主账号)') ?>
						</strong>
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser(1, 1)"><?= TranslateHelper::t('添加授权(新版)') ?></a>
						
						<a class="btn btn-primary btn-sm hidden" style="text-decoration: none;" href="javascript:void(0)" onclick="getOpenSourceAuth()"><?= TranslateHelper::t('获取授权信息') ?></a>
						
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_510.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
					<table id="dg" class="table table-hover">
						<thead>
					    <tr class="list-firstTr">
					    	<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?>  <span class="label label-warning"><?= TranslateHelper::t('别名') ?></span></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('授权过期时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
						<?php 
						$rowIndex = 1;
						foreach( $aliexpressUserList as $aliexpressUser):?>
							<tr>
								<td style="width:5%;"><?=$rowIndex ?></td>
								<td style="width:20%;word-break:break-all;"><?=$aliexpressUser['sellerloginid'] ?>  <?php echo empty($aliexpressUser['store_name'])?"":"<span class='label label-warning'>".$aliexpressUser['store_name']."</span>"?></td>
								<?php if( $aliexpressUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
								<td style="width:20%;"><?=$aliexpressUser['refresh_token_timeout'] ?></td>
								<td><?=$aliexpressUser['next_time'] ?></td>
								<td>
									<?php if( $aliexpressUser['is_active'] == '1'):?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setSync('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>' , 0)"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setSync('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>' , 1)"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>		
									<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('aliexpress',<?=$aliexpressUser['aliexpress_uid'] ?>,'<?=$aliexpressUser['sellerloginid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="delUser('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
									<a style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser(1, 0)"><span><?= TranslateHelper::t('重新绑定') ?></span> | </a>
									<a style="text-decoration: none;" href="javascript:void(0)" onclick="setAliexpressAccountAlias('<?=$aliexpressUser['aliexpress_uid'] ?>', '<?=$aliexpressUser['sellerloginid'] ?>')"><span><?= TranslateHelper::t('设置别名') ?></span> | </a>
									
						        </td>
							</tr>
						<?php $rowIndex++;?>
						<?php endforeach;?>
						</tbody>
					</table>
				</div>	
				
				<!-- liang 2015-6-25 -->
				<div class="wish-account-list" style="display:<?=(!empty($WishUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='wish'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="wish" src="/images/platform_logo/wish.png" class="platform-logo" style="background-color: #4680A6;padding: 9px;">
						<!--  
						<strong>
						<span class="platform-name">Wish</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.addWishAccount()"><?= TranslateHelper::t('添加授权') ?></a>
						
						<a class="btn btn-primary btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="wishGetOpenSourceAuth()"><?= TranslateHelper::t('获取授权信息') ?></a> 
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_276.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?> <span class="label label-warning"><?= TranslateHelper::t('别名') ?></span></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($WishUserList as $WishUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$WishUser['store_name'] ?> <?php echo empty($WishUser['store_name_alias'])?"":"<span class='label label-warning'>".$WishUser['store_name_alias']."</span>"?></td>
					            <?php if( $WishUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td><?=$WishUser['last_order_success_retrieve_time'] .( (! empty($msg_error['wish'][$WishUser['site_id']])) && (! empty($WishUser['token']))?$msg_error['wish'][$WishUser['site_id']]:"")?>
								<?=empty($WishUser['order_retrieve_message'])?'':'<br><span style="color:red">'.$WishUser['order_retrieve_message'].'</span>' ?>
								</td>
					            <td><?=$WishUser['next_time']?></td>
					            <td>
					            <?php if( $WishUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setWishAccountSync(0,<?=$WishUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setWishAccountSync(1,<?=$WishUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('wish',<?=$WishUser['site_id'] ?>,'<?=$WishUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.delWishAccount(<?=$WishUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
					            	
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.rebindingWishAccount(<?=$WishUser['site_id'] ?>)"><span qtipkey="wish_rebind" ><?= TranslateHelper::t('重新绑定') ?></span></a>
					            	
					            	| <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.accountAlias('<?=$WishUser['site_id'] ?>')"><span><?= TranslateHelper::t('设置别名') ?></span>  </a>
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="dhgate-account-list" style="display:<?=(!empty($dhgateUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='dhgate'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="dhgate" src="/images/platform_logo/dhgate.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">dhgate</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateAuthorizationUser()"><?= TranslateHelper::t('添加授权') ?></a>
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_280.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('授权过期时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($dhgateUserList as $dhgateUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$dhgateUser['sellerloginid'] ?></td>
					            <?php if( $dhgateUser['is_active'] == '1' || $dhgateUser['is_active'] == '2'):// 2：为access token 过期，但对来说还是已开启?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td style="width:20%;"><?=$dhgateUser['refresh_token_timeout'] ?></td>
					            <td><?=$dhgateUser['next_time'];?></td>
					            <td>
					            <?php if( $dhgateUser['is_active'] == '1' || $dhgateUser['is_active'] == '2'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateSetSync('<?=$dhgateUser['dhgate_uid'] ?>','<?=$dhgateUser['sellerloginid'] ?>',0);"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateSetSync('<?=$dhgateUser['dhgate_uid'] ?>','<?=$dhgateUser['sellerloginid'] ?>',1);"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('dhgate',<?=$dhgateUser['dhgate_uid'] ?>,'<?=$dhgateUser['sellerloginid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateUnbindUser('<?=$dhgateUser['dhgate_uid'] ?>' , '<?=$dhgateUser['sellerloginid'] ?>');"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateAuthorizationUser()"><span><?= TranslateHelper::t('重新授权') ?></span></a>
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<!-- liang 2015-7-14 -->
				<div class="cdiscount-account-list" style="display:<?=(!empty($CdiscountUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='cdiscount'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="cdiscount" src="/images/platform_logo/cdiscount.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Cdiscount</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CdiscountAccountsList.addCdiscountAccount()"><?= TranslateHelper::t('添加授权') ?></a>
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_279.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($CdiscountUserList as $CdiscountUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$CdiscountUser['store_name'] ?></td>
					            <?php if( $CdiscountUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td><?=empty($CdiscountUser['order_retrieve_message'])?$CdiscountUser['last_order_success_retrieve_time']:'<span style="color:red">'.$CdiscountUser['order_retrieve_message'].'</span>' ?></td>
					            <td><?=$CdiscountUser['next_time'];?></td>
					            <td>
					              <?php if( $CdiscountUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setCdiscountAccountSync(0,<?=$CdiscountUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setCdiscountAccountSync(1,<?=$CdiscountUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
						            
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('cdiscount',<?=$CdiscountUser['site_id'] ?>,'<?=$CdiscountUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CdiscountAccountsList.delCdiscountAccount(<?=$CdiscountUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CdiscountAccountsList.openEditWindow('<?=$CdiscountUser['site_id'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						          
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<!-- dzt 2015-08-21 -->
				<div class="lazada-account-list" style="display:<?=(!empty($LazadaUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='lazada'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="lazada" src="/images/platform_logo/lazada.jpg" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Lazada</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<!-- <a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLazadaAccount()"><?= TranslateHelper::t('添加授权') ?></a> -->
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.lazadaAuthorizationUser()"><?= TranslateHelper::t('添加授权') ?></a>
						 
						<a class="btn btn-primary btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.openGetLazadaAuthInfoWindow()"><?= TranslateHelper::t('获取授权信息') ?></a>
						
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_273.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:5%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:5%;"><?= TranslateHelper::t('新授权方式') ?></th>
							<th style="width:5%;"><?= TranslateHelper::t('站点') ?></th>
							<th style="width:10%;"><?= TranslateHelper::t('授权过期时间') ?></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:10%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($LazadaUserList as $LazadaUser):?>
				            <tr>
					            <td ><?=$rowIndex ?></td>
					            <td style="word-break:break-all;"><?=empty($LazadaUser['store_name'])?$LazadaUser['platform_userid']:$LazadaUser['platform_userid']."(".$LazadaUser['store_name'].")" ?></td>
					            <?php if( $LazadaUser['status'] == '1'):?>
					            <td >
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td >
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td>
					               <?php if(!empty($LazadaUser['version'])){?>
					               <p class="text-success"><span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span></p>
					               <?php }else{?>
					               <p class="text-muted"><span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span></p>
					               <?php }?>
					            </td>
					            <td ><?=$LazadaUser['lazada_site'] ?></td>
					            <td ><?php
					            if(!empty($LazadaUser['refresh_token_timeout'])){
					                if($LazadaUser['refresh_token_timeout'] < time()){
					                    echo '<span style="color:red;">授权已过期，请重新授权。</span>';
					                }else{
					                    echo date('Y-m-d H:i:s',$LazadaUser['refresh_token_timeout']);
					                }
					            }else{
					                echo "";
					            }
					            ?></td>
					            <td ><?= @$LazadaUser['last_time'].(empty($LazadaUser['message'])?'':'<br><span style="color:red">'.$LazadaUser['message'].'</span>') ?></td>
					            <td><?=@$LazadaUser['next_time'];?></td>
					            <td>
					            <?php if( $LazadaUser['status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('lazada',0,<?=$LazadaUser['lazada_uid'] ?>,'<?=$LazadaUser['platform_userid'] ?>');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('lazada',1,<?=$LazadaUser['lazada_uid'] ?>,'<?=$LazadaUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('lazada',<?=$LazadaUser['lazada_uid'] ?>,'<?=$LazadaUser['platform_userid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$LazadaUser['lazada_uid'] ?>','lazada')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.lazadaAuthorizationUser('<?=$LazadaUser['lazada_uid'] ?>')"><?=TranslateHelper::t('重新授权') ?> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.openEditWindow('<?=$LazadaUser['lazada_uid'] ?>','lazada')"><?=TranslateHelper::t('编辑') ?></a>
						            
						            
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="linio-account-list" style="display:<?=(!empty($LinioUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='linio'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="linio" src="/images/platform_logo/linio.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Linio</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
			 			
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLinioAccount()"><?= TranslateHelper::t('添加授权') ?></a>
		 
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:10%;"><?= TranslateHelper::t('站点') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:10%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($LinioUserList as $LinioUser):?>
				            <tr>
					            <td ><?=$rowIndex ?></td>
					            <td style="word-break:break-all;"><?=$LinioUser['platform_userid'] ?></td>
					            <?php if( $LinioUser['status'] == '1'):?>
					            <td >
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td >
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td ><?=$LinioUser['lazada_site'] ?></td>
					            <td ><?=empty($LinioUser['message'])?@$LinioUser['last_time']:'<br><span style="color:red">'.$LinioUser['message'].'</span>' ?></td>
					            <td><?=$LinioUser['next_time'];?></td>
					            <td>
					            <?php if( $LinioUser['status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('linio',0,<?=$LinioUser['lazada_uid'] ?>,'<?=$LinioUser['platform_userid'] ?>');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('linio',1,<?=$LinioUser['lazada_uid'] ?>,'<?=$LinioUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('linio',<?=$LinioUser['lazada_uid'] ?>,'<?=$LinioUser['platform_userid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$LinioUser['lazada_uid'] ?>','linio')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.openEditWindow('<?=$LinioUser['lazada_uid'] ?>','linio')"><?=TranslateHelper::t('编辑') ?></a>
						            
						            
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="jumia-account-list" style="display:<?=(!empty($JumiaUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='jumia'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="jumia" src="/images/platform_logo/jumia.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Jumia</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addJumiaAccount()"><?= TranslateHelper::t('添加授权') ?></a>
			 
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:10%;"><?= TranslateHelper::t('站点') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:10%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($JumiaUserList as $JumiaUser):?>
				            <tr>
					            <td ><?=$rowIndex ?></td>
					            <td style="word-break:break-all;"><?=$JumiaUser['platform_userid'] ?></td>
					            <?php if( $JumiaUser['status'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td >
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td ><?=$JumiaUser['lazada_site'] ?></td>
					            <td ><?=empty($JumiaUser['message'])?@$JumiaUser['last_time']:'<br><span style="color:red">'.$JumiaUser['message'].'</span>' ?></td>
					            <td><?=@$JumiaUser['next_time'];?></td>
					            <td>
					            <?php if( $JumiaUser['status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('jumia',0,<?=$JumiaUser['lazada_uid'] ?>,'<?=$JumiaUser['platform_userid'] ?>');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('jumia',1,<?=$JumiaUser['lazada_uid'] ?>,'<?=$JumiaUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('jumia',<?=$JumiaUser['lazada_uid'] ?>,'<?=$JumiaUser['platform_userid'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$JumiaUser['lazada_uid'] ?>','jumia')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.openEditWindow('<?=$JumiaUser['lazada_uid'] ?>','jumia')"><?=TranslateHelper::t('编辑') ?></a>
						            
						            
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				
				<!-- liang 2016-03-28 -->
				<div class="priceminister-account-list" style="display:<?=(!empty($PriceministerUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='priceminister'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="PriceMinister" src="/images/platform_logo/priceminister.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">PriceMinister</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PriceMinisterAccountsList.addPriceMinisterAccount()"><?= TranslateHelper::t('添加授权') ?></a>
					 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="/platform/priceminister-accounts/list" ><?= TranslateHelper::t('编辑绑定账号') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($PriceministerUserList as $PriceMinisterUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$PriceMinisterUser['store_name'] ?></td>
					            <?php if( $PriceMinisterUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td><?=empty($PriceMinisterUser['order_retrieve_message'])?$PriceMinisterUser['last_order_success_retrieve_time']:'<span style="color:red">'.$PriceMinisterUser['order_retrieve_message'].'</span>' ?></td>
					            <td><?=$PriceMinisterUser['next_time'];?></td>
					            <td>
					              <?php if( $PriceMinisterUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setPriceMinisterAccountSync(0,<?=$PriceMinisterUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setPriceMinisterAccountSync(1,<?=$PriceMinisterUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
						            
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('priceminister',<?=$PriceMinisterUser['site_id'] ?>,'<?=$PriceMinisterUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PriceMinisterAccountsList.delPriceMinisterAccount(<?=$PriceMinisterUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.PriceMinisterAccountsList.openEditWindow('<?=$PriceMinisterUser['site_id'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						          
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="newegg-account-list" style="display:<?=(!empty($NeweggUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='newegg'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="cdiscount" src="/images/platform_logo/newegg.png" class="platform-logo">
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.neweggAccountsList.openNeweggAccountInfoWindow(0)"><?= TranslateHelper::t('添加授权') ?></a>
				 
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($NeweggUserList as $NeweggUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$NeweggUser['store_name'] ?></td>
					            <?php if( $NeweggUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td><?= @$NeweggUser['last_time']?></td>
					            <td><?= @$NeweggUser['next_time']?></td>
					            <td>
					              <?php if( $NeweggUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.neweggAccountsList.setNeweggAccountInfo(<?=$NeweggUser['site_id'] ?>,0);"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.neweggAccountsList.setNeweggAccountInfo(<?=$NeweggUser['site_id'] ?>,1);"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
						            
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('newegg',<?=$NeweggUser['site_id'] ?>,'<?=$NeweggUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.neweggAccountsList.delNeweggAccountInfo(<?=$NeweggUser['site_id'] ?>,'<?=$NeweggUser['store_name'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.neweggAccountsList.openNeweggAccountInfoWindow('<?=$NeweggUser['site_id'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						          
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				<?php ?>
				
				<!-- jie 2016-4-18 -->
				<div class="bonanza-account-list" style="display:<?=(!empty($BonanzaUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='bonanza'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="Bonanza" src="/images/platform_logo/bonanza.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Bonanza</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.BonanzaAccountsList.addBonanzaAccount()"><?= TranslateHelper::t('添加授权') ?></a>
			 
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($BonanzaUserList as $BonanzaUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$BonanzaUser['store_name'] ?></td>
					            <?php if( $BonanzaUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td style="width:25%;"><?=empty($BonanzaUser['order_retrieve_message'])?$BonanzaUser['last_order_success_retrieve_time']:'<span style="color:red">'.$BonanzaUser['order_retrieve_message'].'</span>' ?></td>
					            <td><?=$BonanzaUser['next_time'];?></td>
								<td>
					              <?php if( $BonanzaUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setBonanzaAccountSync(0,<?=$BonanzaUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setBonanzaAccountSync(1,<?=$BonanzaUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
						            
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('bonanza',<?=$BonanzaUser['site_id'] ?>,'<?=$BonanzaUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.BonanzaAccountsList.delBonanzaAccount(<?=$BonanzaUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.BonanzaAccountsList.openEditWindow('<?=$BonanzaUser['site_id'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						          
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
 
				<!-- jie 2016-8-12 -->
				<?php if(!empty($RumallUserList)):?>
				<div class="rumall-account-list" style="display:<?=(!empty($RumallUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='rumall'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="Rumall" src="/images/platform_logo/rumall.png" class="platform-logo">
						<!--  
						<strong>
						<span class="platform-name">Rumall</span>
							<?= TranslateHelper::t('账号') ?>
						</strong>
						-->
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.RumallAccountsList.addRumallAccount()"><?= TranslateHelper::t('添加授权') ?></a>
			 
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:20%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($RumallUserList as $RumallUser):?>
				            <tr>
					            <td style="width:5%;"><?=$rowIndex ?></td>
					            <td style="width:20%;word-break:break-all;"><?=$RumallUser['store_name'] ?></td>
					            <?php if( $RumallUser['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td style="width:25%;"><?=(empty($RumallUser['order_retrieve_message'])||$RumallUser['order_retrieve_message'] == 'get non order')?$RumallUser['last_order_success_retrieve_time']:'<span style="color:red">'.$RumallUser['order_retrieve_message'].'</span>' ?></td>
					            <td><?=$RumallUser['next_time'];?></td>
								<td>
					              <?php if( $RumallUser['is_active'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setRumallAccountSync(0,<?=$RumallUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setRumallAccountSync(1,<?=$RumallUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
						            
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('rumall',<?=$RumallUser['site_id'] ?>,'<?=$RumallUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.RumallAccountsList.delRumallAccount(<?=$RumallUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.RumallAccountsList.openEditWindow('<?=$RumallUser['site_id'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						          
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				<?php endif;?>
				
				
				<div class="customized-account-list" style="display:<?=(!empty($CustomizedUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='customized'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<label class="platform-name" style="font-size:18px;font-weight:600">自定义店铺 账号</label>
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CustomizedAccountsList.addCustomizedAccount()"><?= TranslateHelper::t('添加授权') ?></a>
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_241.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th width="20%"><?=TranslateHelper::t('自定义店铺名') ?></th>
							<th width="20%"><?=TranslateHelper::t('自定义店铺账号') ?></th>
							<th width="20%"><?=TranslateHelper::t('修改时间') ?></th>
							<th width="15%"><?=TranslateHelper::t('是否启用') ?></th>
							<th width="20%"><?= TranslateHelper::t('操作')?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        if(!empty($CustomizedUserList)) :
				      	foreach($CustomizedUserList as $customizedUser):?>
			             <tr>
				            <td><?=$rowIndex ?></td>
			                <td><?=$customizedUser['store_name'] ?></td>
			                <td><?=$customizedUser['username'] ?></td>
				            <td><?=@$customizedUser['update_time'] ?></td>
				            <td><?=!empty($customizedUser['is_active'])?'是':'否' ?></td>
				            <td>
				            	<?php if(!empty($customizedUser['is_active'])){ ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CustomizedAccountsList.switchActive(<?=$customizedUser['site_id'] ?>,0)"><?= TranslateHelper::t('关闭') ?> | </a>
								<?php }else{ ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CustomizedAccountsList.switchActive(<?=$customizedUser['site_id'] ?>,1)"><?= TranslateHelper::t('启用') ?> | </a>
								<?php } ?>
								<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.CustomizedAccountsList.editCustomizedAccount(<?=$customizedUser['site_id'] ?>)"><?= TranslateHelper::t('编辑') ?></a>
				            </td>
				        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;endif;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="shopee-account-list" style="display:<?=(!empty($ShopeeUserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='shopee'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="shopee" src="/images/platform_logo/shopee.png" class="platform-logo">
						 
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.authorizationUser()"><?= TranslateHelper::t('添加授权') ?></a>
			 
			            <a class="btn btn-primary btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.getOpenSourceAuth()"><?= TranslateHelper::t('获取授权信息') ?></a> 
						 
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_515.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
				    <table class="table table-hover">
				    	<thead>
						<tr class="list-firstTr">
							<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:10%;"><?= TranslateHelper::t('站点') ?></th>
							<!--  <th style="width:15%;"><?= TranslateHelper::t('同步内容') ?></th>-->
							<th style="width:20%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
							<th style="width:10%;"><span qtipkey="platform_order_sync_next_time"><?= TranslateHelper::t('预计下次同步时间') ?></span></th>
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
				        <?php 
				        $rowIndex = 1;
				        foreach($ShopeeUserList as $ShopeeUser):?>
				            <tr>
					            <td ><?=$rowIndex ?></td>
					            <td style="word-break:break-all;"><?= $ShopeeUser['store_name'] ?></td>
					            <?php if( $ShopeeUser['status'] == '1'):?>
					            <td >
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td >
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
					            <td ><?=$ShopeeUser['site'] ?></td>
					            <td ><?= @$ShopeeUser['last_time'].(empty($ShopeeUser['message'])?'':'<br><span style="color:red">'.$ShopeeUser['message'].'</span>') ?></td>
					            <td><?=@$ShopeeUser['next_time'];?></td>
					            <td>
					            <?php if( $ShopeeUser['status'] == '1'):?>
	            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.setShopeeAccountSync(0,<?=$ShopeeUser['shopee_uid'] ?>);"><?= TranslateHelper::t('关闭同步') ?> | </a>
						            <?php else:?>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.setShopeeAccountSync(1,<?=$ShopeeUser['shopee_uid'] ?>);"><?= TranslateHelper::t('开启同步') ?> | </a>
						            <?php endif;?>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="viewAndSetSync('shopee',<?=$ShopeeUser['shopee_uid'] ?>,'<?=$ShopeeUser['store_name'] ?>')"><?= TranslateHelper::t('查看同步') ?> | </a>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.unbindShopeeAccount('<?=$ShopeeUser['shopee_uid'] ?>', '<?=$ShopeeUser['store_name'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
						            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ShopeeAccountsList.openEditWindow('<?=$ShopeeUser['shopee_uid'] ?>')"><?=TranslateHelper::t('编辑') ?></a>
						            
						            
					            	
					            </td>
					        </tr>
					       <?php $rowIndex++;?>
				        <?php endforeach;?>
				        </tbody>
				    </table>
				</div>
				
				<div class="1688-account-list" style="display:<?=(!empty($Al1688UserList) || (!empty($_REQUEST['platform']) && strtolower($_REQUEST['platform'])=='1688'))?'block':'none'?>">
					<div style="margin-bottom: 10px;">
						<img alt="aliexpress" src="/images/platform_logo/1688.png" class="platform-logo">
						<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser1688()"><?= TranslateHelper::t('添加授权') ?></a>
						<a class="btn btn-info btn-sm" style="text-decoration: none;" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_511.html')?>" target="_blank"><?= TranslateHelper::t('查看授权帮助') ?></a>
					</div>
					<table id="dg" class="table table-hover">
						<thead>
					    <tr class="list-firstTr">
					    	<th style="width:5%;"></th>
							<th style="width:20%;word-break:break-all;"><?= TranslateHelper::t('账户') ?> </th>
							<th style="width:15%;"><?= TranslateHelper::t('系统同步') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('授权过期时间') ?></th>
							<th style="width:20%;"><?= TranslateHelper::t('操作') ?></th>
						</tr>
						</thead>
						<tbody>
						<?php 
						$rowIndex = 1;
						foreach( $Al1688UserList as $Al1688User):?>
							<tr>
								<td style="width:5%;"><?=$rowIndex ?></td>
								<td style="width:20%;word-break:break-all;"><?=$Al1688User['store_name'] ?> </td>
								<?php if( $Al1688User['is_active'] == '1'):?>
					            <td style="width:15%;">
					            <p class="text-success">
					            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已开启') ?>
					            </p>
					            </td>
					            <?php else:?>
					            <td style="width:15%;">
					            <p class="text-muted">
					            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					            <?= TranslateHelper::t('已关闭') ?></p>
					            </td>
					            <?php endif;?>
								<td style="width:20%;"><?=$Al1688User['refresh_token_timeout'] ?></td>
								<td>
					            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="delUser1688('<?=$Al1688User['uid_1688'] ?>' , '<?=$Al1688User['aliId'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
									<a style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser1688()"><span><?= TranslateHelper::t('重新绑定') ?></span> | </a>
									
						        </td>
							</tr>
						<?php $rowIndex++;?>
						<?php endforeach;?>
						</tbody>
					</table>
				</div>	
				
			</div>
		</div>
		
		<div class="view-and-set-sync"></div>
	</div> 
</div>
<?php 
$this->registerJs("
	$(\".platform_selecter input[type='checkbox']\").change(function(){
		var platform = $(this).val();
		//console.log(platform);
		if(typeof(platform)!=='undefined' && platform!==''){
			var account_list_div = \".\"+platform+'-account-list';
			if($(this).prop('checked'))
				var display = 'block';
			else
				var display = 'none';
			if(display=='none'){
				$(account_list_div).slideUp('fast');
			}else{
				$(account_list_div).slideDown('fast');
			}
		}
	});" , \yii\web\View::POS_READY);


?>
<script type="text/javascript">
	function viewAndSetSync(platform,site_id,store_name){
		if (platform=='' || site_id=='')  {
		   bootbox.alert({title:Translator.t('错误提示') , message:Translator.t("传入信息有误，操作终止") });	
		   return false;
		}
		window.open(global.baseUrl+'platform/platform/view-or-set-sync-config?platform='+platform+'&site_id='+site_id+'&store_name='+store_name);
		return false;
		$.get( global.baseUrl+'platform/platform/view-or-set-sync-config?platform='+platform+'&site_id='+site_id,
			function (data){
				$.hideLoading();
				bootbox.dialog({
					title : Translator.t("查看/设置 同步选项&nbsp;&nbsp;"+platform+"&nbsp;-&nbsp;"+store_name),
					className: "view-and-set-sync",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});
			}
		);
	}
</script>