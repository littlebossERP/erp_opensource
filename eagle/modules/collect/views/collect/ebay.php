<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;

$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
$this->registerJSFile(\Yii::getAlias('@web').'/js/project/collect/collect_ebay.js');
?>
<style type="text/css">
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.mutisearch{
	margin:10px 0px;
}
.data{
	clear:both;
}
table img{
	width:60px;
	height:60px;
	max-height:60px;
}
.father{
	position: relative;
	padding:0px;
}
td .father{
	padding:0px;
}
td .platform{
	background-color:rgb(220,239,245);
	position: absolute; bottom: 0; 
	text-align:center;
	width:100%;
	margin:0px 0px 0px -10px;
}
.doaction{
	border-width:0px;
	padding:3px;
	margin:0px 10px 10px 0px;
	color:rgb(102,102,102);
}
.do{
	color:#ffffff;
	background:rgb(3,206,89) !important;
	border-radius:3px;
}
.delete{
	background-color:rgb(153,153,153);
	margin:10px 0px;
	height:25px;
	padding:0px 10px;
	color:#ffffff;
}
</style>
<?php 
	$action=[
		'mutiedit'=>'批量修改',
		'delete'=>'批量删除',
		'movetowait'=>'批量移入待发布',
		'additem'=>'发布',
		//'dignshi'=>'定时发布'
	];
	$action_one=[
		'edit'=>'修改',
		'movetowait'=>'移入待发布',
		'additem'=>'发布',
		//'dignshi'=>'定时发布'
	];
?>
<?=$this->render('_ebay_leftmenu',['active'=>'eBay草稿箱']);?>
<?php 
$site_tmp = Helper_Siteinfo::getEbaySiteIdList('no','en');
?>
<div class="mainbody">
	<div class="data">
		<div class="mianbaoxie">
			<span></span>eBay草稿箱
		</div>
		<form action="" method="post">
		<div class="mutisearch">
		<?=Html::dropDownList('site',@$_REQUEST['site'],$site_tmp,['prompt'=>'eBay站点','class'=>"iv-input"])?>
		<?=Html::dropDownList('listingtype',@$_REQUEST['listingtype'],['Chinese'=>'拍卖','FixedPriceItem'=>'一口价'],['prompt'=>'刊登类型','class'=>"iv-input"])?>
		<div class="input-group iv-input">
			<?=Html::dropDownList('search_name',@$_REQUEST['search_name'],['itemtitle'=>'标题','sku'=>'SKU'],['class'=>"iv-input"])?>
			<input type="text" name="search_key" class="select_status iv-input" value="<?php if(isset($_REQUEST['search_key'])):?><?=$_REQUEST['search_key']?><?php endif;?>"> 
			<button type="submit" class="iv-btn btn-search">
				<span class="iconfont icon-sousuo"></span>	
			</button>
		</div>
		</div>
		</form>
        <div class="table-action">
			<div class="pull-left">
			<span class="iconfont icon-fanxuan"><?=Html::dropDownList('do','',$action,['onchange'=>"doaction($(this).val())",'class'=>'btn btn-default doaction','prompt'=>'批量操作','onmousedown'=>'$(this).val("")']);?></span>
			</div>
		</div>
		
		<table class="iv-table">
		    <thead>
		        <tr>
		            <th style="width:20px;">
		                <input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		            </th>
		            <th style="width:60px;">图片</th>
		            <th style="width:15%;text-align: left;">标题</th>
		            <th style="width:4%;">站点</th>
		            <th style="width:80px;">SKU</th>
		            <th style="width:80px;">价格</th>
		            <th style="width:80px;">库存</th>
		            <th style="width:80px;">天数</th>
		            <th style="width:100px;">创建时间</th>
		            <th style="width:100px;">操作</th>
		        </tr>
		    </thead>
		    <?php if (count($collects)):?>
		    <tbody>
		    	<?php foreach ($collects as $collect):?>
		    	<tr>
		    		<td>
		    			<input type="checkbox" class="ck" name="collect[]" value="<?=$collect->mubanid?>">
		    		</td>
		    		<td>
		    			<img src="<?=!empty($collect->mainimg)&&strlen($collect->mainimg)?$collect->mainimg:'http://v2-test.littleboss.cn/images/batchImagesUploader/no-img.png'?>" width="80px" height="80px">
		    		</td>
		    		<td>
		    			<?=$collect->itemtitle?>
		    		</td>
		    		<td>
		    			<?php 
		    				if (isset($collect->siteid)){
								echo $site_tmp[$collect->siteid];
							}
		    			?>
		    		</td>
		    		<td>
		    			<?=$collect->sku?>
		    		</td>
		    		<td>
		    			<?=$collect->startprice?>
		    		</td>
		    		<td>
		    			<?=$collect->quantity?>
		    		</td>
		    		<td>
		    			<?=$collect->listingduration?>
		    		</td>
		    		<td>
		    			<?=date('Y-m-d H:i:s',$collect->createtime)?>
		    		</td>
		    		<td>
		    		<?=Html::dropDownList('do','',$action_one,['onchange'=>"doaction($(this).val(),'".$collect->mubanid."');",'prompt'=>'操作','onmousedown'=>'$(this).val("")']);?>
					<br/>
					<button class="btn btn-default delete" onclick="delone('<?=$collect->mubanid?>')">删除</button>
		    		</td>
		    	</tr>
		    	<?php endforeach;?>
		    </tbody>
		    <?php endif;?>
		    <!--分页---->
		    <?php if(! empty($pages)):?>
        <tfoot>
            <tr>
                <td colspan = "10">
                    <?php 
                        $pageBar = new \render\layout\Pagebar();
                        $pageBar->page = $pages;
                        echo $pageBar;
                    ?>
                </td>
            </tr>
        </tfoot>
    <?php endif;?>
			<!--分页---->
		</table>
	</div>
</div>

