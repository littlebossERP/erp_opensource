<?php
use yii\helpers\Url;
use yii\helpers\Html;

$this->registerJSFile(\Yii::getAlias('@web').'/js/project/collect/collect_index.js');
$this->registerCSSFile(\Yii::getAlias('@web').'/css/listing/wish_list.css');
?>
<style type="text/css">
.liucheng>p{
	font-weight:bold;
}
.liucheng img{
	float:left;
	margin:10px 0px;
}
.liucheng>.xiaotieshi{
	float:left;
	width:400px;
	height:85px;
	background:rgb(243,243,243);
	margin:13px 0px 0px 30px;
	border-radius:5px;
}
.tupian{
	position: relative;
	width:40px;
}
.tupian>img{
	position: absolute; left: -6px; 
}
.miaoshu{
	float:right;
	width:360px;
}
.miaoshu>p{
	font-size:14px;
	margin:8px 0px;
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
	$platform=[
		'wish'=>'认领到Wish',
		'ebay'=>'认领到eBay',
		'lazada'=>'认领到Lazada',
		'ensogo'=>'认领到Ensogo'
	];
?>
<?=$this->render('_leftmenu_all',['active'=>'采集箱']);?>
<div class="mainbody">
	<div class="liucheng">
		<p>商品采集流程</p>
		<img src="/images/collect/liucheng.png">
		<div class="xiaotieshi">
			<div class="tupian">
				<img src="/images/collect/xiaotieshi.png">
			</div>
			<div class="miaoshu">
				<p>目前支持插件(插件下载)单品采集</p>
				<p>支持的平台:淘宝、天猫、1688、速卖通</p>
				<p>可发布到:Wish、eBay、Lazada、Ensogo。不断更新中</p>
			</div>
		</div>
	</div>
	
	<div class="data">
	 	<ul class="main-tab tab-xs">
            <li class="check_status <?php if (!isset($_REQUEST['active_type'])|| isset($_REQUEST['active_type'])&&$_REQUEST['active_type']!='N'&&$_REQUEST['active_type']!='Y'){echo 'active';}?>"><a href="<?=Url::to(['/collect/collect/index'])?>">全部(<?=$count['all']?>)</a></li>
            <li class="check_status <?php if (isset($_REQUEST['active_type'])&&$_REQUEST['active_type']=='N'){echo 'active';}?>"><a href="<?=Url::to(['/collect/collect/index','active_type'=>'N'])?>">未认领(<?=$count['weirenling']?>)</a></li>
            <li class="check_status <?php if (isset($_REQUEST['active_type'])&&$_REQUEST['active_type']=='Y'){echo 'active';}?>"><a href="<?=Url::to(['/collect/collect/index','active_type'=>'Y'])?>">已认领(<?=$count['renling']?>)</a></li>
        </ul>
        
        <div class="table-action">
			<div class="pull-left">
			<span class="iconfont icon-fanxuan"></span><?=Html::dropDownList('do','',$platform,['onchange'=>"renling($(this).val())",'class'=>'btn btn-default doaction','prompt'=>'批量认领','onmousedown'=>'$(this).val("")']);?>
			<button type="button" class='btn btn-default doaction' onclick="delall();"><span class="iconfont icon-shanchu"></span>删除</button>
			</div>
		</div>
		
		<table class="iv-table">
		    <thead>
		        <tr>
		            <th style="width:20px;">
		                <input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		            </th>
		            <th style="width:100px;">图片</th>
		            <th style="width:20%;text-align: left;">标题</th>
		            <th style="width:38%;">描述</th>
		            <th style="width:80px;">价格</th>
		            <?php if (!isset($_REQUEST['active_type'])||(isset($_REQUEST['active_type'])&&$_REQUEST['active_type']!='N')):?>
		            <th style="width:100px;">认领记录</th>
		            <?php endif;?>
		            <th style="width:100px;">创建时间</th>
		            <th style="width:100px;">操作</th>
		        </tr>
		    </thead>
		    <?php if (count($collects)):?>
		    <tbody>
		    	<?php foreach ($collects as $collect):?>
		    	<tr>
		    		<td>
		    			<input type="checkbox" class="ck" name="collect[]" value="<?=$collect->id?>">
		    		</td>
		    		<td class="father">
		    			<img src="<?=!empty($collect->mainimg)&&strlen($collect->mainimg)?$collect->mainimg:'http://v2-test.littleboss.cn/images/batchImagesUploader/no-img.png'?>"><br/>
		    			<div class="platform"><?=$collect->platform?></div>
		    		</td>
		    		<td>
		    			<?=$collect->title?>
		    		</td>
		    		<td>
		    			<?php
							$items = json_decode($collect->description,true);
							echo $items['itemspecifics'];
						?>
		    		</td>
		    		<td>
		    			<?=$collect->price?>
		    		</td>
		    		<?php if (!isset($_REQUEST['active_type'])||(isset($_REQUEST['active_type'])&&$_REQUEST['active_type']!='N')):?>
		    		<td>
		    			<?php if ($collect->wish ==1 || $collect->ebay ==1 || $collect->lazada ==1 || $collect->ensogo ==1 ):?>
		    			已认领到<br>
		    			<?php endif;?>
		    			<?php if ($collect->wish ==1):?>
		    			Wish<br/>
		    			<?php endif;?>
		    			<?php if ($collect->ebay ==1):?>
		    			eBay<br/>
		    			<?php endif;?>
		    			<?php if ($collect->lazada ==1):?>
		    			Lazada<br/>
		    			<?php endif;?>
		    			<?php if ($collect->ensogo ==1):?>
		    			Ensogo<br/>
		    			<?php endif;?>
		    		</td>
		    		<?php endif;?>
		    		<td>
		    			<?=date('Y-m-d H:i:s',$collect->createtime)?>
		    		</td>
		    		<td>
					<?=Html::dropDownList('do','',$platform,['onchange'=>"renlingone($(this).val(),'".$collect->id."');",'class'=>'do','prompt'=>'认领','onmousedown'=>'$(this).val("")']);?>
					<br/>
					<button class="btn btn-default delete" onclick="delone('<?=$collect->id?>')">删除</button>
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

