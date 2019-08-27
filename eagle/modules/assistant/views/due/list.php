<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/assistant.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs('jQuery(document).ready(function (){
	$("body")
	// datetimepicker
		.on("mouseenter", ".js_datetimepicker", function() {
		var $this = $(this);
		$this.datetimepicker({
			format: $this.data("picker-format")
		});
		})
		.on("mouseenter", "[type=date]", function() {
			var $input = $(this);
			if (!$input.hasH5("input", "type", "date")) {
				$input.datetimepicker({
					format: "yyyy-mm-dd"
				});
			}
		})
		.on("focus", "[dynamic-min],[dynamic-max]", function() {
			var $this = $(this),
				$form = $this.closest("form"),
				min = $this.attr("dynamic-min"),
				max = $this.attr("dynamic-max"),
				$min = $form.find("[name=" + min + "]"),
				$max = $form.find("[name=" + max + "]");

			if (min && $min.val()) {
				$this.attr("min", $min.val());
			}
			if (max && $max.val()) {
				$this.attr("max", $max.val());
			}
		})
	});', \yii\web\View::POS_READY);
// 催款记录

?>
<style>
	.left_menu a{
		color: #62778B;
	}
	.left_menu .down{
        cursor: pointer;
    }
</style>
<?php 
    $menu = [
        '催款设置' => [
            'icon' => 'iconfont icon-shezhi',
            'items'=> [
                '催款规则列表'=>[
                    'url'=> '/assistant/rule/list',
                ],
                '催款模板设置'=>[
                    'url'=>'/assistant/template/list',
                ]
            ],
        ],
        '催款记录' => [
            'icon' => 'iconfont icon-liebiao',
            'items'=> [
                '催款记录' =>[
                    'url'=> '/assistant/due/list',
                ],
            ],
        ],
        '统计信息' => [
            'icon' => 'lconfont icon-liebiao',
            'items' => [
                '店铺统计' => [
                    'url' => '/assistant/due/byshop',
                ],           
                '规则统计' => [
                    'url' => '/assistant/due/dueinfo',
                ]
            ],
        ],
    ];

    echo $this->render('//layouts/new/left_menu',[
            'menu' => $menu,
            'active' => '催款记录',
        ]);
?>
<div class="content-wrapper">
	<form action="" class="block">
		<div class="filter-bar">
			<div class="input-group iv-input">
				<select name="searchtype" class="iv-input" placeholder="查询条件">
					<option value="">查询条件</option>
					<option value="source_id" <?= @$_GET['searchtype'] == 'source_id'?'selected="selected"':''?> >订单编号</option>
					<option value="buyer" <?= @$_GET['searchtype'] == 'buyer'?'selected="selected"':''?> > 买家</option>
				</select> 
				<input type="text" name="searchtype_val" class="iv-input" value="<?= @$_GET['searchtype_val']?>" />
			</div>
			<div class="input-group iv-input">
				<select name="timetype" class="iv-input" placeholder="查询时间">
					<option value="">查询时间</option>
					<option value="order_time"><?= @$_GET['timetype'] == 'order_time'?'selected="selected"':''?>>下单时间</option>
					<option value="create_time" <?= @$_GET['timetype'] == 'pay_time' ? 'selected="selected"':'' ?>>付款时间</option>
				</select>
				<input class="iv-input" type="date" name="timetype_val1" value="<?= @$_GET['timetype_val1']?>" max="timetype_val2" /> -
				<input class="iv-input" type="date" name="timetype_val2" value="<?= @$_GET['timetype_val2']?>" min="timetype_val1" />
			</div>
			<input type="submit" value="搜索" class="iv-btn btn-search btn-spacing-middle">
			<input type="submit" value="重置" class="iv-btn btn-important btn-spacing-middle"> 	
		</div>
	    <table class="table table-hover table-striped table-bordered">
	        <tr class="list-firstTr">
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('订单编号') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<select name="shop_id" id="selectshop" onchange="this.form.submit();">
	            		<option value=""><?= TranslateHelper::t('店铺') ?></option>
	            		<?php 
	            		foreach($shops as $shop){
	            			echo "<option value='{$shop->dp_shop_id}' ".(@$_GET['shop_id']==$shop->dp_shop_id?'selected="selected"':'').">{$shop->dp_shop_id}</option>";
	            		}
	            		?>
	            	</select>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('买家') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('订单金额') ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?=$sort->link('order_time',['label'=>TranslateHelper::t('下单时间')]) ?>
	            </th>
	            <th class="text-nowrap col-lg-2">
	            	<?= TranslateHelper::t('催款时间') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<?= TranslateHelper::t('付款时间') ?>
	            </th>
	            <th class="text-nowrap col-lg-1">
	            	<select name="due_status" id="due_status" onchange="this.form.submit();">
	            		<option value="" <?= @!$_GET['due_status']?'selected="selected"':'' ?> ><?= TranslateHelper::t('付款状态') ?></option>
	            		<option value="1" <?= @$_GET['due_status']==1?'selected="selected"':'' ?> >未付款</option>
	            		<option value="2" <?= @$_GET['due_status']==2?'selected="selected"':'' ?> >已付款</option>
	            	</select>
	            </th>
		    </tr>
	        <?php foreach($dpInfo as $info):?>
	        <tr>
	            <td class="text-nowrap"><?= $info->source_id ?></td>
	            <td class="text-nowrap"><?= $info->shop_id ?></td>
	            <td class="text-nowrap"><?= $info->buyer ?></td>
	            <td class="text-nowrap"><?= $info->cost ?></td>
	            <td class="text-nowrap"><?= $info->order_time ?></td>
	            <td class="text-nowrap"><?= $info->create_time ?></td>
	            <td class="text-nowrap"><?= strtotime($info->pay_time)>0?$info->pay_time:'' ?></td>
	            <td class="text-nowrap"><?= $info->due_status==2?'已付款':'未付款'?></td>
	        </tr>
	        <?php endforeach;?>
	    </table>
	    <div id="pager-group">
		        <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages, 'pageSizeOptions'=>$pages->pageSizeLimit, 'class'=>'btn-group dropup']);?>
	        <div class="btn-group" style="width: 49.6%;text-align: right;">
	        	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
	    	</div>
	    </div>

	</form>
</div>
