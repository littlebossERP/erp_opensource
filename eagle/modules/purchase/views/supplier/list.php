<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/supplier/supplier_list.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("supplier.list.init();" , \yii\web\View::POS_READY);
?>

<style>
.div_inner_td{
	width: 100%;
}
.supplier_list th, .supplier_list td{
	padding: 4px !important;
  	vertical-align: middle !important;
	border: 0px !important;
}

.supplier_list .btn-xs{
	padding:0px 3px !important;
	margin:0px !important;
}
</style>
<FORM action="<?= Url::to(['/purchase/supplier/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left">

	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按供应商启用状态过滤显示结果') ?>">
  			<SELECT name="status" value="" style="width:150px;margin:0px" class="eagle-form-control">
	  			<OPTION value=""><?= TranslateHelper::t('是否已启用') ?></OPTION>
	  				<?php foreach($supplierStatus as $k=>$v){
						echo "<option value='".$k."'";
						if(isset($_GET['status']) && $_GET['status']==$k && is_numeric($_GET['status'])) echo " selected ";
						echo ">".$v."</option>";
					} ?>
  			</SELECT>
  		</div>
  	</div>
	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按结算方式过滤显示结果') ?>">
  			<SELECT name="account_settle_mode" value="" style="width:150px;margin:0px" class="eagle-form-control">
	  			<OPTION value="" <?=(!isset($_GET['account_settle_mode']) or !is_numeric($_GET['account_settle_mode']) )?" selected ":'' ?>><?= TranslateHelper::t('结算方式') ?></OPTION>
	  				<?php foreach($accountSettleMode as $k=>$v){
						echo "<option value='".$k."'";
						if(isset($_GET['account_settle_mode']) && $_GET['account_settle_mode']==$k && is_numeric($_GET['account_settle_mode'])) echo " selected ";
						echo ">".$v."</option>";
					} ?>
  			</SELECT>
  		</div>
  	</div>
  	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('选择某属性并输入关键字模糊查询供应商') ?>">
  			<SELECT name="queryKey" value="" style="width:150px;margin:0px;float:left" class="eagle-form-control">
  			<?php $searchSelecter=array(
  						'name'=>TranslateHelper::t('供应商名称'),
  						'contact_name'=>TranslateHelper::t('联系人'),
  						'phone_number'=>TranslateHelper::t('联系电话'),
  						'mobile_number'=>TranslateHelper::t('手机号码'),
  						'qq'=>'qq',
  						'email'=>TranslateHelper::t('email'),
  						'address'=>TranslateHelper::t('地址'),
  						'comment'=>TranslateHelper::t('备注'),
  				);
  				foreach ($searchSelecter as $key=>$name){
					$selected='';
  				?>
	  				<OPTION value="<?=$key ?>" 
	  				<?php if(isset($_GET['queryKey']) && $_GET['queryKey']==$key) $selected='selected';?>
	  				<?php if(!isset($_GET['queryKey']) and $key=='name') $selected='selected';?>
	  				<?=$selected ?>><?= $name ?></OPTION>
	  			<?php } ?>
  			</SELECT>
  			<input name="queryValue" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('输入查询字段')?>" value="<?php if(isset($_GET['queryValue'])) echo $_GET['queryValue'] ?>"  style="width:160px;float:left;margin:0px"/>
  		</div>
  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin:0px 0px 0px 5px;padding:0px;height:28px;width:30px;border-radius:0px;border:1px solid #b9d6e8;">
				<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
		    </button>
		</div>
		<div class="div-input-group" style="float: left;margin-left:5px;">
		    <button type="button" id="btn_clear" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
				<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
			</button>
		</div>
  	</div>
</FORM>
		
<div style="width:100%;float:left;margin:10px 0px;">
	<div style="float:left;">
	   <button id="create_new_supplier" type="button" class="btn-xs btn-transparent font-color-1">
	   	<span class="glyphicon glyphicon-plus"></span>
	   	<?=TranslateHelper::t('新建供应商') ?>
	   </button>
	</div>
	<div style="margin-left:10px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" id="batch_inactivate_supplier">
			<span class="glyphicon glyphicon-remove"></span>
			<?=TranslateHelper::t('批量停用供应商') ?>
		</button>
	</div>
	<div style="margin-left:10px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" id="batch_activate_supplier">
			<span class="glyphicon glyphicon-ok"></span>
			<?=TranslateHelper::t('批量启用供应商') ?>
		</button>
	</div>
	<div style="margin-left:10px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" id="batch_del_supplier">
			<span class="egicon-trash" style="height: 16px;"></span>
			<?=TranslateHelper::t('批量删除供应商') ?>
		</button>
	</div>
