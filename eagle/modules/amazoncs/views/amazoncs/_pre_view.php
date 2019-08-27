<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\amazoncs\models\CsQuestTemplate;

$uid = \Yii::$app->user->id;


$ContentVariance=[
	'order_source_order_id'=>'[平台订单号]',
	'seller_sku'=>'[店铺SKU]',
	'asin'=>'[ASIN]',
	'consignee'=>'[收件人名称]',
	'buyer_id'=>'[买家名称]',
	'items_list'=>'[订单物品列表(商品sku，名称，数量，单价)]',
	'track_no'=>'[包裹物流号]',
	'ship_by'=>'[包裹递送物流商]',
	'query_url'=>'[买家查看包裹追踪及商品推荐链接]',
	'product_url'=>'[商品链接]',
	'img_product_url'=>'[带图片的商品链接]',
	'contact_link'=>'[联系卖家链接]',
	'review_link'=>'[review链接]',
	'feedback_link'=>'[feedback链接]',
];


$addi_title_key_map = [
	'[买家查看包裹追踪及商品推荐链接]'=>'query_url',
	'[联系卖家链接]'=>'contact_seller',
	'[feedback链接]'=>'feedback_url',
];

$template_addi_info = empty($template_data['addi_info'])?[]:json_decode($template_data['addi_info'],true);
$addi_url_title_arr = empty($template_addi_info['url_title'])?[]:$template_addi_info['url_title'];
$TestContent=[
	'[平台订单号]'=>'02X-3417XXX-39XXX66 ',
	'[店铺SKU]'=>'BM600XXXXXXXX50',
	'[ASIN]'=>'B01XXXXXEI',
	'[收件人名称]'=>'Alex',
	'[买家名称]'=>'Alex',
	'[订单物品列表(商品sku，名称，数量，单价)]'=>'<br><table>
		<tr><td style="text-align:center;border:1px solid;">SKU</td>
			<td style="text-align:center;border:1px solid;">Product Name</td>
			<td style="text-align:center;border:1px solid;">Quantity</td>
			<td style="text-align:center;border:1px solid;">Unit Price</td>
		</tr>
		<tr>
			<td style="text-align:center;border:1px solid;">sku001</td>
			<td style="text-align:center;border:1px solid;">product_name001</td>
			<td style="text-align:center;border:1px solid;">1</td>
			<td style="text-align:center;border:1px solid;">10$</td>
		</tr>
	</table><br>',
	'[包裹物流号]'=>'TrackNo',
	'[包裹递送物流商]'=>'Carrier',
	'[买家查看包裹追踪及商品推荐链接]'=>empty($addi_url_title_arr[$addi_title_key_map['[买家查看包裹追踪及商品推荐链接]']])?'TrackUrl':$addi_url_title_arr[$addi_title_key_map['[买家查看包裹追踪及商品推荐链接]']],
	'[商品链接]'=>'ProductUrl',
	'[带图片的商品链接]'=>'ProductUrl',
	'[联系卖家链接]'=>empty($addi_url_title_arr[$addi_title_key_map['[联系卖家链接]']])?'Contact Seller':$addi_url_title_arr[$addi_title_key_map['[联系卖家链接]']],
	'[review链接]'=>'Leave Product Review',
	'[feedback链接]'=>empty($addi_url_title_arr[$addi_title_key_map['[feedback链接]']])?'Leave Feedback':$addi_url_title_arr[$addi_title_key_map['[feedback链接]']],
];

$a_variance_key = [
	'[买家查看包裹追踪及商品推荐链接]',
	'[商品链接]',
	'[带图片的商品链接]',
	'[联系卖家链接]',
	'[review链接]',
	'[feedback链接]',
];


$subject = empty($template_data['subject'])?'':$template_data['subject'];
$content = empty($template_data['contents'])?'':$template_data['contents'];

$content = str_replace(chr(10), '' ,$content);
$content = str_replace(chr(13), '<br>' ,$content);

foreach ($TestContent as $variance=>$testValue){
	$blue_test_value = '<span style="color:blue;">'.$testValue.'</span>';
	$subject = str_replace($variance, $blue_test_value ,$subject);
	
	if(in_array($variance,$a_variance_key)){
		if($variance=='[带图片的商品链接]'){
			$a_test_value = '<a><img src="/images/batchImagesUploader/no-img.png" style="width:100px;height:100px;"></a><a href="#" target="_blank">'.$blue_test_value.'</a>';
			$content = str_replace($variance, $a_test_value ,$content);
		}else{
			$a_test_value = '<a href="#" target="_blank">'.$blue_test_value.'</a>';
			$content = str_replace($variance, $a_test_value ,$content);
		}
	}else
		$content = str_replace($variance, $blue_test_value ,$content);
}


?>
<style>

</style>

<div>
	<div style="width:100%;" class='alert alert-info' role="alert">
		<span>蓝色字体为将被订单信息替换的内容</span>
	</div>
	<div style="width:100%;border-bottom:1px solid;margin-ottom:10px;">
		<span style="font: bold 14px/40px SimSun,Arial;color: #374655;">邮件标题:</span>
		<span style="font: bold 14px/40px SimSun,Arial;color: #374655;"><?=$subject?></span>
	</div>
	<div style="width:100%;padding:10px;font:14px SimSun,Arial;">
		<?=$content?>
	</div>
</div>