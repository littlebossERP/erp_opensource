<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SaasCdiscountUser;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\util\helpers\CountryHelper;
?>

<?php 
$uid = \Yii::$app->user->id;

$this->registerJs("initFormValidateInput();" , \yii\web\View::POS_READY);

?>

<div>
	<form id="seller_invoice_info_form">
		<input type="hidden" name="act" value="<?=empty($act)?'add':$act ?>" >
		<input type="hidden" name="info_id" value="<?=empty($infos['id'])?'':$infos['id'] ?>" >
		<table class="table">
			<tr><th colspan="4">卖家信息(最好为销售平台对应语言)</th></tr>
			<tr>
				<td>公司名称</td>
				<td colspan="3"><input type="text" name="company" value="<?=empty($infos['company'])?'':$infos['company']?>" style="width:90%;"><span style="color:red">*</span></td>
			</tr>
			<tr>
				<td>地址</td>
				<td colspan="3"><input type="text" name="address" value="<?=empty($infos['address'])?'':$infos['address']?>" style="width:90%;"><span style="color:red">*</span></td>
			</tr>
			<tr>
				<td>VAT码</td>
				<td ><input type="text" name="vat" value="<?=empty($infos['vat'])?'':$infos['vat']?>"></td>
				<td>增值税率(%)</td>
				<td ><input type="text" name="tax_rate" value="<?=empty($infos['tax_rate'])?'':$infos['tax_rate']?>"></td>
			</tr>
			<tr>
				<td>税费计算方式:</td>
				<td colspan="3">
					<input type="radio" name="tax_formula" id="tax_formula_1" value="1" <?=(empty($infos['tax_formula']) || $infos['tax_formula']==1)?'checked':'' ?> ><label for="tax_formula_1" style="margin-right: 20px;">不含税价格=总价/(1+增值税率)</label>
					<input type="radio" name="tax_formula" id="tax_formula_2" value="2" <?=(!empty($infos['tax_formula']) && $infos['tax_formula']==2)?'checked':'' ?> ><label for="tax_formula_2">不含税价格=总价*(1-增值税率)</label>
				</td>
			</tr>
			<tr><td colspan="4">设置税率会影响所有与此信息关联的店铺发票，如果不需要显示税率，您需要另外再设置一条信息</td></tr>
			<tr>
				<td>签名图片Url</td>
				<td colspan="3"><input type="text" name="autographurl" value="<?=empty($infos['autographurl'])?'':$infos['autographurl']?>" style="width:90%;"></td>
			</tr>
			<tr>
				<td width="15%">电话</td>
				<td width="35%"><input type="text" name="phone" value="<?=empty($infos['phone'])?'':$infos['phone']?>"></td>
				<td width="15%">e-mail</td>
				<td width="35%"><input type="text" name="email" value="<?=empty($infos['email'])?'':$infos['email']?>"></td>
			</tr>
			<tr>
				<td>发票类型:</td>
				<td colspan="3">
					<input type="radio" name="invoice_type" id="invoice_type_1" value="O" <?=(empty($infos['type']) || $infos['type']=="O")?'checked':'' ?> onclick="isShowStores()" ><label for="invoice_type_1" style="margin-right: 20px;">一般发票</label>
					<input type="radio" name="invoice_type" id="invoice_type_2" value="G" <?=(!empty($infos['type']) && $infos['type']=="G")?'checked':'' ?> onclick="isShowStores()" ><label for="invoice_type_2">高青发票</label>
				</td>
			</tr>
		</table>
		<table class="table" id="table_Stores_Info" style="<?=(!empty($infos['type']) && $infos['type']=="G")?'display:none':''?> ">
			<?php $invoicePlatforms=OrderHelper::getInvoicePlatforms(); ?>
			<tr><th colspan="4">适用店铺(目前支持发票功能的平台有：<?=implode(',', $invoicePlatforms) ?>)</th></tr>
			<tr><td colspan="4" style="color:#DA6C6C;">如果可选店铺没有出现在下面列表中，则可能该店铺未启用，或者被其他发票信息绑定了</td></tr>
			<?php $selectedStores = json_decode($infos['stores'],true);if(empty($selectedStores)) $selectedStores=[];?>
			<?php foreach ($invoicePlatforms as $platform):?>
			<tr style="border:2px #D9EFFC solid">
				<td style="background-color: #C3FFD3;"><?=$platform ?> :</td>
				<!-- Cdiscount发票信息 -->
				<?php if($platform=='cdiscount'){
					$cdStores = empty($canChoseStores['cdiscount']['FR'])?[]:$canChoseStores['cdiscount']['FR'];
				?>
				<td colspan="3">
					<?php	//已选店铺
					if(!empty($selectedStores['cdiscount']['FR'])){
						foreach ($selectedStores['cdiscount']['FR'] as $selectedStore):
					?>
					<div style="float: left;margin:5px;">
						<label for="<?=$platform.'_'.$selectedStore?>"><?=$selectedStore?></label>
						<input type="checkbox" name="stores[cdiscount][FR][]" id="<?=$platform.'_'.$selectedStore?>" value="<?=$selectedStore?>" checked>
					</div>
					<?php endforeach;}?>
					<?php	//未选且可选店铺
					foreach ($cdStores as $cdStore){?>
					<div style="float: left;margin:5px;">
						<label for="<?=$platform.'_'.$cdStore?>"><?=$cdStore?></label>
						<input type="checkbox" name="stores[cdiscount][FR][]" id="<?=$platform.'_'.$cdStore?>" value="<?=$cdStore?>">
					</div>
					<?php }?>
				</td>
				<?php }?>
				<!-- Amazon发票信息 -->
				<?php if($platform=='amazon'){
					//已选店铺数组：[merchant_id=>[site....],...]
					if(!empty($selectedStores['amazon'])){
						$selectedAmzArr = [];
						foreach ($selectedStores['amazon'] as $site=>$mkIdArr){
							foreach ($mkIdArr as $mkId){
								$selectedAmzArr[$mkId][] = $site;
							}
						}
					}else 
						$selectedAmzArr=[];
				?>
				<td colspan="3">
					<table class="table">
					<?php 
					//未选且可选店铺数组：[merchant_id=>[site....],...]
					$amzStores = empty($canChoseStores['amazon'])?[]:$canChoseStores['amazon'];
					$amzStoreMappingMk=[];
					foreach ($amzStores as $mk=>$amzStoreArr){
						foreach ($amzStoreArr as $amzStore){
							$amzStoreMappingMk[$amzStore][] = $mk;
							$amzStoreMappingMk[$amzStore] = array_unique($amzStoreMappingMk[$amzStore]);
						}
					}
					foreach ($selectedAmzArr as $mkId=>$stores){
						if(!isset($amzStoreMappingMk[$mkId]))
							$amzStoreMappingMk[$mkId]=[];
					}
					?>
					<?php foreach ($amzStoreMappingMk as $store=>$mkArr){?>
					<?php 
					   $amzAccount = SaasAmazonUser::find()->where(['merchant_id'=>$store])->one();
					   if(empty($amzAccount)) continue;
					?>
						<tr style="border:2px #D9EFFC solid">
							<?php ?>
							<td><?=$amzAccount->store_name ?> : </td>
							<td>
								<?php if(!empty($selectedAmzArr[$store])):
									foreach ($selectedAmzArr[$store] as $selectedAmzMk):
								?>
								<div style="float: left;margin:5px;">
									<label for="<?=$platform.'_'.$store.'_'.$selectedAmzMk?>"><?=$selectedAmzMk?></label>
									<input type="checkbox" name="stores[amazon][<?=$selectedAmzMk?>][]" id="<?=$platform.'_'.$store.'_'.$selectedAmzMk?>" value="<?=$store?>" checked>
								</div>
								<?php endforeach;endif;?>
							<?php foreach ($mkArr as $mk){?>
								<div style="float: left;margin:5px;">
									<label for="<?=$platform.'_'.$store.'_'.$mk?>"><?=$mk?></label>
									<input type="checkbox" name="stores[amazon][<?=$mk?>][]" id="<?=$platform.'_'.$store.'_'.$mk?>" value="<?=$store?>">
								</div>
							<?php } ?>
							</td>
						</tr>
						<?php } ?>
					</table>
				</td>
				<?php }?>
				
				<!-- Priceminister发票信息 -->
				<?php if($platform=='priceminister'){
					$pmStores = empty($canChoseStores['priceminister']['FR'])?[]:$canChoseStores['priceminister']['FR'];
				?>
				<td colspan="3">
					<?php	//已选店铺
					if(!empty($selectedStores['priceminister']['FR'])){
						foreach ($selectedStores['priceminister']['FR'] as $selectedStore):
					?>
					<div style="float: left;margin:5px;">
						<label for="<?=$platform.'_'.$selectedStore?>"><?=$selectedStore?></label>
						<input type="checkbox" name="stores[priceminister][FR][]" id="<?=$platform.'_'.$selectedStore?>" value="<?=$selectedStore?>" checked>
					</div>
					<?php endforeach;}?>
					<?php	//未选且可选店铺
					foreach ($pmStores as $pmStore){?>
					<div style="float: left;margin:5px;">
						<label for="<?=$platform.'_'.$pmStore?>"><?=$pmStore?></label>
						<input type="checkbox" name="stores[priceminister][FR][]" id="<?=$platform.'_'.$pmStore?>" value="<?=$pmStore?>">
					</div>
					<?php }?>
				</td>
				<?php }?>
				
				<?php //@todo 后续支持的平台		?>
			</tr>
			<?php endforeach;?>
		</table>
	</form>
</div>