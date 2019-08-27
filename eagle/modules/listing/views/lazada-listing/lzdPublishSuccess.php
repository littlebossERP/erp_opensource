
<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/lazadaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$lazada_listing_version );
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

$this->title = TranslateHelper::t("Lazada发布成功列表");
?>
<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('lazada');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$activeMenu
	]);
?>
<div class="col2-layout lazada-listing lazada-listing-publish-succcess">
        <div class="search">
        <form action="/listing/lazada-listing/publish-success" method="GET">
            <?=Html::dropDownList('shop_name',@$_REQUEST['shop_name'],$shop_name,['onchange'=>"lazada_submit($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'width:130px;','prompt'=>'全部lazada店铺'])?>
            <br />
            <?=Html::dropDownList('condition',@$_REQUEST['condition'],$condition,['onchange'=>"",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;'])?>
            <input type="text" class="eagle-form-control" id="condition_search" name="condition_search" value="<?php echo !empty($_REQUEST['condition_search'])?htmlentities($_REQUEST['condition_search']):null?>"><input type="submit" value="搜索"  id="search" class="btn btn-success btn-sm">
       	</form>
        </div>
        <div>
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="chk_all"></th>
                        <th>图片</th>
                        <th>标题</th>
                        <th style="width: 120px;">SKU</th>
                        <th style="width: 70px;">价格</th>
                        <th>库存</th>
                        <th>lazada店铺</th>
                        <th>创建时间</th>
                        <th nowrap style="width:175px;">操作</th>
                    </tr>
                 </thead>
                 <tbody class="lzd_body">
                 <?php $num=1;foreach ($data as $data_detail):?>
                    <tr data-id="<?php echo $data_detail['id']?>" <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>
                        <td><input type="checkbox" id="chk_one"></td>
                        <td><img src="<?php echo $data_detail['image'];?>" style="width:60px;height:60px;"></td>
                        <td><?php echo $data_detail['title'];?></td>
                        <td colspan="3">
                            <table class="table_in" style="width: 100%;">
                                <tbody>
                                    <?php foreach ($data_detail['variation'] as $var):?>
                                    <tr>
                                        <td style="width: 120px;"><?php echo $var['sku'];?></td>
                                        <td style="width: 70px;"><?php echo $var['price'];?></td>
                                        <td><?php echo $var['quantity'];?></td>
                                    </tr>
                                    <?php endforeach;?>
                                </tbody>
                            </table>
                        </td>
                        <td><?php echo $data_detail['shop_name'];?></td>
                        <td><?php echo $data_detail['create_time'];?></td>
                        <td class="table-operate-style">
                            <a title="复制" href="/listing/lazada-listing/copy-product?id=<?php echo $data_detail['id'];?>"><span class="iconfont icon-fuzhi"></span></a> 
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
	}
</script>