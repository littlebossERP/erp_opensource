<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;

$supplierInfo['supplier_id'] = ((is_numeric($model->supplier_id))?$model->supplier_id:'');
$supplierInfo['name'] = ((empty($model->name))?"":$model->name);
$supplierInfo['address_nation']= ((empty($model->address_nation))?"CN":$model->address_nation) ;
$supplierInfo['address_state']= ((empty($model->address_state))?"":$model->address_state);
$supplierInfo['address_city']= ((empty($model->address_city))?"":$model->address_city) ;
$supplierInfo['address_street']= ((empty($model->address_street))?"":$model->address_street);
$supplierInfo['url']= ((empty($model->url))?"":$model->url);
$supplierInfo['post_code']= ((empty($model->post_code))?"":$model->post_code);
$supplierInfo['phone_number']= ((empty($model->phone_number))?"":$model->phone_number);
$supplierInfo['fax_number']= ((empty($model->fax_number))?"":$model->fax_number);
$supplierInfo['contact_name']= ((empty($model->contact_name))?"":$model->contact_name);
$supplierInfo['mobile_number']= ((empty($model->mobile_number))?"":$model->mobile_number);
$supplierInfo['qq']= ((empty($model->qq))?"":$model->qq);
$supplierInfo['ali_wanwan']= ((empty($model->ali_wanwan))?"":$model->ali_wanwan);
$supplierInfo['msn']= ((empty($model->msn))?"":$model->msn);
$supplierInfo['email']= ((empty($model->email))?"":$model->email);
$supplierInfo['status']= ((empty($model->status))?1:$model->status);
$supplierInfo['account_settle_mode']= ((empty($model->account_settle_mode))?"":$model->account_settle_mode);
$supplierInfo['payment_mode']= ((empty($model->payment_mode))?"":$model->payment_mode);
$supplierInfo['payment_account']= ((empty($model->payment_account))?"":$model->payment_account);
$supplierInfo['comment']= ((empty($model->comment))?"":$model->comment);
$supplierInfo['capture_user_id']= ((empty($model->capture_user_id))?\Yii::$app->user->id : $model->capture_user_id);
$supplierInfo['capture_user_name'] = ((empty($model->capture_user_id))? \Yii::$app->user->identity->getFullName() : UserHelper::getFullNameByUid($model->capture_user_id));
$supplierInfo['create_time']= ((empty($model->create_time))?"":$model->create_time);
$supplierInfo['update_time']= ((empty($model->update_time))?"":$model->update_time) ;
$supplierInfo['is_disable']= ((empty($model->is_disable))?"0":$model->is_disable);

