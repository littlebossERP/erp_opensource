<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/LazadaAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.LazadaAccountsNewOrEdit.initWidget();" , \yii\web\View::POS_READY);

?>
<style>
.imgBlock .modal-dialog{
	width: 1210px;
	height: 500px;
}
</style>

<div style="padding:10px 15px 0px 15px">
	<form id="platform-LazadaAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/lazada-accounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
		<input class="form-control" name="lazada_uid" type=hidden value="<?php echo $LazadaUserData['lazada_uid']; ?>" />
	    <input class="form-control" name="puid" type=hidden value="<?php echo $LazadaUserData['puid']; ?>" />
	<?php } ?>    
		<input class="form-control" name="platform" type=hidden value="<?php echo $platform; ?>" />
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t(ucfirst($platform).' api账号信息设置') ?></label></div>
			<table>  
				<tr>
					<td><?= TranslateHelper::t(ucfirst($platform).'店铺名称:') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$LazadaUserData['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;" />
						<?php endif;?>
						
						<?php if('lazada' == $platform):?>
						<span style="color:red;margin-left:3px">*</span>
						<?php endif;?>
					</td>
				</tr> 
				<?php if('lazada' == $platform):?>
				<tr style="color:red;font-size:14px;"><td colspan="2"><label>店铺名称是在<?=ucfirst($platform) ?>注册的店铺名，打印高仿面单会用到。</label></td></tr>     
				<?php endif;?>
				<?php if(!empty($LazadaUserData['version'])&&$platform == 'lazada'){?>
				<?php }else{//旧授权?>
    				<tr>
    					<td><?= TranslateHelper::t('API账号邮箱:') ?></td>
    					<td>
    						<?php if( $mode <> "new"):?>
    						<input class="form-control" type="text" name="platform_userid" value="<?=$LazadaUserData['platform_userid'] ?>" style="width:180px;display:inline;" disabled />
    						<?php else:?>
    						<input class="form-control" type="text" name="platform_userid" style="width:180px;display:inline;" />
    						<?php endif;?>
    						<span style="color:red;margin-left:3px">*</span>
    					</td>
    				</tr>
    				<tr>
    					<td><?= TranslateHelper::t('API key:') ?></td>
    					<td>
    						<?php if( $mode <> "new"):?>
    						<input class="form-control" name="token" type="text" value="<?=$LazadaUserData['token'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
    						<?php else:?>
    						<input class="form-control" name="token" type="text" style="width:180px;display:inline;" />
    						<?php endif;?>
    						<span style="color:red;margin-left:3px">*</span>
    					</td>
    				</tr>
    				<tr>
    					<td><?= TranslateHelper::t('站点:') ?></td>
    					<?php if( $mode <> "new"):?>
    					<td><?=Html::dropDownList('lazada_site',@$LazadaUserData['lazada_site'],$lazadaSite,['class'=>'form-control input-sm','prompt'=>'-请选择-','disabled'=>true])?></td>
    					<?php else:?>
    					<td><?=Html::dropDownList('lazada_site',@$LazadaUserData['lazada_site'],$lazadaSite,['class'=>'form-control input-sm','prompt'=>'-请选择-'])?></td>
    					<?php endif;?>
    				</tr>
    				
				<?php }?>
				    <tr>
    					<td><?= TranslateHelper::t('系统同步是否开启:') ?></td>
    					<td><select class="form-control" style="width: auto;" name="status" <?php if ($mode=="view") echo "disabled"; ?>>
    						   <option value="1" <?php if ($mode<>"new" and $LazadaUserData["status"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
    						   <option value="0" <?php if ($mode<>"new" and $LazadaUserData["status"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option>
    						</select>
    					</td>
    				</tr>
				<!--
    				<?php if('linio' == $platform){ ?>
    				<?php
    					$addi_info = empty($LazadaUserData['addi_info'])?[]:json_decode($LazadaUserData['addi_info'],true);
    					if(empty($addi_info)) $addi_info=[];
    					if(empty($addi_info['oms_use_product_image']))
    						$oms_use_product_image = false;
    					else 
    						$oms_use_product_image = true;
    				?>
				<tr>
					<td style="max-width:150px;padding:10px 0px;"><?= TranslateHelper::t('订单图片是否使用配对SKU的商品库主图:') ?></td>
					<td style="padding:10px 0px;">
						<div style="display:inline-block;">
							否
							<input class="" type="radio" name="oms_use_product_image" value="0" style="width:20px" <?=(!$oms_use_product_image)?'checked':''?> />
							是
							<input class="" type="radio" name="oms_use_product_image" value="1" style="width:20px" <?=($oms_use_product_image)?'checked':''?> />
						</div>
					</td>
				</tr>
				<?php } ?>
				-->
			</table>
			<?php  // @todo:解释文档页面多语言问题。?>
			<div style="margin-top: 10px;margin-left:5px;"><?php echo TranslateHelper::t('帮助文档');?>:<a target="_blank" style="color:blue;" href="<?php echo \Yii::getAlias('@web');?>/docs/<?=$platform?>Helper.html"><?php echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?></a></div>
        </div>
	</form>
	
</div>
