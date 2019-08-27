<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/shopeeAccountsNewOrEdit.js");
$this->registerJs("platform.shopeeAccountsNeworedit.init();", \yii\web\View::POS_READY);

?>
<form class="form-horizontal" id="platform-shopeeAccounts-form" action="/platform/shopeeAccounts/save" method="post">
	<?php if($mode <> "new"){?>
		<input name="shopee_uid" type="hidden" value="<?= $shopeeUser['shopee_uid'] ?>" />
	<?php }?>
	<!-- store name -->
	<div class="form-group">
		<label for="inputStroeName" class="col-sm-4 control-label">
			<span style="color: red; margin-left: 3px;">*</span>
			<?= TranslateHelper::t("店铺名称 (自定义)："); ?>
		</label>
		<div class="col-sm-8">
			<input class="form-control" type="text" id="inputStoreName" placeholder="店铺名称(自定义)" name="store_name"
				value="<?= $mode<>"new" ? $shopeeUser['store_name'] : '' ?>" style="display: inline; "
			/>
		</div>
	</div>
	<!-- ShopID -->
	<div class="form-group">
		<label for="inputShopID" class="col-sm-4 control-label">
			<span style="color: red; margin-left: 3px;">*</span>
			<?= TranslateHelper::t("ShopID："); ?>
		</label>
		<div class="col-sm-8">
			<input class="form-control" type="text" id="inputShopID" placeholder="ShopID" name="shop_id"
				value="<?= $mode<>"new" ? $shopeeUser['shop_id'] : '' ?>" style="display: inline; " <?= $mode<>'new' ? 'readonly' : '' ?> 
			/>
		</div>
	</div>
	<!-- PartnerID -->
	<div class="form-group">
		<label for="inputPartnerID" class="col-sm-4 control-label">
			<span style="color: red; margin-left: 3px;">*</span>
			<?= TranslateHelper::t("PartnerID："); ?>
		</label>
		<div class="col-sm-8">
			<input class="form-control" type="text" id="inputPartnerID" placeholder="PartnerID" name="partner_id" <?= $mode<>'new' ? 'readonly' : '' ?> 
				value="<?= $mode<>"new" ? $shopeeUser['partner_id'] : '' ?>" style="display: inline; "
			/>
		</div>
	</div>
	<!-- SecretKey -->
	<div class="form-group">
		<label for="inputSecretKey" class="col-sm-4 control-label">
			<span style="color: red; margin-left: 3px;">*</span>
			<?= TranslateHelper::t("SecretKey："); ?>
		</label>
		<div class="col-sm-8">
			<input class="form-control" type="text" id="inputSecretKey" placeholder="SecretKey" name="secret_key" <?= $mode<>'new' ? 'readonly' : '' ?> 
				value="<?= $mode<>"new" ? $shopeeUser['secret_key'] : '' ?>" style="display: inline; "
			/>
		</div>
	</div>
	<!-- site -->
	<div class="form-group">
		<label for="inputSite" class="col-sm-4 control-label">
			<span style="color: red; margin-left: 3px;">*</span>
			<?= TranslateHelper::t("站点："); ?>
		</label>
		<div class="col-sm-8">
			<?php if($mode == 'new'){?>
			<select class="form-control js-sel-mp" id="inputSite" name="site">
				<?php foreach($sites as $key => $val){?>
					<option value="<?= $key ?>" <?= ($mode <> "new" && $shopeeUser["site"] == $key) ?   'selected' : ''; ?>><?= $val ?></option>
				<?php }?>
			</select>
			<?php }else{?>
			<input class="form-control" type="text" value="<?= $sites[$shopeeUser['site']] ?>" style="display: inline; " readonly />
			<input type="hidden" name="site" value="<?= $shopeeUser['site'] ?>"/>
			<?php }?>
		</div>
	</div>
	<div class="form-group">
		<label for="inputActive" class="col-sm-4 control-label">
			<span style="color:red;margin-left:3px">*</span>
			<?php echo TranslateHelper::t('是否开启系统同步');?>:
    	</label>
	    <div class="col-sm-8">
	        <select class="form-control" style="width: auto;" name="status" >
	            <option value="1" <?= ($mode <> "new" && $shopeeUser["status"] == 1) ?   'selected' : ''; ?>><?= TranslateHelper::t('是');?></option>
	            <option value="0" <?= ($mode <> "new" && $shopeeUser["status"] == 0) ?  'selected' : '' ?>><?= TranslateHelper::t('否');?></option>
	        </select>
	    </div>
	</div>

	<?php
	// dzt20190429 对接了新授权，这个可以跳过。
	if( false && $mode!="new" ){
		$partner_id= $shopeeUser['partner_id'];
		$secret_key= $shopeeUser['secret_key'];
		$redirect= 'http://auth.littleboss.com/platform/platform/all-platform-account-binding';
		$token= hash_hmac("sha256",$secret_key.$redirect , $secret_key);
		$token= hash( "sha256",$secret_key.$redirect );
		$url= "https://partner.uat.shopeemobile.com/api/v1/shop/auth_partner?id={$partner_id}&token={$token}&redirect={$redirect}";
	?>
		<div class="form-group">
			<label for="inputActive" class="col-sm-4 control-label">
				<span style="color:red;margin-left:3px">*</span>
				<?php echo TranslateHelper::t('完成授权');?>:
			</label>
			<div class="col-sm-8">
				<a href="<?php echo $url?>" target="_blank">点击跳转至Shopee完成授权</a>
			</div>
		</div>


	<?php
	}
	?>
</form>


