<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
use common\helpers\Helper_Emoji;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerCssFile(\Yii::getAlias('@web')."/js/lib/intro/introjs.min.css");
$this->registerCssFile(\Yii::getAlias('@web')."/css/emoji.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/intro/intro.min.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/comment/comment/commentindex.js", ['depends' => ['yii\web\JqueryAsset']]);

?>
<style>
	.comment-top-ul {
		list-style-type: none;
		height:50px;
		margin-bottom:0px;
		padding-left:15px;
	}
	.comment-top-ul li {
		float:left;
		font-size: 12px;
		height:40px;
		line-height: 40px;
		margin-right:10px;
		color:#666;
	}
	.icon-button {
		display:inline-block;
		height:20px;
		width:25px;
		cursor: pointer;
		font-size: 13px;
		font-weight:bold;
	}
	.comment-table-content-tr {
		font-size:12px;
		color:#666;
		height:50px;
		border-bottom:1px solid #ccc;
	}
	.comment-table-content-tr:hover .icon-button {
		color :#02BDF0;
	}
	.has-dispute {color:#FF9801;font-weight:bold;}
	.has-no-dispute {color:#2DCC6F;font-weight:bold;}
	.comment-add-title {height:30px;font:bold 15px/30px 微软雅黑}
	.comment-add-content-div {clear:both;height:50px;border-bottom: 1px solid #ccc;margin:10px 0px;}
	.comment-add-content-div label {font-weight: normal}
	.left_menu a{
		color: #62778B;
	}
	.left_menu .down{
		cursor: pointer;
	}
	.btn-fun{
		background-color: #01BDF0 !important;
		color: white !important;
	}
</style>
<?php 
	$menu = [
		'自动好评' =>[
			'icon' => 'iconfont icon-stroe', 
			'items'=>[
				'自动好评' => [
					'url' => '/comment/comment/rule',
				],
				'好评模板' =>[
					'url' => '/comment/comment/template',
				],
			],
		],
		'手动批量好评' =>[
			'icon' => 'iconfont icon-shezhi',
			'items'=> [
				'手动批量好评'=>[
					'url' => '/comment/comment/index',
				],
			],
		],
		'好评记录' =>[
			'icon' => 'iconfont icon-stroe',
			'items'=>[
				'好评记录'=>[
					'url' => '/comment/comment/log',
				],
			]
		],
	];
	echo $this->render('//layouts/new/left_menu',[
			'menu'=>$menu,
			'active'=>'手动批量好评',
		]);
?>
<div>
	<div class="panel">
		<form action="" name="search_form1" class="block">
		<div class="filter-bar">
		<!-- 	<select name="operate_type" class="iv-input" onchange="document.search_form1.submit()"  placeholder="待好评">
				<option value="0">待好评</option>
				<option value="2">不处理</option>
			</select> -->
			<?php 
				$aliexpressusers_arr = array_map(function($user){
            	    return $user->sellerloginid;
        		},$aliexpressusers);
        	?>
			<select name="selleruser" class="iv-input" placeholder="全部速卖通店铺" onchange="document.search_form1.submit()">
				<option value="0">全部速卖通店铺</option>
				<?php foreach($aliexpressusers_arr as $val): ?>
						<option value="<?=$val?>" <?php if($val == $selleruser): ?>selected = "selected" <?php endif;?>><?=$val?></option>
				<?php endforeach;?>
			</select>
			<label style="margin-left:10px;"><?= TranslateHelper::t('下单日期') ?></label>
			<input class="iv-input" type="date" name="stime" placeholder="开始时间" max="etime" value="<?= \Yii::$app->request->get('stime')?>"/> -
			<input class="iv-input" type="date" name="etime" placeholder="结束时间" min="stime" value="<?= \Yii::$app->request->get('etime')?>" />

			<div class="input-group iv-input">
				 <select name="keys" class="iv-input">
					<option	value="comment_order_id">小老板订单号</option>
					<option	value="comment_source_id">速卖通订单号</option>
					<option value="comment_sku">SKU</option>
					<option value="comment_buyerid">买家账号</option>
					<option value="comment_buyername">买家姓名</option>
				</select>
				<input type="text" id="num" class="iv-input" name="searchval" placeholder="请填写id号">
				<button type="submit" href="#" onclick="document.search_form1.submit()" class="iv-btn btn-search">
					<span class="iconfont icon-sousuo"></span>
				</button> 
			</div>
		</div>
		</form>
		<?php if(!isset($_GET['operate_type']) || $_GET['operate_type'] === '0'): ?>
			<div class="table-action clearfix">
				<div class="pull-left">
					<a href="#" id="listcommentsubmit" data-toggle="modal" data-target="#comment_list" class="iv-btn btn-fun"><span class="iconfont icon-pingjia" style="font-size:12px;"></span> 批量好评</a>
					<!-- <a href="#" id="listignore-button"  class="iv-btn btn-fun" style="margin-right:0"><span class="glyphicon glyphicon-hourglass"></span> 不予处理</a><span style="font-size:12px;" qtipkey="comment_no_deal_tip"></span> -->
				</div>
			</div>
		<?php endif ?>

		<div class="iv-alert alert-remind">
			速卖通接口变更导致评价出现问题，我们正在加紧修复
		</div>

	    <table class=" table-striped table-nobordered">
	    	<thead>
	        <tr class="list-firstTr">
	        	<th style="width:20px">
	        		<input type="checkbox" data-check-all="order_info" />
	        	</th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('平台订单号') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('速卖通订单号') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	
	        <!--     	<?php $aliexpressusers_arr = array_map(function($user){
	            	    return $user->sellerloginid;
	            	},$aliexpressusers);
	            	?>
	            	<?= HtmlHelper::dropdownlistSearchButton('selleruser',$aliexpressusers_arr,TranslateHelper::t('店铺名称'))?>-->
	            	<?= TranslateHelper::t('店铺名称');?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?=TranslateHelper::t('买家帐号') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?=TranslateHelper::t('买家姓名') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?=TranslateHelper::t('商品sku') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?=TranslateHelper::t('订单金额') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?=$sort->link('order_source_create_time',['label' =>TranslateHelper::t('下单时间')]) ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?=$sort->link('issuestatus',['label'=>TranslateHelper::t('有无纠纷')]) ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('操作') ?>
	            	<!-- <span qtipkey="comment_operation_tip"></span> -->
	            </th>
		    </tr>
		    </thead>
		    <tbody>
	        <?php if(count($orders)):foreach($orders as $order):?>
	        <tr class="comment-table-content-tr">
	        	<td><input type="checkbox" class="order_checkbox" value="<?=$order->order_source_order_id?>" littlebossid="<?=intval($order->order_id)?>" data-check="order_info" /></td>
	        	<td style="font-weight:bold"><?= $order->order_id ?></td>
	        	<td><?= $order->order_source_order_id ?></td>
	        	<td style="font-weight:bold"><?= $order->selleruserid ?></td>
	        	<td><?= $order->source_buyer_user_id ?></td>
	        	<td><?= $order->consignee ?></td>
	        	<td><?php
	        			if(isset($order->items) && count($order->items)):
	        			foreach($order->items as $item){
	        				if(strlen($item->sku))
	        					echo $item->sku.' <b>X'.$item->quantity.'</b><br/>';
	        			}
	        			endif;
	        		 ?></td>
	        	<td><?= $order->subtotal.'&nbsp;'.$order->currency ?></td>
				<td><?= date('Y-m-d H:i:s',$order->order_source_create_time) ?></td>
				<td><?= $order->issuestatus != 'NO_ISSUE' && !empty($order->issuestatus)?'<span class="has-dispute">有</span>':'<span class="has-no-dispute">无</span>' ?></td>
				<td>
					<?php if(!isset($_GET['operate_type']) || $_GET['operate_type'] == 0):?>
					<span class="glyphicon glyphicon-heart-empty icon-button" title="好评" data-toggle="modal" data-target="#comment_list" onclick="commentsubmit(<?=$order->order_source_order_id?>)"></span>
					<!-- <span class="glyphicon glyphicon-hourglass icon-button" title="不予处理" onclick="commentignore(<?=intval($order->order_id)?>)"></span> -->
					<?php endif;?>
				</td>
	        </tr>
	        <?php endforeach;else:?>
	        <tr>
	        	<td colspan="11">该条件下无订单.</td>
	        </tr>
	        <?php endif;?>
	        </tbody>
	    </table>
		<?= HtmlHelper::Pagination($pages) ?>
	</div>
</div>

<!-- Modal -->
<div class="modal-v2 modal fade" id="comment_list" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">好评留言列表</h4>
      </div>
      <div class="modal-body">
		  <form action="">
			  <div class="comment-add-title">订单号</div>
			  <textarea name="ids" id="comment-add-textarea" cols="75" rows="3" style="margin-bottom:20px;" readonly></textarea>
			  <div class="comment-add-title">选择好评模版</div>
			  <div style="height:300px;width:98%;overflow-y: auto;">
				  <!--        遍历好评模版-->
				  <?php
				  if(count($comment_templates)):foreach($comment_templates as $k=> $t):
					  ?>
					  <div class="comment-add-content-div">
						  <div style="height:40px;width:10%;float:left;padding-left:20px;"><input type="radio" name="template_id" value="<?=$t->id ?>" id="comment-add-radio<?=$t->id?>" <?=$k===0?'checked':''?>></div>
						  <div style="width:88%;height: 40px;overflow: hidden;float:left">
							  <label for="comment-add-radio<?=$t->id?>"><?= $t->content?></label>
						  </div>
					  </div>
					  <?php
				  endforeach;else:echo '请先添加模版!';endif;
				  ?>

			  </div>
		  </form>
      </div>
		<div class="modal-footer">
			<button type="button" class="btn btn-success" id="commentsubmit">发送好评</button>
			<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
		</div>
    </div>
  </div>
</div>