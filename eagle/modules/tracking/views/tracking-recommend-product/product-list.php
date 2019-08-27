<?php 
use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\widgets\LinkPager;

$this->registerJs('$( "#product_startdate" ).datepicker({dateFormat:"yy-mm-dd"});' , \yii\web\View::POS_READY);
$this->registerJs('$( "#product_enddate" ).datepicker({dateFormat:"yy-mm-dd"});' , \yii\web\View::POS_READY);
?>
<style>
.recommend_product td{
    vertical-align:middle !important;
	font-size:12px;
}

</style>
<div class="tracking-index col2-layout">
	
	<?= $this->render('_menu') ?>
	<!-- 右侧table内容区域 -->	
	<div class="content-wrapper" >
	<form action="/tracking/tracking-recommend-product/product-list" method="post">
	   <div class="explain">
	       <p>请选择需要的时间段，建议参考结果，用于核实物流商的结账费用清单、不同物流商的服务质量</p>
	   </div>
	   <div>
	       <?php 
//     	   $aa = [
//     	       "ebay"=>"ebay",
//     	       "aliexpress"=>"aliexpress"
//     	   ];
    	   $allaccount = array();
    	   foreach ($accounts as $account){
    	       $allaccount[$account->platform_account_id] = $account->platform_account_id;
    	   }
	       ?>
	       <?=Html::dropDownList('seller_account',@$_REQUEST['seller_account'],$allaccount,['onchange'=>"doaction($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:125px;','prompt'=>'平台帐号'])?>
	       &nbsp;&nbsp;&nbsp;<input type="text" id="product_startdate" name="product_startdate" class="eagle-form-control" style="width:90px;" placeholder="起始时间" value="<?php echo $date['star'];?>">&nbsp;<span>-</span>
           <input type="text" id="product_enddate" name="product_enddate" class="eagle-form-control" style="width: 90px;" value="<?php echo $date['end']; ?>" placeholder="截止时间">
	       &nbsp;<input type="submit" id="time_search" value="搜索" class="btn btn-success btn-sm">
	   </div>
	   <table class="table recommend_product">
	   <thead>
	       <tr>
	           <th><input type="checkbox" id="chk_all" style="width:29px;"></th>
	           <th class="no-qtip-icon" style="text-align: left; width:62px;">图片</th>
	           <th class="th_orderid no-qtip-icon" style="text-align: left; width:240px;">Title</th>
	           <th style="text-align: left;">价格</th>
	           <th style="text-align: left;">站点</th>
	           <th style="text-align: left;">平台帐号</th>
	           <th style="text-align: left;">展示次数</th>
	           <th style="text-align: left;">点击次数</th>
	           <th style="text-align: left;">状态</th>
	           <th style="text-align: left;">操作</th> 
	       </tr>
	   </thead>
	   <tbody>
	       <?php $num=1; foreach ($products as $product):?>
	       <tr style="height:81px;" <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>     
    	       <td><input type="checkbox" id="chk_all"></td>
    	       <td><a href="<?php echo $product->product_url?>" target="_blank" ><img src="<?php echo $product->product_image_url; ?>" style="width: 60px; height: 60px;"></a></td>
    	       <td><a href="<?php echo $product->product_url?>" target="_blank" ><?php echo $product->product_name;?></a></td>
    	       <td><?php echo $product->product_price;?>&nbsp;<?php echo $product->product_price_currency;?></td>
    	       <td><?php echo $product->platform_site_id;?></td>
    	       <td  style="text-align: left;"><?php echo $product->platform;?></td>
    	       <td style="text-align: left; color:#ff9900; font-size:14px;font-weight:600;">
    	       <?php
    	           $view_count=null;
    	           $click_count=null;
    	           foreach ($counts as $count){
    	               if($count['product_id']==$product->id){
    	                   $view_count=$count['total_view_count'];
    	                   $click_count=$count['total_click_count'];
    	                   break;
    	               }
    	           } 
    	           echo $view_count==null?'0':$view_count;
    	       ?>
    	       </td>
    	       <td style="text-align: left;color:#00c453; font-size:14px;font-weight:600;">
    	       <?php echo $click_count==null?'0':$click_count;?>
    	       </td>
    	       <td><?php echo $product->is_on_sale==="Y" ? "在售":"下架";?></td>
    	       <td><a href="<?php echo $product->product_url?>" target="_blank" ><span class="egicon-eye"></span></a></td>
	       </tr>
	       <?php endforeach;?>
	   </tbody>
	   </table>
	 </form>
	   <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
            
        	</div>
	            <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
        </div>
	   
	</div> 
	
</div>
<script>
   function doaction(val){
	   $("form").submit();		   
	}

</script>