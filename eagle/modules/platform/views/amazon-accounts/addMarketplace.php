<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/amazonAccountsNewOrEdit.js?=".time());
$this->registerJs('platform.amazonAccountsNeworedit.initWidget();', \yii\web\View::POS_READY);
?>
	<form id="platform-amazonAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/amazonAccounts/update" method="post">
		<input class="form-control" type="hidden" name="store_name" value="<?=$amazonUser['store_name'] ?>" />
		<input class="form-control" type="hidden" name="amazon_uid" value="<?php echo $amazonUser['amazon_uid']; ?>"  />
		<input class="form-control" type="hidden" name="merchant_id" value="<?=$amazonUser['merchant_id'] ?>" />
		<input class="form-control" type="hidden" name="access_key_id" value="<?=$amazonUser['access_key_id'] ?>" />
		<input class="form-control" type="hidden" name="secret_access_key" value="<?php echo $amazonUser['secret_access_key']; ?>"  />
		<input class="form-control" type="hidden" name="is_active" value="<?php echo $amazonUser['is_active']; ?>"  />
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
		
		    <div style="font-size: 19px;font-weight:bold"><label><?php echo TranslateHelper::t('amazon 账号信息设置');?></label></div>
				<div id="marketplace-options-div" style="margin-top: 10px;">
		            <div style="margin-bottom:10px"><label><?php echo TranslateHelper::t('请勾选国家分站(MarketplaceId). ');?> <a id="marketplaceid-country-tip-a" style="color:blue" href="#"><?php echo TranslateHelper::t('MarketplaceId跟国家的对应关系，点击这里');?></a></label></div>
		            <div class="checkbox">
		            <?php 
		               $countrycodeNameMap = SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP;
		               foreach($countrycodeNameMap as $code=>$name):
		            ?>
		            	<?php if(in_array("marketplace_".$code, $chosenCountryList)) :?>
		            	<label style="display: none;"><input class="form-control" style="display: inline;width: auto;height: auto;" id="marketplace_<?php echo $code; ?>" name="marketplace_<?php echo $code; ?>" type="checkbox" checked="checked" /><?php echo TranslateHelper::t($name); ?></label>                 
		            	<?php else :?>
		                <label><input class="form-control" style="display: inline;width: auto;height: auto;" id="marketplace_<?php echo $code; ?>" name="marketplace_<?php echo $code; ?>" type="checkbox"  /><?php echo TranslateHelper::t($name); ?></label>                 
	            		<?php endif;?>
		            <?php endforeach; ?>
		            </div>		            
		                        
		        </div>            
	        
	        <?php 
	        // @todo:解释文档页面多语言问题。
	        $currentLan = TranslateHelper::getCurrentLanguague();
	        $linkSuffix = "";
	        if("zh-cn" == $currentLan ){
				$linkSuffix = "";
			}
	        ?>
	        <!-- <div style="margin-top: 10px;margin-left:5px"><?php //echo TranslateHelper::t('帮助文档');?>:<a target="_blank" style="color:blue;" href="<?php //echo \Yii::getAlias('@web');?>/docs/amazonHelper<?= $linkSuffix ?>.html"><?php //echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?></a></div> -->
	        <div style="margin-top: 10px;margin-left:5px"><?php echo TranslateHelper::t('帮助文档');?>:
		        <a target="_blank" style="color:blue;" href="http://help.littleboss.com/word_list_247_516.html">
		        	<?php echo TranslateHelper::t('如果不知道怎么填写，请点击这里');?>
		        </a>
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
