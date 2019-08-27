<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/amazonAccountsNewOrEdit.js?=".time());

$this->registerJs('platform.amazonAccountsNeworedit.setting.mode="'.$mode.'";', \yii\web\View::POS_READY);

$emailPrefix="";
$emailPasswd="";
$inputStatus="";
$boxTitle = "";

//mode--new,view,edit
if ($mode <> "new"){
	$this->registerJs('platform.amazonAccountsNeworedit.setting.amazonUser='.json_encode($amazonUser).';', \yii\web\View::POS_READY);
	$this->registerJs('platform.amazonAccountsNeworedit.setting.chosenCountryList='.json_encode($chosenCountryList).';', \yii\web\View::POS_READY);
	
// 	if ($amazonEmail<>null){
// 		$emailPrefix=$amazonEmail["userid"];
// 		$emailPasswd=$amazonEmail["userpass"];
// 	}
}

$this->registerJs('platform.amazonAccountsNeworedit.initWidget();', \yii\web\View::POS_READY);
?>
	<form id="platform-amazonAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/amazonAccounts/save" method="post">
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="amazon_uid" type=hidden value="<?php echo $amazonUser['amazon_uid']; ?>"  />
	<?php } ?>    
	
	<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
	
	    <div style="font-size: 19px;font-weight:bold"><label><?php echo TranslateHelper::t('amazon api账号信息设置');?></label></div>
        <table>        
            <tr>
                <td><?php echo TranslateHelper::t('MERCHANT_ID(Seller Id)');?>:</td>
                <?php if ($mode<>"new"):?>
                <td><input class="form-control" type="text" name="merchant_id" value="<?=$amazonUser['merchant_id'] ?>" style="width:180px;display:inline;" readonly/><span style="color:red;margin-left:3px">*</span></td>
            	<?php else:?>
            	<td><input class="form-control" type="text" name="merchant_id" value="" style="width:180px;display:inline;"/><span style="color:red;margin-left:3px">*</span></td>
            	<?php endif;?>
            </tr>
            
            <tr>
	            <td colspan="2">
	                    <div id="marketplace-options-div" style="margin-top: 10px;">
			            <div style="margin-bottom:10px"><label><?php echo TranslateHelper::t('请勾选国家分站(MarketplaceId). ');?> <a id="marketplaceid-country-tip-a" style="color:blue" href="#"><?php echo TranslateHelper::t('MarketplaceId跟国家的对应关系，点击这里');?></a></label></div>
			            <div class="checkbox">
			            <?php 
			               $countrycodeNameMap = SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP;
			               foreach($countrycodeNameMap as $code=>$name):
			            ?>
			                 <label><input class="form-control" style="display: inline;width: auto;height: auto;" id="marketplace_<?php echo $code; ?>" name="marketplace_<?php echo $code; ?>" type="checkbox"  <?php if ($mode=="view") echo "disabled"; ?>  /><?php echo TranslateHelper::t($name); ?></label>                 
			            <?php endforeach; ?>
			            </div>		            
			                        
			        </div>            
	            </td>
            </tr>
            
            <tr>
                <td><?php echo TranslateHelper::t('ACCESS_KEY_ID');?>:</td>
                
                <?php if ($mode<>"new"):?>
                <td><input class="form-control" name="access_key_id" value="<?=!empty($amazonUser['access_key_id'])?$amazonUser['access_key_id']:'' ?>"  style="width:180px;display:inline;" readonly /><span style="color:red;margin-left:3px">*</span></td>
            	<?php else:?>
            	<td><input class="form-control" name="access_key_id" value=""  style="width:180px;display:inline;"/><span style="color:red;margin-left:3px">*</span></td>
            	<?php endif;?>
                
            </tr>            
            
            <?php if ($mode=="new") :?>
            <tr>
                <td><?php echo TranslateHelper::t('SECRET_ACCESS_KEY');?>:&nbsp;&nbsp;&nbsp;</td>
                <td><input class="form-control" type="password" style="width:180px;display:inline;" name="secret_access_key" /><span style="color:red;margin-left:3px">*</span></td>
            </tr>
            <?php endif; ?>
      
            <tr>
                <td><?php echo TranslateHelper::t('店铺名称(自定义)');?>:</td>
                <?php if ($mode == "new"):?>
 				<td><input class="form-control" type="text" name="store_name" value="" style="width:180px;display:inline;" /><span style="color:red;margin-left:3px">*</span></td>
            	<?php else:?>
            	<td><input class="form-control" type="text" name="store_name" value="<?=$amazonUser['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?>  /><span style="color:red;margin-left:3px">*</span></td>
            	<?php endif;?>
                           </tr>
            <tr style="color:blue"><td colspan="2"><label><?php echo TranslateHelper::t('店铺名称并不是amazon提供的接口信息，是小老板内部管理该店铺的展示名称，可以是任意名字。');?></label></td></tr>        
            
            
            <tr>
                <td><?php echo TranslateHelper::t('系统同步是否开启');?>:</td>
                <td><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
                       <option value="1" <?php if ($mode<>"new" and $amazonUser["is_active"]==1) echo "selected";  ?>><?php echo TranslateHelper::t('是');?></option>
                       <option value="0" <?php if ($mode<>"new" and $amazonUser["is_active"]==0) echo "selected";  ?>><?php echo TranslateHelper::t('否');?></option></select></td>
            </tr>
        </table>
        
        <?php 
        // @todo:解释文档页面多语言问题。
        $currentLan = TranslateHelper::getCurrentLanguague();
        $linkSuffix = "";
        if("zh-cn" == $currentLan ){
			$linkSuffix = "";
		}
        ?>
 <!--        <div style="margin-top: 10px;margin-left:5px"><?php //echo TranslateHelper::t('帮助文档');?>:<a target="_blank" style="color:blue;" href="<?php //echo \Yii::getAlias('@web');?>/docs/amazonHelper<?= $linkSuffix ?>.html"><?php //echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?></a></div> -->
        <div style="margin-top: 10px;margin-left:5px"><?php echo TranslateHelper::t('帮助文档');?>:
            <a target="_blank" style="color:blue;" href="http://help.littleboss.com/word_list_247_516.html">
                <?php echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?>
            </a>
        </div>

   	</div>

        <div class="amazon-account-email-set" style="display: none;margin-top:25px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
           <div style="font-size: 19px;font-weight:bold;margin-bottom:8px"><label><?php echo TranslateHelper::t('amazon卖家邮箱设置');?></label></div>           
           <div>
               <label><?php echo TranslateHelper::t('邮箱账号');?></label><input name="email_name_prefix" type="text" value="<?php echo $emailPrefix; ?>"    <?php if ($mode=="view") echo "disabled"; ?>  />
	           @<select name="email_name_suffix"    <?php echo $inputStatus; ?>   >
	                 <option value ="gmail">gmail.com</option>	                 
	           </select>
            </div>
            <div>
               <label><?php echo TranslateHelper::t('邮箱密码');?></label><input name="email_passwd" type="password" value="<?php echo $emailPasswd; ?>"   <?php if ($mode=="view") echo "disabled"; ?>   />
            </div>            
        </div>        
	</form>
<br/>

<!-- marketplaceid和国家名称的对应关系弹出框 -->
<div id="amazon-marketplaceid-countryname-win" class="modal fade" tabindex="-1" data-backdrop="false"  >
    <div class="modal-dialog">
        <div class="modal-content"></div><!-- /.modal-content -->
    </div>
</div>
<!-- /.modal-dialog -->
