<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->title = TranslateHelper::t('平台绑定');
$this->params['breadcrumbs'][] = $this->title;

$title_lv2 =  TranslateHelper::t('平台绑定 ');
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->registerJsFile($baseUrl."js/project/platform/ebayAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/aliexpressAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/WishAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/dhgateAccountList.js");
$this->registerJsFile($baseUrl."js/project/platform/LazadaAccountsList.js");
$this->registerJsFile($baseUrl."js/project/platform/EnsogoAccountsList.js");
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

?>
<style>
.table td,.table th{
	text-align: center;
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

.platform-logo{
	height: 40px;
    position: relative;
    top: 20px;
}
</style>


<div class="tracking-index col1-layout">

	<div class="content-wrapper" >
	
		<div class="">
				<div class="">
					
				
					<div class="ebay-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="ebay" src="/images/platform_logo/ebay.png" class="platform-logo">
							<!-- 
							<strong>
							<span class="platform-name">eBay</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<!-- -->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuAdd()"><?= TranslateHelper::t('添加绑定') ?></a>
							
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('失效时间') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($ebayUserList as $ebayUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$ebayUser['selleruserid'] ?></td>
						            <?php if( $ebayUser['item_status'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <td style="width:25%;"><?=$ebayUser['expiration_time'] ?>
						            <?= (empty($ebayUser['DevAcccountID'] ) || $ebayUser['DevAcccountID']==150)?'<br><span style="color:red">账号绑定过期，请重新绑定！</span>':'' ?>
						            </td>
						            <td style="width:25%;">
							            <?php if( $ebayUser['item_status'] == '1'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="set(0,'<?=$ebayUser['ebay_uid'] ?>','item_status');"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="set(1,'<?=$ebayUser['ebay_uid'] ?>','item_status');"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            <!-- -->
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuDelete(<?=$ebayUser['ebay_uid'] ?>);"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
										
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
					
					<div class="aliexpress-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="aliexpress" src="/images/platform_logo/aliexpress.png" class="platform-logo">
							<!--  
							<strong>
							<span class="platform-name">AliExpress</span>
							<?= TranslateHelper::t('账号') ?><?= TranslateHelper::t('(目前只支持主账号)') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser()"><?= TranslateHelper::t('添加绑定') ?></a>
						</div>
						<table id="dg" class="table table-hover">
							<thead>
						    <tr class="list-firstTr">
						    	<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?>  <span class="label label-warning"><?= TranslateHelper::t('别名') ?></span></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('失效时间') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作')?></th>
							</tr>
							</thead>
							<tbody>
							<?php 
							$rowIndex = 1;
							foreach( $aliexpressUserList as $aliexpressUser):?>
								<tr>
									<td style="width:5%;"><?=$rowIndex ?></td>
									<td style="width:25%;word-break:break-all;"><?=$aliexpressUser['sellerloginid'] ?> <?php echo empty($aliexpressUser['store_name'])?"":"<span class='label label-warning'>".$aliexpressUser['store_name']."</span>"?></td>
									<?php if( $aliexpressUser['is_active'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
									<td style="width:25%;"><?=$aliexpressUser['refresh_token_timeout'] ?></td>
									<td style="width:25%;">
	    								<?php if( $aliexpressUser['is_active'] == '1'):?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setSync('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>' , 0)"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setSync('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>' , 1)"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>		
										
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="delUser('<?=$aliexpressUser['aliexpress_uid'] ?>' , '<?=$aliexpressUser['sellerloginid'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span> | </a>
										<a style="text-decoration: none;" href="javascript:void(0)" onclick="setAliexpressAccountAlias('<?=$aliexpressUser['aliexpress_uid'] ?>', '<?=$aliexpressUser['sellerloginid'] ?>')"><span><?= TranslateHelper::t('设置别名') ?></span> | </a>
							        </td>
								</tr>
							<?php $rowIndex++;?>
							<?php endforeach;?>
							</tbody>
						</table>
					</div>	
					
					<!-- liang 2015-6-25 -->
					<div class="Wish-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="wish" src="/images/platform_logo/wish.png" class="platform-logo" style="background-color: #4680A6;padding: 9px;">
							<!--  
							<strong>
							<span class="platform-name">Wish</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.addWishAccount()"><?= TranslateHelper::t('添加绑定') ?></a>
							<a class="btn btn-transparent btn-sm" style="text-decoration: none;" href="/platform/wish-accounts-v2/binding-guide" target="_blank"><?= TranslateHelper::t('绑定教程') ?></a>
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('上次成功获取order的时间') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($WishUserList as $WishUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$WishUser['store_name'] ?></td>
						            <?php if( empty($WishUser['token'])):?>
									<td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('未绑定') ?></p>
						            </td>
						            <?php elseif( $WishUser['is_active'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <?php $retrieve_error = '';
						            	if(!empty($WishUser['order_retrieve_message']) && empty($WishUser['product_retrieve_message']))
						            		$retrieve_error = "<br><span style='color:red'>".$WishUser['order_retrieve_message']."</span>";
						            	if(empty($WishUser['order_retrieve_message']) && !empty($WishUser['product_retrieve_message']))
						            		$retrieve_error = "<br><span style='color:red'>".$WishUser['product_retrieve_message']."</span>";
					            		if(!empty($WishUser['order_retrieve_message']) && !empty($WishUser['product_retrieve_message']))
					            			$retrieve_error = "<br><span style='color:red'>".$WishUser['order_retrieve_message']."</span>";
						            ?>
						            <td style="width:25%;"><?=$WishUser['last_order_success_retrieve_time'].$retrieve_error ?></td>
						            <td style="width:25%;">
							            <?php if( $WishUser['is_active'] == '1'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="setWishAccountSync(0,<?=$WishUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="setWishAccountSync(1,<?=$WishUser['site_id'] ?>,'is_active');"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.rebindingWishAccount(<?=$WishUser['site_id'] ?>)"><span qtipkey="wish_rebind" ><?= TranslateHelper::t('重新绑定') ?></span>|</a>
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.WishAccountsList.delWishAccount(<?=$WishUser['site_id'] ?>)"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
					<div class="dhgate-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="dhgate" src="/images/platform_logo/dhgate.png" class="platform-logo">
							<!--  
							<strong>
							<span class="platform-name">dhgate</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateAuthorizationUser()"><?= TranslateHelper::t('添加绑定') ?></a>
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('失效时间') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($dhgateUserList as $dhgateUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$dhgateUser['sellerloginid'] ?></td>
						            <?php if( $dhgateUser['is_active'] == '1' || $dhgateUser['is_active'] == '2'):// 2：为access token 过期，但对来说还是已开启?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <td style="width:25%;"><?=$dhgateUser['refresh_token_timeout'] ?></td>
						            <td style="width:25%;">
							            <?php if( $dhgateUser['is_active'] == '1' || $dhgateUser['is_active'] == '2'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateSetSync('<?=$dhgateUser['dhgate_uid'] ?>','<?=$dhgateUser['sellerloginid'] ?>',0);"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateSetSync('<?=$dhgateUser['dhgate_uid'] ?>','<?=$dhgateUser['sellerloginid'] ?>',1);"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="dhgateUnbindUser('<?=$dhgateUser['dhgate_uid'] ?>' , '<?=$dhgateUser['sellerloginid'] ?>');"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
					<!-- dzt 2015-08-21 -->
					<div class="lazada-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="lazada" src="/images/platform_logo/lazada.jpg" class="platform-logo">
							<!--  
							<strong>
							<span class="platform-name">Lazada</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLazadaAccount()"><?= TranslateHelper::t('添加绑定') ?></a>
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('站点') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($LazadaUserList as $LazadaUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$LazadaUser['platform_userid'] ?></td>
						            <?php if( $LazadaUser['status'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <td style="width:25%;"><?=$LazadaUser['lazada_site'] ?></td>
						            <td style="width:25%;">
							            <?php if( $LazadaUser['status'] == '1'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('lazada',0,<?=$LazadaUser['lazada_uid'] ?>,'<?=$LazadaUser['platform_userid'] ?>');"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('lazada',1,<?=$LazadaUser['lazada_uid'] ?>,'<?=$LazadaUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$LazadaUser['lazada_uid'] ?>','<?=$LazadaUser['platform'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
					<div class="linio-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="linio" src="/images/platform_logo/linio.png" class="platform-logo">
							<!--  
							<strong>
							<span class="platform-name">Linio</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLinioAccount()"><?= TranslateHelper::t('添加绑定') ?></a>
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('站点') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($LinioUserList as $LinioUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$LinioUser['platform_userid'] ?></td>
						            <?php if( $LinioUser['status'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <td style="width:25%;"><?=$LinioUser['lazada_site'] ?></td>
						            <td style="width:25%;">
							            <?php if( $LinioUser['status'] == '1'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('linio',0,<?=$LinioUser['lazada_uid'] ?>,'<?=$LinioUser['platform_userid'] ?>');"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('linio',1,<?=$LinioUser['lazada_uid'] ?>,'<?=$LinioUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$LinioUser['lazada_uid'] ?>','<?=$LinioUser['platform'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
					<div class="jumia-account-list">
						<div style="margin-bottom: 10px;">
							<img alt="jumia" src="/images/platform_logo/jumia.png" class="platform-logo">
							<!--  
							<strong>
							<span class="platform-name">Jumia</span>
								<?= TranslateHelper::t('账号') ?>
							</strong>
							-->
							<a class="btn btn-success btn-sm" style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addJumiaAccount()"><?= TranslateHelper::t('添加绑定') ?></a>
						</div>
					    <table class="table table-hover">
					    	<thead>
							<tr class="list-firstTr">
								<th style="width:5%;"></th>
								<th style="width:25%;word-break:break-all;"><?= TranslateHelper::t('账户') ?></th>
								<th style="width:20%;"><?= TranslateHelper::t('系统同步') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('站点') ?></th>
								<th style="width:25%;"><?= TranslateHelper::t('操作') ?></th>
							</tr>
							</thead>
							<tbody>
					        <?php 
					        $rowIndex = 1;
					        foreach($JumiaUserList as $JumiaUser):?>
					            <tr>
						            <td style="width:5%;"><?=$rowIndex ?></td>
						            <td style="width:25%;word-break:break-all;"><?=$JumiaUser['platform_userid'] ?></td>
						            <?php if( $JumiaUser['status'] == '1'):?>
						            <td style="width:20%;">
						            <p class="text-success">
						            <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已开启') ?>
						            </p>
						            </td>
						            <?php else:?>
						            <td style="width:20%;">
						            <p class="text-muted">
						            <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
						            <?= TranslateHelper::t('已关闭') ?></p>
						            </td>
						            <?php endif;?>
						            <td style="width:25%;"><?=$JumiaUser['lazada_site'] ?></td>
						            <td style="width:25%;">
							            <?php if( $JumiaUser['status'] == '1'):?>
		            					<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('jumia',0,<?=$JumiaUser['lazada_uid'] ?>,'<?=$JumiaUser['platform_userid'] ?>');"><?= TranslateHelper::t('停止同步') ?>|</a>
							            <?php else:?>
							            <a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.setLazadaAccountSync('jumia',1,<?=$JumiaUser['lazada_uid'] ?>,'<?=$JumiaUser['platform_userid'] ?>');"><?= TranslateHelper::t('开启同步') ?>|</a>
							            <?php endif;?>
							            
						            	<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$JumiaUser['lazada_uid'] ?>','<?=$JumiaUser['platform'] ?>')"><span qtipkey="unbind_platform" ><?= TranslateHelper::t('解除绑定') ?></span></a>
						            </td>
						        </tr>
						       <?php $rowIndex++;?>
					        <?php endforeach;?>
					        </tbody>
					    </table>
					</div>
					
				</div>
			</div>
	
	
	
	</div> 
</div>