// 特殊字符处理
foreach($supplierInfo as &$value){
	if (is_string($value))
	$value = htmlspecialchars($value);
}
$name_readonly = '';
if(isset($_GET['tt']) and $_GET['tt']=='view') $name_readonly = 'readonly';
else{
	if(is_numeric($model->supplier_id) and $model->supplier_id==0)  $name_readonly = 'readonly';
}
?>
<style>
.supplier_detail_win .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow-y: auto;	
}
#supplier_model_form td{
	padding:4px 0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#supplier_model_form .eagle-form-control{
	padding:0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#supplier_model_form table.table input,#supplier_model_form table.table select{
	width:100%;
}
.content_lfet{
	float:left;
}
.content_right{
	float:right;
}
#supplier_model_form input.ui-combobox-input{
	width: 95% !important;
}
</style>
<FORM id="supplier_model_form" role="form">
	<input type="hidden" name="tt" value="<?=isset($_GET['tt'])?$_GET['tt']:'' ?>" />
	<input type="hidden" id="supplier_id" class="eagle-form-control" name="supplier_id" value="<?=$supplierInfo['supplier_id'] ?>" />
	<table class="table" style="width:100%;margin-bottom:5px;font-size:12px;">
		<tr><th colspan="8"><?= TranslateHelper::t('供应商基本信息')?></th></tr>
		<tr>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('名称') ?></td>
		  	<td style="width:160px;"><input type="text" class="eagle-form-control" id="name" name="name" value="<?=$supplierInfo['name']?>" <?=$name_readonly ?>></td>
			<td style="width:55px;text-align:right;"><div><?=TranslateHelper::t('启用状态') ?></div></td>
		  	<td style="width:160px;">
  			<?php if($_GET['tt']=='edit' or $_GET['tt']=='create'): ?>
  			<?php if($model->supplier_id!==0):?>
  				<select id="status" name="status" value="" class="eagle-form-control">
  					<?php foreach ($statusMap as $k=>$v){?>
  					<option value="<?=$k ?>" <?=(empty($supplierInfo['status']) or $supplierInfo['status']==$v )?'selected="selected"':'' ?>><?=$v ?></option>
  					<?php }?>
  				</select>
  			<?php else: ?>
  				<select id="status" name="status" value="" class="eagle-form-control" readonly title="<?=TranslateHelper::t('默认供应商不能弃用或关闭') ?>">
  					<option value="1" selected><?=$statusMap['1'] ?></option>
  				</select>
  			<?php endif; ?>
  			<?php else: ?>
  				<input type="text" class="eagle-form-control" id="status" name="status" value="<?=$statusMap[$supplierInfo['status']] ?>">
  			<?php endif; ?>
		  	</td>
		  	
		  	
		  	<?php   if(!isset($_GET['tt']) or $_GET['tt']=='create'){
		  				$readonly="";
		  			}
		  			if(isset($_GET['tt']) && $_GET['tt']!=='create'){
						$readonly="readonly";
		  			}
		  	?>
		  	<td style="width:55px;text-align:right;"><?=TranslateHelper::t('录入时间') ?></td>
		  	<td style="width:160px;">
				<input type="text" name="create_time" id="create_time" class="eagle-form-control" value="<?=$supplierInfo['create_time'] ?>" <?=$readonly?>>
			</td>
			<?php if(isset($_GET['tt']) && $_GET['tt']=='view'){ ?>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('最后修改') ?></td>
		  	<td style="width:160px;">
				<input type="text" name="update_time" id="update_time" class="eagle-form-control" value="<?=$supplierInfo['update_time'] ?>">
			</td>
			<?php }else{?>
			<td style="width:55px;text-align:right;"></td><td style="width:160px;"></td>
			<?php } ?>
		</tr>
		<tr>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('录入人员') ?></td>
		  	<td style="width:160px;">
		  		<input type="hidden" id="capture_user_id" name="capture_user_id" value="<?=$supplierInfo['capture_user_id'] ?>">
		  		<input type="text" class="eagle-form-control" id="capture_user_name"  value="<?=$supplierInfo['capture_user_name'] ?>" disabled="disabled">
			</td>
			<td style="text-align:right;"><?=TranslateHelper::t('备注') ?></td>
		  	<td colspan="5">
		  		<textarea class="form-control" rows="3" name="comment" id="comment"><?= $supplierInfo['comment']?></textarea>	
		  	</td>
		</tr>
		<tr><th colspan="8"><?= TranslateHelper::t('支付信息')?></th></tr>
		<tr>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('结算方式') ?></td>
		  	<td style="width:160px;">
		  	    <?php if($_GET['tt']=='edit' or $_GET['tt']=='create'){ ?>
    				<select id="account_settle_mode" name="account_settle_mode" value="" class="eagle-form-control">
      					<?php foreach ($accountSettleMode as $k=>$v){?>
      					<option value="<?=$k ?>" <?=(empty($supplierInfo['account_settle_mode']) or $supplierInfo['account_settle_mode']==$k )?'selected="selected"':'' ?>><?=$v ?></option>
      					<?php }?>
      				</select>
      		    <?php } else{ 
          		            $accountsettlemode = '';
              		        foreach ($accountSettleMode as $k=>$v){
              		            if($supplierInfo['account_settle_mode']==$k){
          				            $accountsettlemode = $v;
          				            break;
                                }?>
  				        <?php }?>
  				        <input type="text" class="eagle-form-control" id="status" name="status" value="<?=$accountsettlemode?>">
  			    <?php }?>
			</td>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('支付渠道') ?></td>
		  	<td style="width:160px;">
				<input type="text" name="payment_mode" id="payment_mode" class="eagle-form-control" value="<?=$supplierInfo['payment_mode'] ?>">
			</td>
			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('支付账号') ?></td>
		  	<td style="width:160px;">
				<input type="text" name="payment_account" id="payment_account" class="eagle-form-control" value="<?=$supplierInfo['payment_account'] ?>">
			</td>
			<td></td>
		</tr>
		<tr><th colspan="8"><?= TranslateHelper::t('联系方式')?></th></tr>
		<tr>
		  	<td></td>
		  	<td colspan="7">
		  		<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('国家') ?></span></div>
		  		<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('州/省') ?></span></div>
		  		<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('市') ?></span></div>
		  		<div style="width:40%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('街道门牌') ?></span></div>
		  	</td>
		</tr>
		<tr>
		  	<td style="text-align:right;"><?=TranslateHelper::t('联系地址') ?></td>
		  	<td colspan="7">
		  		<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  		<?php if(!isset($_GET['tt']) or $_GET['tt']!=='view'){?>
		  			<select id="address_nation" name="address_nation" value="" class="eagle-form-control" style="padding:0px 5px;">
		  			<?php foreach ($countrys as $code=>$name){ ?>
		  				<?php if($code=='CN'){ ?>
		  				<option value="<?=$code ?>" <?=($supplierInfo['address_nation']=='CN')?'selected="selected"':'' ?>><?=TranslateHelper::t('中国') ?></option>
		  				<?php }else{?>
		  				<option value="<?=$code ?>" <?=($supplierInfo['address_nation']==$code)?'selected="selected"':'' ?>><?=$name ?></option>
		  				<?php }?>
		  			<?php } ?>
		  			</select>
		  		<?php } ?>
		  		<?php if(isset($_GET['tt']) && $_GET['tt']=='view'){
		  			$country_zh = $supplierInfo['address_nation'];
		  			foreach ($countrys as $code=>$name){
		  				if( $country_zh== $code){
		  					$country_zh = $name;
		  					break;
		  				}
		  			}
		  		?>
		  			<input type="text" class="eagle-form-control" id="address_nation" name="address_nation" value="<?=$country_zh ?>" style="padding:0px 5px;">
				<?php } ?>
		  		</div>
		  		<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  			<input type="text" class="eagle-form-control" id="address_state" name="address_state" value="<?=$supplierInfo['address_state'] ?>" style="padding:0px 5px;">
		  		</div>
		  		<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  			<input type="text" class="eagle-form-control" id="address_city" name="address_city" value="<?=$supplierInfo['address_city'] ?>" style="padding:0px 5px;">
		  		</div>
		  		<div style="width:40%;text-align:left;float:left;padding: 0px 4px;">
		  			<input type="text" class="eagle-form-control" id="address_street" name="address_street" value="<?=$supplierInfo['address_street'] ?>" style="padding:0px 5px;">
		  		</div>	
		  	</td>
		</tr>
		<tr>
			<td style="text-align:right;"><?=TranslateHelper::t('联系人') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="contact_name" name="contact_name" value="<?=$supplierInfo['contact_name'] ?>">
		  	</td>
			<td style="text-align:right;"><?=TranslateHelper::t('手机号码') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="mobile_number" name="mobile_number" value="<?=$supplierInfo['mobile_number'] ?>">
		  	</td>
		  	<td style="text-align:right;"><?=TranslateHelper::t('联系电话') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="phone_number" name="phone_number" value="<?=$supplierInfo['phone_number'] ?>">
		  	</td>
		  	<td style="text-align:right;"><?=TranslateHelper::t('邮政编码') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="post_code" name="post_code" value="<?=$supplierInfo['post_code'] ?>">
		  	</td>
		</tr>
		<tr>
			<td style="text-align:right;"><?=TranslateHelper::t('传真号码') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="fax_number" name="fax_number" value="<?=$supplierInfo['fax_number'] ?>">
		  	</td>
		  	<td style="text-align:right;"><?=TranslateHelper::t('QQ号码') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="qq" name="qq" value="<?=$supplierInfo['qq'] ?>">
		  	</td>
			<td style="text-align:right;"><?=TranslateHelper::t('阿里旺旺') ?></td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="ali_wanwan" name="ali_wanwan" value="<?=$supplierInfo['ali_wanwan'] ?>">
		  	</td>
			<td style="text-align:right;">email</td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="email" name="email" value="<?=$supplierInfo['email'] ?>">
		  	</td>
		<tr>
		<tr>
			<td style="text-align:right;">MSN</td>
		  	<td>
		  		<input type="text" class="eagle-form-control" id="msn" name="msn" value="<?=$supplierInfo['msn'] ?>">
		  	</td>
		  	<td colspan="6"></td>
		</tr>
		<tr>
			<td style="text-align:right;">网址</td>
			<td colspan="7">
		  		<input type="text" class="eagle-form-control" id="url" name="url" value="<?=$supplierInfo['url'] ?>">
		  	</td>
		</tr>
	</table>
</FORM>
<input id="data_empty_message" type="hidden" value="<?=TranslateHelper::t('无输入数据,请重新输入') ?>">

<script>
<?php //只读的情况下设置readonly 和  disabled
if ( in_array($_GET['tt'], ['view'])):?>
	$('#supplier_model_form input').prop('readonly','readonly');
	$('#supplier_model_form textarea').prop('disabled','disabled');
	$('#supplier_model_form button').prop('disabled','disabled');
	$('#supplier_model_form .cursor_pointer').css('display','none');
<?php else: //非view情况下才绑定日期插件?>
	$("#supplier_model_form #create_time,#supplier_model_form #update_time").datepicker({dateFormat:"yy-mm-dd"});
<?php endif;?>
	//
<?php foreach ($countrys as $code=>$name){?>
	supplier.list.countrys.push(["<?=$code ?>","<?=$name ?>"]);
<?php } ?>
	
	supplier.list.initFormValidateInput();
</script>