</div>
<!-- table -->
<div class="supplier_list" style="width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;float:left;" class="table table-hover">
		<tr class="list-firstTr">
			<th title="<?=TranslateHelper::t('全选') ?>" width="30px"><input type="checkbox" id="select_all"></th>
			<th width="200px"><?=$sort->link('name',['label'=>TranslateHelper::t('供应商名称')]) ?></th>
			<th width="100px"><?=$sort->link('contact_name',['label'=>TranslateHelper::t('联系人')]) ?></th>
			<th width="100px"><?=$sort->link('phone_number',['label'=>TranslateHelper::t('联系电话')]) ?></th>
			<th width="100px"><?=$sort->link('mobile_number',['label'=>TranslateHelper::t('手机')]) ?></th>
			<th width="100px">e-mail</th>
			<th width="200px"><?=TranslateHelper::t('地址/网址') ?></th>
			<th width="60px"><?=$sort->link('status',['label'=>TranslateHelper::t('状态')]) ?></th>
			<th width="100px"><?= TranslateHelper::t('操作')?></td>
		</tr>
        <?php foreach($suppliersListData['data'] as $index=>$supplier):?>
        	<?php $address_nation = $supplier['address_nation'];
        		if( isset($countrys[$supplier['address_nation']]) ){
        			$address_nation = $countrys[$supplier['address_nation']];
        	}?>
		<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
		<?php if(!empty($supplier['supplier_id'])){ ?>
           	<td ><input type="checkbox" class="select_one" name="orderSelected" value="<?=$supplier['supplier_id']?>" ></td>
        <?php }else{ ?>
        	<td></td>
        <?php } ?>
            <td ><?=$supplier['name'] ?></td>
            <td ><?=$supplier['contact_name'] ?></td>
            <td ><?=$supplier['phone_number'] ?></td>
            <td ><?=$supplier['mobile_number'] ?></td>
            <td ><?=$supplier['email'] ?></td>
            <td >
            	<?=$address_nation ?> <?=$supplier['address_state'] ?> <?=$supplier['address_city'] ?> <?=$supplier['address_street'] ?>
            	<?=empty($supplier['url'])?'':'<br><a href="'.$supplier['url'].'" target="_blank">'.$supplier['url'].'</a>';?>
            </td>
            <td><?php if($supplier['status']==1){?>
            	<p class="text-success" style="margin:0px;">
					<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
					<?=$supplierStatus[$supplier['status']] ?>
				</p>
				<?php }elseif($supplier['status']==2){?>
				<p class="text-muted" style="margin:0px;">
					<span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
					<?=$supplierStatus[$supplier['status']] ?>
				</p>
            	<?php }else{?>
            		<?='--' ?>
            	<?php } ?>
			</td>
	        <td >
            	<button type="button" onclick="supplier.list.viewSupplier(<?=$supplier['supplier_id'] ?>)" class="btn-xs btn-transparent font-color-1" 
            	style="border-style:none;" title="<?=TranslateHelper::t('查看详情') ?>">
            		<span class="egicon-eye"></span>
            	</button>
            	<button type="button" onclick="supplier.list.editSupplier(<?=$supplier['supplier_id'] ?>)" class="btn-xs btn-transparent font-color-1" 
            	title="<?=TranslateHelper::t('修改供应商信息') ?>" style="vertical-align: middle;">
            		<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
            	</button>
            	<button type="button" onclick="supplier.list.supplierProducts(<?=$supplier['supplier_id'] ?>,this)" class="btn-xs btn-transparent font-color-1" 
            	title="<?=TranslateHelper::t('供应商供应的产品列表') ?>" style="vertical-align: middle;">
            		<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
            	</button>
            	<?php if(isset($supplier['status']) && $supplier['status']==1 ) { ?>
            	<?php if(!empty($supplier['supplier_id'])){ ?>
            	<button type="button" onclick="supplier.list.inactivateSupplier(<?=$supplier['supplier_id'] ?>,this,'one')" class="btn-xs btn-transparent font-color-1" 
            	title="<?=TranslateHelper::t('停用该供应商') ?>" style="vertical-align: middle;">
            		<span class="glyphicon glyphicon-remove"></span>
            	</button>
            	<?php } ?>
            	<?php }else{ ?> 	
            	<button type="button" onclick="supplier.list.activateSupplier(<?=$supplier['supplier_id'] ?>,this,'one')" class="btn-xs btn-transparent font-color-1" 
            	title="<?=TranslateHelper::t('重新启用该供应商') ?>" style="vertical-align: middle;">
            		<span class="glyphicon glyphicon-ok"></span>
            	</button>
            	<?php } ?>
				<?php if(!empty($supplier['supplier_id'])){ ?>
	            <button type="button" onclick="supplier.list.deleteSupplier(<?=$supplier['supplier_id'] ?>,this,'one')" class="btn-xs btn-transparent font-color-1" title="<?=TranslateHelper::t('删除供应商') ?>">
	            	<span class="egicon-trash" style="height:16px;"></span>
	            </button>
	            <?php } ?>
	        </td>
	    </tr>
         
        <?php endforeach;?>
    </table>

</div>
		
<?php if($suppliersListData['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$suppliersListData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $suppliersListData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

<!-- Modal -->
<div class="supplier_detail_win"></div>
<div class="operation_result"></div>
<div class="supplier_prod_win"></div>
<!-- /.modal-dialog -->

