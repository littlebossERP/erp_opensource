<?php
use yii\helpers\Html;
use yii\jui\DatePicker;

?>

		<form id="form1" action="" metdod="post">
		<table class='table table-bordered table-condensed'>
		
		<tr class="info">
			<th class="text-right">
				<select style="width: 100px" id="keys" name="keys" >
				  <option>订单号</option>
				  <option>平台订单号</option>
				  <option>SKU</option>
				  <option>产品ID</option>
				  <option>物流号</option>
				</select>
			</th>
			<td>
				<?=Html::input('','searchval','',['id'=>'num','style'=>'width:150px;'])?>
			</td>
			
			<th class="text-right">状态</th>	
			<td>
			<?=Html::dropDownList('goodstype','',['1'=>'已付款'],['prompt'=>'','style'=>'width:150px;'])?>
			</td>
			<th class="text-right">
				平台状态
			</th>
			<td>
				<?=Html::dropDownList('orderlimit','',['50'=>'等待买家付款'],['prompt'=>'','id'=>'orderlimit','style'=>'width:150px;'])?>
			</td>
			<th class="text-right">
				每页数量
			</th>
			<td>
				<?=Html::dropDownList('orderlimit','',['50'=>'每页50条','100'=>'每页100条','200'=>'每页200条'],['id'=>'orderlimit','style'=>'width:150px;'])?>
			</td>
			</tr>
		<tr class="info">
			<th class="text-right">
				<?=Html::dropDownList('timetype','',['soldtime'=>'下单日期'],['style'=>'width:100px;'])?>
			</th>
			<td colspan="3"  >
				<?=DatePicker::widget(['name'  => 'timevalue','value'  => '','dateFormat' => 'yyyy-MM-dd']);?>~
				<?=DatePicker::widget(['name'  => 'timevalue','value'  => '','dateFormat' => 'yyyy-MM-dd']);?>
			</td>
			<td colspan="4" class="text-right">
				<?=Html::button('搜索',['class'=>"btn btn-primary btn-xs",'id'=>'search'])?>
				<?=Html::button('重置',['class'=>"btn btn-primary btn-xs",'id'=>'clear'])?>
				<?=Html::button('更多搜索',['class'=>"btn btn-primary btn-xs",'id'=>'clear'])?>
			</td>
		</tr>
		<tr class="info">
			<th class="text-right">
				账号
			</th>
			<td colspan="7">
				<?=Html::checkboxList('selleruserid','',['1'=>'速卖通账号1','2'=>'速卖通账号2','3'=>'速卖通账号3'])?>
			</td>
			</tr>
		<tr class="info">
			<th class="text-right">
				仓库
			</th>
			<td colspan="7">
				<?=Html::checkboxList('selleruserid','',['1'=>'上海仓','2'=>'深圳成','3'=>'美国仓'])?>
			</td>
			</tr>
		<tr class="info">
			<th class="text-right">
				物流商
			</th>
			<td colspan="7">
				<?=Html::checkboxList('selleruserid','',['1'=>'4px','2'=>'出口易','3'=>'万邑通','4'=>'顺丰'])?>
			</td>
			</tr>
		<tr class="info">
			<th class="text-right">
				运输服务
			</th>
			<td colspan="7">
				<?=Html::checkboxList('selleruserid','',['1'=>'中邮小包','2'=>'E邮宝','3'=>'新加坡小包'])?>
			</td>
			</tr>
		<tr class="info">
			<th class="text-right">
				标签
			</th>
			<td colspan="7">
				<?=Html::checkboxList('selleruserid','',['1'=>'SKU不存在','2'=>'有留言','3'=>'有备注','4'=>'有纠纷','5'=>'等待退款'])?>
			</td>
		</tr>
		</table>
		</form>
	<form name="a" id="a" action="" method="post">
	<?php $doarr=[
		''=>'批量操作',
	];
	?>
	<?=Html::dropDownList('do','',$doarr,[]);?> 
	<?=Html::dropDownList('do','',['申请物流号'],[]);?> 
	<br/><br/>
		<table class="table table-condensed table-bordered" style="font-size:12;">
		<tr class="info">
			<th><input type="checkbox" >订单号</th>
			<th class="text-center text-nowrap">操作</th>
			<th class="text-center text-nowrap">标签</th>
			<th class="text-center text-nowrap">状态</th>
			<th class="text-center text-nowrap">运输服务</th>
			<th class="text-center text-nowrap">平台订单号</th>
			<th class="text-center text-nowrap">账号</th>
			<th class="text-center text-nowrap">仓库</th>
			<th class="text-center text-nowrap">平台状态</th>
			<th class="text-center text-nowrap">收货信息</th>
			<th class="text-center text-nowrap">物流号</th>
		</tr>
		<tr class="info">
			<th colspan="1" class="text-center text-nowrap">商品图片</th>
			<th colspan="1" class="text-center text-nowrap">SKU</th>
			<th colspan="1" class="text-center text-nowrap">单价</th>
			<th colspan="2" class="text-center text-nowrap">数量/单位</th>
			<th colspan="1" class="text-center text-nowrap">平台订单号</th>
			<th colspan="4" class="text-center text-nowrap">商品信息</th>
			<th colspan="1" class="text-center text-nowrap">产品属性</th>
		</tr>
		<tr class="warning">
			<td><input type="checkbox"><label>100000</label></td>
			<td class="text-nowrap"><a href="http://www.baidu.com">修改</a></td>
			<td class="text-nowrap"><?=Html::button('SKU不存在',['class'=>"btn btn-danger btn-xs"])?><br><?=Html::button('有留言',['class'=>"btn btn-danger btn-xs"])?></td>
			<td class="text-nowrap">已付款</td>
			<td class="text-nowrap">E邮宝</td>
			<td class="text-nowrap">66226622574447</td>
			<td class="text-nowrap">cn1452368789</td>
			<td class="text-nowrap">上海仓</td>
			<td class="text-nowrap">等待您发货</td>
			<td class="text-nowrap">
			收件人 :Tehard Noemie<br>
			地址:La pineliere<br>
			  La Guerche de Bretagne, Bretagne, France<br>
			邮编:35130<br>
			手机:<br>
			电话:33-6-49234205<br>
			传真:<br>
			</td>
			<td>LK34973475999</td>
		</tr>
		<tr class="warning">
			<td colspan="1" class="text-center"><img alt="" src="http://g03.a.alicdn.com/kf/UT8kOfVXe8XXXagOFbX8.jpg_50x50.jpg" width='60px'></td>
			<td colspan="1" class="text-center text-nowrap">sku001</td>
			<td colspan="1" class="text-center">$10.60</td>
			<td colspan="2" class="text-center">1/个</td>
			<td colspan="1" class="text-center">66226622574447</td>
			<td colspan="4" class="text-center"><a href="http://www.baidu.com">For soft 3d iPad mini case Wireless Bluetooth Keyboard PU Leather Stand Case Cover For iPad Mini 2 Free Shipping</a></td>
			<td colspan="1" class="text-center">color：blue</td>
		</tr>
		<tr class="warning">
			<td colspan="1" class="text-center"><img alt="" src="http://g02.a.alicdn.com/kf/UT8t4vVXgJXXXagOFbXB.jpg_50x50.jpg" width='60px'></td>
			<td colspan="1" class="text-center">sku002</td>
			<td colspan="1" class="text-center">$7.00</td>
			<td colspan="2" class="text-center">1/个</td>
			<td colspan="1" class="text-center">66226622574447</td>
			<td colspan="4" class="text-center"><a href="http://www.baidu.com">For soft 3d iPad mini case Wireless Bluetooth Keyboard PU Leather Stand Case Cover For iPad Mini 1 Free Shipping</a></td>
			<td colspan="1" class="text-center">color：red</td>
		</tr>
		<tr class="warning">
			<th colspan="1" class="text-nowrap">
			订单留言
			</th>
			<td colspan="5" class="text-nowrap">
			买家:留言1<br>
			卖家:留言2
			</td>
			<th colspan="1" class="text-nowrap">
			小老板订单备注
			</th>
			<td colspan="4" class="text-nowrap">
			操作人销售1:客户两个都需要红色的！<br>
			操作人发货员2:红色的缺货！
			</td>
		</tr>
		<tr class="warning">
			<th colspan="1" class="text-right text-nowrap">
			付款备注
			</th>
			<td colspan="5">think you！</td>
			<th colspan="5" class="text-right text-nowrap">
			下单时间：2015-03-12 12:00:00 产品总额：US $ 17.60  运费总额：US $ 0.00 订单总额：US $ 17.60
			</th>
		</tr>
		</table>
	</form>
