<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;

$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/lazadaListing.css' );
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/lazada_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$lazada_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("lazadaListing.list_init()", \yii\web\View::POS_READY);

// $station=[
//   'MY'=>'MY', 
//   'TL'=>'TL'
// ];
// $shop_name=[
//     '1'=>'123@qq.com',
//     '2'=>'321@qq.com'
// ];
$condition=[
    'title'=>'标题',
    'sku'=>'SKU'
];
$batch=[
    '1'=>'批量发布',
    '2'=>'批量删除'
];

$this->title = TranslateHelper::t("lazada发布失败列表");
?>

<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('lazada');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$activeMenu
	]);
?>
<div class="col2-layout lazada-listing lazada-listing-publish-fail">
        <div class="search">
        <form action="/listing/lazada-listing/publish-fail" method="GET">
            <?=Html::dropDownList('shop_name',@$_REQUEST['shop_name'],$shop_name,['onchange'=>"lazada_submit($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'padding-top:3px;','prompt'=>'全部lazada店铺'])?>
            <?=Html::dropDownList('condition',@$_REQUEST['condition'],$condition,['onchange'=>"",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;padding-top:3px;'])?>
            <input type="text" class="eagle-form-control" id="condition_search" name="condition_search" value="<?php echo !empty($_REQUEST['condition_search'])?htmlentities($_REQUEST['condition_search']):null?>"><button type="submit" id="search" class="iv-btn btn-search serach-button"><span class="iconfont icon-sousuo"></span></button>
            <br />
            <button class="btn btn-info operate-button" type="button" onclick="lazadaListing.batch(1)"><span class="iconfont icon-fabu"></span> 批量发布</button>
            <button class="btn btn-info operate-button" type="button" onclick="lazadaListing.batch(2)"><span class="iconfont icon-shanchu"></span> 批量删除</button>
        
            <a class="btn btn-warning pull-right" style="" href="<?= eagle\modules\util\helpers\SysBaseInfoHelper::getHelpdocumentUrl("word_list_182_485.html ");?>" target="_blank">常见错误解决方式汇总</a>
            
        </form>
        </div>
        <div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="chk_all"></th>
                        <th style="width: 80px;">图片</th>
                        <th>标题</th>
                        <th style="width: 100px;">SKU</th>
                        <th style="width: 40px;">价格</th>
                        <th>库存</th>
                        <th>lazada店铺</th>
                        <th>创建时间</th>
                        <th style="width: 200px;">发布失败原因</th>
                        <th nowrap style="width:150px;">操作</th>
                    </tr>
                 </thead>
                 <tbody class="lzd_body">
                 <?php $num=1;foreach ($data as $data_detail):?>
                    <tr data-id="<?php echo $data_detail['id']?>" <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>
                        <td><input type="checkbox" id="chk_one"></td>
                        <td><img src="<?php echo $data_detail['image'];?>" style="max-width:60px;max-height:60px;"></td>
                        <td style="word-break:break-all;"><?php echo $data_detail['title'];?></td>
                        <td colspan="3">
                            <table style="width: 100%;" class="table_in">
                                <tbody>
                                    <?php foreach ($data_detail['variation'] as $var):?>
                                    <tr>
                                        <td style="width: 100px;word-break:break-all;"><?php echo $var['sku'];?></td>
                                        <td style="width: 40px;"><?php echo $var['price'];?></td>
                                        <td class="table-font-weight"><?php echo $var['quantity'];?></td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table>
                        </td>
                        <td><?php echo $data_detail['shop_name'];?></td>
                        <td><?php echo $data_detail['create_time'];?></td>
                      	<td style="word-break: break-word;color:red;">
                      		<?php if(isset(LazadaApiHelper::$PUBLISH_FAIL_STATUS_NAME_MAP[$data_detail['status']])):?>
                      		<?= LazadaApiHelper::$PUBLISH_FAIL_STATUS_NAME_MAP[$data_detail['status']]?>
                      		<?php endif;?>
                      		<br>
                      		<?php echo $data_detail['feed_info'];?>
                      	</td>
                        <td class="table-operate-style">
                            <a title="发布" onclick="lazadaListing.publishOne(<?= $data_detail['id'];?>)"><span class="iconfont icon-fabu"></span></a>
                            <a title="修改" href="/listing/lazada-listing/edit-product?id=<?php echo $data_detail['id'];?>"><span class="iconfont icon-tanchushezhi"></span></a>
                            <a title="删除" onclick="lazadaListing.deleteProduct(<?= $data_detail['id'];?>)"><span class="iconfont icon-shanchu"></span></a>
                            <a title="确认产品已发布" onclick="lazadaListing.confirmUploaded(<?= $data_detail['id'];?>)"><span class="iconfont icon-shuaxin"></span></a>
                        </td>
                    </tr>
                 <?php endforeach;?>
                 </tbody>
                
            </table>
        </div>
        <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
        	</div>
                <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
        </div>
</div>
<script>
function lazada_submit(val){
	 $("form").submit();		   
	};
</script>