<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/PaypalAccounts.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.PaypalAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);

?>
<style>
.PaypalAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;heig">
	<form id="platform-PaypalAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/paypal-accounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="uid" type=hidden value="<?php echo $PaypalData['uid']; ?>" />
		<input class="form-control" name="ppid" type=hidden value="<?php echo $PaypalData['ppid']; ?>" />
	<?php } ?>    
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Paypal 账号信息设置') ?></label></div>
			<table>        
				<tr>
					<td style="font-size:16px;"><?= TranslateHelper::t('Paypal账号:') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="paypal_user" value="<?=$PaypalData['paypal_user'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<?php else:?>
						<input class="form-control" type="text" name="paypal_user" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr>
					<td style="font-size:16px;"><?= TranslateHelper::t('Paypal地址作为ebay买家订单地址 ：') ?></td>
					<td style="font-size:16px;"><select class="form-control" style="width: auto;" name="overwrite_ebay_consignee_address" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="Y" <?php if ($mode<>"new" and $PaypalData["overwrite_ebay_consignee_address"]=='Y') echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="N" <?php if ($mode<>"new" and $PaypalData["overwrite_ebay_consignee_address"]=='N') echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				
				<tr style="color:blue;font-size:16px;">
					<td colspan="2">
						请使用以下方式授权paypal, 详细图文教程请查看<a target="_blank" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_232.html')?>">paypal授权帮助文档</a>。
					</td>
				</tr>
				<tr>
					<td colspan="2" style="font-size:14px;">
					<span style="float:left;margin:5px;">1.登录Paypal(www.paypal.com);</span>
					<span style="float:left;margin:5px;">2.在"账户首页"下点击"卖家习惯设定"菜单，进入页面;</span>
					<span style="float:left;margin:5px;">3.选择"api访问" 点击"添加"或"更新";</span>
					<span style="float:left;margin:5px;">4.在"选项1"里点击"授予 API 许可"或者"添加或编辑 API 许可";</span>
					<span style="float:left;margin:5px;">5.在"第三方许可用户名"中输入 paypal_api1.littleboss.com 并点击"查找";</span>
					<span style="float:left;margin:5px;">6.在可用权限下选择"获取有关单笔交易的信息"和"在您的交易中搜索符合特定条件的物品并显示结果"并点击"新增";</span>
					</td>
				</tr>
			</table>
        </div>
	</form>
	
</div>
<script>
</script>