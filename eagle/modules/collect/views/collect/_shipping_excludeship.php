<?php
use yii\helpers\Html;
use common\helpers\Helper_Array;
use eagle\models\EbayExcludeshippinglocation;
/**
 * @author fanjs
 * 用户物流设置模块屏蔽目的地页面
 */
?>
<strong><?php echo @implode(', ',@$data['shippingdetails']['ExcludeShipToLocation'])?></strong> 
<span class="wuliudeal" onclick="$('#exclude_all').toggle()">修改目的地</span><br>
<span id='changeexclude'></span>
<div id="exclude_all" style="display: none">
	<hr>
	<div>
        <input type="checkbox" class="excludeship" >常用 <a href="#fjs" onclick='$("#excludeshipnormal").slideToggle();'>展开/收起</a> <br>
        <div id="excludeshipnormal">
        <?php $excludeship=EbayExcludeshippinglocation::find()->where(array('region'=>array('Domestic Location','Additional Locations'),'siteid'=>$data['siteid']))->asArray()->all()?>
        <?php $excludeship=Helper_Array::toHashmap($excludeship,'location','description');?>
        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeship,['itemOptions'=>['class'=>'excludeship']]);?>
		<?php echo $htm;?>
        </div>
	</div><hr>
	<div>
		<input type="checkbox" class="excludeship" >世界 <a href="#fjs" onclick='$("#excludeshipworld").slideToggle();'>展开/收起</a> <br>
        <div id="excludeshipworld">
        <?php $excludeshipsj=EbayExcludeshippinglocation::find()->where(array('region'=>'Worldwide','siteid'=>$data['siteid']))->asarray()->all();?>
        <?php $excludeshipsj=Helper_Array::toHashmap($excludeshipsj,'location','description');?>
        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipsj,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
        </div>
	</div><hr>
	<div>
        <input type="checkbox" class="excludeship" >南美 <a href="#fjs" onclick=$('#excludeshipsouthamer').slideToggle();>展开/收起</a> <br>
        <div id="excludeshipsouthamer" >
        <?php $excludeshipsouthamer=EbayExcludeshippinglocation::find()->where(array('region'=>'South America','siteid'=>$data['siteid']))->asarray()->all();?>
        <?php $excludeshipsouthamer=Helper_Array::toHashmap($excludeshipsouthamer,'location','description');?>
        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipsouthamer,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
        </div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >东南亚 <a href="#fjs" onclick=$('#excludeshipsouthasia').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipsouthasia" >
       	<?php $excludeshipsouthasia=EbayExcludeshippinglocation::find()->where(array('region'=>'Southeast Asia','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipsouthasia=Helper_Array::toHashmap($excludeshipsouthasia,'location','description');?>
       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipsouthasia,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>	
      	</div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >大洋洲 <a href="#fjs" onclick=$('#excludeshipoceania').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipoceania" >
       	<?php $excludeshipoceania=EbayExcludeshippinglocation::find()->where(array('region'=>'Oceania','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipoceania=Helper_Array::toHashmap($excludeshipoceania,'location','description');?>
       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipoceania,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
       	</div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >北美 <a href="#fjs" onclick=$('#excludeshipnorthamer').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipnorthamer" >
       	<?php $excludeshipnorthamer=EbayExcludeshippinglocation::find()->where(array('region'=>'North America','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipnorthamer=Helper_Array::toHashmap($excludeshipnorthamer,'location','description');?>
       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipnorthamer,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
       	</div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >中东 <a href="#fjs" onclick=$('#excludeshipma').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipma" >
       	<?php $excludeshipma=EbayExcludeshippinglocation::find()->where(array('region'=>'Middle East','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipma=Helper_Array::toHashmap($excludeshipma,'location','description');?>
       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipma,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
       	</div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >欧洲 <a href="#fjs" onclick=$('#excludeshipeur').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipeur" >
       	<?php $excludeshipeur=EbayExcludeshippinglocation::find()->where(array('region'=>'Europe','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipeur=Helper_Array::toHashmap($excludeshipeur,'location','description');?>
       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipeur,['itemOptions'=>['class'=>'excludeship']])?>
		<?php echo $htm;?>
       	</div>
	</div><hr>
	<div>
       	<input type="checkbox" class="excludeship" >中美 <a href="#fjs" onclick=$('#excludeshipcaac').slideToggle();>展开/收起</a> <br>
       	<div id="excludeshipcaac" >
       	<?php $excludeshipcaac=EbayExcludeshippinglocation::find()->where(array('region'=>'Central America and Caribbean','siteid'=>$data['siteid']))->asarray()->all();?>
       	<?php $excludeshipcaac=Helper_Array::toHashmap($excludeshipcaac,'location','description');?>
       	<?php echo Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipcaac,['itemOptions'=>['class'=>'excludeship']])?>
       	</div>
	</div><hr>
	<div>
       <input type="checkbox" class="excludeship">亚洲 <a href="#fjs" onclick=$('#excludeshipasia').slideToggle();>展开/收起</a> <br>
       <div id="excludeshipasia" >
       <?php $excludeshipasia=EbayExcludeshippinglocation::find()->where(array('region'=>'Asia','siteid'=>$data['siteid']))->asarray()->all();?>
       <?php $excludeshipasia=Helper_Array::toHashmap($excludeshipasia,'location','description');?>
       <?php echo Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipasia,['itemOptions'=>['class'=>'excludeship']])?>
       </div>
	</div><hr>
	<div>
       <input type="checkbox" class="excludeship" >非洲 <a href="#fjs" onclick=$('#excludeshipafr').slideToggle();>展开/收起</a> <br>
       <div id="excludeshipafr" >
       <?php $excludeshipafr=EbayExcludeshippinglocation::find()->where(array('region'=>'Africa','siteid'=>$data['siteid']))->asarray()->all();?>
       <?php $excludeshipafr=Helper_Array::toHashmap($excludeshipafr,'location','description');?>
       <?=Html::checkboxList('shippingdetails[ExcludeShipToLocation]', @$data['shippingdetails']['ExcludeShipToLocation'], $excludeshipafr,['itemOptions'=>['class'=>'excludeship']])?>
       </div>
    </div> 
     &nbsp;&nbsp;
    <input class="iv-btn btn-search" type="button" value="全选" onclick="$('.excludeship').prop('checked',true);"> &nbsp;&nbsp;  <input class="iv-btn btn-search" type="button" value="全不选" onclick="$('.excludeship').removeAttr('checked');">
</div>
<script>

</script>