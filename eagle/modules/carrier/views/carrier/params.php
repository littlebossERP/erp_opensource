<?php 
use eagle\modules\util\helpers\TranslateHelper;

 ?>
 <style>
	table {
		font-size: 12px;
	}
 </style>
 <?php 
 $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/deliverycarrier.js", ['depends' => ['yii\web\JqueryAsset']]);
  ?>
<script>
	
	paramsUrl = "<?= \Yii::$app->urlManager->createUrl('carrier/carrier/params') ?>";
	indexUrl = "<?= \Yii::$app->urlManager->createUrl('carrier/carrier/index') ?>";

	function typeOperate(type,me){
		var tableEle = $(me).parents('table');
		var $table = $(tableEle);
		//如果是订单参数和商品参数 则进行操作
		if(type == 2 || type == 3){
			$table.find('.append').show();
		}else{
			//移除append元素
			$table.find('.append').hide().find('input').val('');
		}
	}
</script>
<h4 class="modal-title" id="myModalLabel" style="display:inline-block">物流商参数</h4><br><br>
<form action="<?= \Yii::$app->urlManager->createUrl('carrier/carrier/params') ?>" method="post" id="create_form" style="margin-bottom:100px">
<input type="hidden" name="check" value="1">
<input type="hidden" name="code" value="<?=$code ?>">
<?php if(!empty($carrier_param)):
 ?>
<?php foreach($carrier_param as $p):
 ?>
<div class="table-responsive">
	<!-- table -->
    <table cellspacing="0" cellpadding="0" class="table table-striped">
    	<tr style="background-color:#abcdef">
	        <td><?=TranslateHelper::t('参数中文名') ?></td>
	        <td><input type="text" name="carrier_param_name[]"  value="<?= @$p['carrier_param_name'] ?>"></td>
	        <td><?=TranslateHelper::t('接口字段名') ?></td>
	        <td><input type="text" name="carrier_param_key[]" value="<?= @$p['carrier_param_key'] ?>"></td>
	        <td><?=TranslateHelper::t('必填') ?></td>
	        <td>
	        	<select name="is_required[]">
	        		<option value="0" <?= isset($p['is_required'])&&$p['is_required']==0?'selected':'' ?>>否</option>
	        		<option value="1" <?= isset($p['is_required'])&&$p['is_required']==1?'selected':'' ?>>是</option>
	        	</select>
	        </td>
	        <td><?=TranslateHelper::t('参数类型') ?></td>
	    	<td>
	        	<select name="type[]" onchange="typeOperate(this.value,this)">
	        		<option value="0" <?= @$p['type']=='0'?'selected':'' ?>><?=TranslateHelper::t('认证参数') ?></option>
	        		<option value="1" <?= @$p['type']=='1'?'selected':'' ?>><?=TranslateHelper::t('普通参数') ?></option>
	        		<option value="2" <?= @$p['type']=='2'?'selected':'' ?>><?=TranslateHelper::t('订单参数') ?></option>
	        		<option value="3" <?= @$p['type']=='3'?'selected':'' ?>><?=TranslateHelper::t('商品参数') ?></option>
	        	</select>
	    	</td>
	    	<td><?=TranslateHelper::t('参数显示方式') ?></td>
	    	<td>
	        	<select name="display_type[]" >
	        		<option value="text" <?= @$p['display_type']=='text'?'selected':'' ?>>文本框</option>
	        		<option value="dropdownlist" <?= @$p['display_type']=='dropdownlist'?'selected':'' ?>>下拉框</option>
	        	</select>
	    	</td>
	    	<td><?=TranslateHelper::t('排序') ?></td>
	    	<td>
	        	<input type="text" name="sort[]" value="<?= @$p['sort'] ?>" style="width:30px;">
	    	</td>
	    	<td>
	    		<a class="btn btn-success btn-sm" onclick="delparam(this)">删除该参数</a>
	    	</td>
	    </tr>
	    <tr>
	        <td><?=TranslateHelper::t('参数值') ?></td>
	        <td colspan="12">
	        <?php $param_str = '';
	        if (is_array($p->carrier_param_value) && count($p->carrier_param_value)){
				foreach ($p->carrier_param_value as $k=>$v){
					$param_str .= $k.':'.$v.';';
				}
			}
			?>
				<textarea name="carrier_param_value[]" id="" cols="130" rows="3"><?= $param_str;?></textarea>
	        </td>
	    </tr>
	    <?php
	    	$display=(isset($p['input_style']) && strlen($p['input_style']) >0) || (isset($p['data_key']) && strlen($p['data_key']) >0 )?'':'none';
	     ?>
	    	<tr class="append" style="background-color:#ddd;display:<?=$display ?>">
		    	<td>数据库字段名</td>
		    	<td><input type="text" name="data_key[]" value="<?= isset($p['data_key'])&&strlen($p['data_key'])>0?$p['data_key']:'' ?>"></td>
		    	<td>style</td>
		    	<td><input type="text" name="input_style[]" value="<?= isset($p['input_style']) && strlen($p['input_style'])?$p['input_style']:'' ?>"></td>
		    	<td colspan="9"></td>
	    	</tr>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>
</form>
<button class="btn btn-success" id="addnewbutton">添加新参数</button>
<button class="btn btn-primary" id="savebutton">保存</button>


<div class="table-responsive" id="backupdiv" style="display:none">
	<!-- table -->
    <table cellspacing="0" cellpadding="0" class="table table-striped">
    	<tr style="background-color:#abcdef">
	        <td><?=TranslateHelper::t('参数中文名') ?></td>
	        <td><input type="text" name="carrier_param_name[]"  value=""></td>
	        <td><?=TranslateHelper::t('接口字段名') ?></td>
	        <td><input type="text" name="carrier_param_key[]" value=""></td>
	        <td><?=TranslateHelper::t('必填') ?></td>
	        <td>
	        	<select name="is_required[]">
	        		<option value="0">否</option>
	        		<option value="1">是</option>
	        	</select>
	        </td>
	        <td><?=TranslateHelper::t('参数类型') ?></td>
	    	<td>
	        	<select name="type[]" onchange="typeOperate(this.value,this)" >
	        		<option value="1"><?=TranslateHelper::t('普通参数') ?></option>
	        		<option value="0"><?=TranslateHelper::t('认证参数') ?></option>
	        		<option value="2"><?=TranslateHelper::t('订单参数') ?></option>
	        		<option value="3"><?=TranslateHelper::t('商品参数') ?></option>
	        	</select>
	    	</td>
	    	<td><?=TranslateHelper::t('参数显示方式') ?></td>
	    	<td>
	        	<select name="display_type[]" >
	        		<option value="text">文本框</option>
	        		<option value="dropdownlist">下拉框</option>
	        	</select>
	    	</td>
	    	<td><?=TranslateHelper::t('排序') ?></td>
	    	<td>
	        	<input type="text" name="sort[]" style="width:30px;">
	    	</td>
	    	<td>
	    		<a class="btn btn-success" onclick="delparam(this)">删除该参数</a>
	    	</td>
	    </tr>
	    <tr>
	        <td><?=TranslateHelper::t('参数值') ?></td>
	        <td colspan="12">
				<textarea name="carrier_param_value[]" cols="130" rows="3"></textarea>
	        </td>
	    </tr>
		<tr class="append" style="background-color:#ddd" style="display:none">
	    	<td>数据库字段名</td>
	    	<td><input type="text" name="data_key[]"></td>
	    	<td>style</td>
	    	<td><input type="text" name="input_style[]"></td>
	    	<td colspan="9"></td>
		</tr>
    </table>
</div>

