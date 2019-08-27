
<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\helpers\AppHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/app/appList.js", ['depends' => ['yii\web\JqueryAsset']]);

$imageBasePath=Yii::getAlias('@web')."/images";

$this->registerJs('appManager.list.categoryKeyMapJson='. json_encode($categoryAppkeysMap).';', \yii\web\View::POS_END);

?>
<style>
.kf5_selection .selection_widget {
width: 100%;
}

.kf5_selection .selection_widget dl {
float: left;
display: inline-block;
width: 50%;
position: relative;
height: 150px;
}
.kf5_selection .selection_widget dl dt {
position: absolute;
top: 10px;
left: 10px;
}
.kf5_selection .selection_widget dl dt img {
width: 120px;
height: 120px;
border: 1px solid #ececec;
border-radius: 10px;
}
.kf5_selection .selection_widget dl dd {
margin: 15px 40px 10px 150px;
}
.kf5_selection .selection_widget dl dd ul {
font-size: 13px;
color: #777;
margin-top: 10px;
line-height: 20px;
}
</style>

<div class="row">

	<div class="filter_wrap col-md-2" sytle="">
	
	<!-- 列出所有的app filter  -->
	  <div>
		<ul class="nav nav-pills nav-stacked filter-ul">
		  <li role="presentation" class="active" categoryid="-1"><a href="#"><?=TranslateHelper::t("所有应用") ?></a></li>
		</ul>	  
	  </div>
	  
	  <div class="clear-div" style="border-bottom:1px solid black"></div>
	  	
	<?php
	    $appCategoryList=$appCategoryMap["platform"];	    
	?>
	<!-- 平台相关的app filter  -->
	  <div style="padding:5px 5px 5px 0px">
		<ul class="nav nav-pills nav-stacked filter-ul">
		<?php 
		foreach($appCategoryList as $appCategory){
       ?>
           <li role="presentation" categoryid="<?=$appCategory['category_id'] ?>" ><a href="#">
            <?=$appCategory['name'] ?> <span class="badge"><?=$appCategory['count'] ?></span></a></li>        
        <?php  } ?>
		</ul>
	  </div>
	  
	  <div class="clear-div" style="border-bottom:1px solid black"></div>
	  	
	<?php
	    $appCategoryList=$appCategoryMap["bussiness"];	    
	?>
	<!-- 业务相关的app filter  -->
	  <div style="padding:5px 5px 5px 0px">
		<ul class="nav nav-pills nav-stacked filter-ul">
		<?php 
		foreach($appCategoryList as $appCategory){

        ?>
           <li role="presentation" categoryid="<?=$appCategory['category_id'] ?>" ><a href="#">
            <?=$appCategory['name'] ?> <span class="badge"><?=$appCategory['count'] ?></span></a></li>        
        <?php  } ?>
		</ul>
	  </div>
	</div>
	
	
	
	
	
	
	<!-- 所有app的列表  -->
	<div class="box_wrap col-md-10 app-list-div" style="padding: 5px 0;font-size: 14px;">
		<div class="box_head">
		   <div class="box_title" style="color: #555;font-size: 16px;line-height: 30px;overflow: hidden;float: left;"><?=TranslateHelper::t('选择模块应用安装') ?></div>
		   <?php 
		   $appKeyList = AppHelper::getActiveAppKeyList();
		   if(empty($appKeyList)):?>
		   <div class="box_title" style="color: #555;font-size: 16px;line-height: 30px;overflow: hidden;float: right;"><a href="#" onclick="appManager.list.openUserGuide()" ><?=TranslateHelper::t('使用向导') ?></a></div>
			<?php endif;?>
		</div>
		<div class="kf5_selection wp">
		<div class="selection_widget app-list-di-wrap clearfix" style="display: block;clear:both">
		<?php foreach($allAppList as $appInfo):  ?>
		<dl appkey="<?=$appInfo["key"] ?>"  appname="<?=TranslateHelper::t($appInfo["name"]) ?>" >
			<dt>
			<a href="#" target="_self" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>" >
			<img src="<?php echo $imageBasePath."/app/".$appInfo["key"].".jpg"; ?>"></a></dt>
			<dd><h4><a href="#" target="_self" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>" ><?=TranslateHelper::t($appInfo["name"]) ?></a>
			</h4>
			<ul><?=$appInfo["description"] ?></ul>
			<button class="btn btn-default disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('免费') ?></button>
			<?php if ($appInfo["installed"]=='N' or $appInfo["is_active"]=='N' ) { ?>
			     <button class="btn btn-default disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('未启用') ?></button>
			     <button class="btn btn-default app-detail-btn" type="button" style="margin-top:10px"><?=TranslateHelper::t('详情') ?></button>
			     <button  type="button"  class="btn btn-info app-install-btn" style="margin-top:10px"><?=TranslateHelper::t('启用app') ?></button>
			<?php } else { ?>
			     <button class="btn btn-default disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('已启用') ?></button>
			     <button class="btn btn-default app-detail-btn" type="button" style="margin-top:10px"><?=TranslateHelper::t('详情') ?></button>
			     <button  type="button"  class="btn btn-danger app-list-unactivate-btn" style="margin-top:10px"><?=TranslateHelper::t('停用app') ?></button>
			<?php } ?>
			
			
	
			</dd>
		</dl>
		<?php endforeach; ?>
		
		</div>
		</div>
	</div>

</div>



