<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\bootstrap\Modal;
use eagle\modules\listing\models\EbayAutoInventorySearch;
/* @var $this yii\web\View */
/* @var $searchModel eagle\modules\listing\models\EbayAutoInventorySearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/ebay/ebayautoinventory.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaylistingv2/inventoryIndex.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->title = '自动补货列表';
$this->params['breadcrumbs'][] = $this->title;
$procArry=EbayAutoInventorySearch::$arrayProc;
?>
<!-- 左侧栏 -->
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>
    <!--No.1 search Create -->
    <?=$this->render('_searchItem',['sellers'=>$sellers]);?>

    <div class="ebay-auto-inventory-index">
    <!--No.2 batch operation -->
        <div class="batchOperation">
            <?= Html::a('创建', "javascript:void(0);", ['class' => 'btn btn-success','onclick'=>'autoInventory.created()']) ?>
    <!--         <?= Html::a('批量修改',"/listing/ebayautoinventory/update", ['class' => 'btn btn-success']) ?>
            <?= Html::a('批量删除',"javascript:void(0);", ['class' => 'btn btn-info gridviewdelete','onclick'=>'dobatch("delete")']) ?> -->
        </div>
    <!--No.3 grid view table -->
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            // 'options' =>['id'=>'grid'],
            'filterModel' => $searchModel,
            'rowOptions' => function ($model, $key, $index, $grid) {
                return ['id' => intval($model["id"])];
            },
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [//No.1-勾选框
                'class' => 'yii\grid\CheckboxColumn',
                'name'=>'id',
                ],
                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    // "attribute" => "itemid",
                    "label" => "缩略图",
                    "value" => function ($model,$key) {
                        $htmlStr =Html::img($model['mainimg'],['width'=>"60px",'height'=>"60px"]);
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.3-item信息
                    "attribute" => "itemid",
                    "label" => "Item ID",
                    "value" => function ($model,$key) {
                        $htmlStr =Html::beginTag('div',['style'=>'text-align:left;vertical-align:middle']);
                        $htmlStr.=Html::beginTag('p',['class'=>'m0_ebay']);
                        $htmlStr.=Html::a($model['itemid'],$model['viewitemurl'],['target'=>"_blank"]);
                        $htmlStr.=Html::endTag('p');
                        $htmlStr.=Html::tag('span',$model['itemtitle']);
                        $htmlStr.=Html::beginTag('span',['class'=>'pull-right']);
                        if ($model['isvariation']) {
                            $htmlStr.=Html::tag('span','多',['class'=>'squareSpan','style'=>'background-color:#0087e0;']);
                        }else if($model['listingtype']=='FixedPriceItem'){
                            $htmlStr.=Html::tag('span','固',['class'=>'squareSpan','style'=>'background-color:#096;']);
                        }elseif(($model['listingtype']=='Chinese')&&($model['isvariation']==0)){
                            $htmlStr.=Html::tag('span','拍',['class'=>'squareSpan','style'=>'background-color:#C9D12D;']);
                        }
                        $htmlStr.=Html::endTag('span');
                        $htmlStr.=Html::tag('p','「'.$model["selleruserid"].'」「'.$model['site'].'」',["class"=>"e_color e_m0"]);
                        $htmlStr.=Html::endTag('div');
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.4-sku
                    "attribute" => "sku",
                    "label" => "SKU",
                    "value"=>function($model){
                        $htmlStr=Html::beginTag('div',['style'=>'text-align:center;padding:7px']);
                        $htmlStr.=Html::tag('p',$model['sku']);
                        $htmlStr.=Html::endTag('div');
                        $htmlStr.=Html::beginTag('div',['style'=>'text-align:center;']);
                        if (isset($model['var_specifics'])) {
                            $paramVar='';
                            $arrayVar = json_decode($model['var_specifics'],true);
                            if (!isset($arrayVar[0])) {
                                $arrayVar=array($arrayVar);
                            }
                            foreach ($arrayVar as $vkey => $vval) {
                                    if (is_array($vval['Value'])){
                                        $paramVar='['.$vval['Name'].':'.$vval['Value'][0].']';
                                    }else{
                                        $paramVar='['.$vval['Name'].':'.$vval['Value'].']';
                                    }
                            $htmlStr.=Html::tag('p',$paramVar,["class"=>"e_color e_m0"]);
                            }
                        }
                        $htmlStr.=Html::endTag('div');

                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.5-online_quantity
                    "attribute" => "online_quantity",
                    "label" => "在线数量",
                    "value" => function ($model,$key) {
                        return $model['online_quantity'];
                    },
                ],
                [//No.6-状态
                    "attribute" => "status",
                    "label" => "状态",
                    "value" => function ($model,$key) {
                        $arr = array(0=>"暂停",1=>"开启",2=>"关闭");
                        $htmlStr =Html::beginTag('div',
                            [
                            'class'=>'btn-group btn-group-sm',
                            'style'=>'width:100px',
                            'name'=>intval($model['id']),
                            'value'=>$model['status'],
                            'onclick'=>'autoInventory.switchStatus(this)',
                            ]);
                        if ($model['status']==1) {//开启
                            $htmlStr.=Html::button($arr[$model['status']],['class'=>"btn btn-success",'style'=>'width:40px']);
                            $htmlStr.=Html::button('&nbsp&nbsp',['class'=>"btn btn-default",'style'=>'width:40px']);
                        }else{
                            $htmlStr.=Html::button('&nbsp&nbsp',['class'=>"btn btn-default",'style'=>'width:40px']);
                            $htmlStr.=Html::button($arr[$model['status']],['class'=>"btn btn-info",'style'=>'width:40px']);
                        }

                        $htmlStr.=Html::endTag('div');
                        return $htmlStr;
                    },
                    "filter"=>[0=>"暂停",1=>"开启",2=>"关闭"],
                    "format" => "raw",
                ],

                // [//No.5-执行状态
                //     "attribute" => "status_process",
                //     "label" => "执行状态",
                //     "value" => function ($model,$key) {
                //         $arrayProc = array(
                //             0=>"检查",
                //             1=>"检查运行中",
                //             3=>"检查异常",
                //             4=>"检查无item",
                //             2=>"补货",
                //             10=>"补货运行中",
                //             20=>"补货完成",
                //             30=>"补货异常",);
                //         // return $model["status_process"];
                //         return $arrayProc[$model["status_process"]];
                //     },
                //     "filter" =>$procArry,
                //     "format" => "raw",
                // ],
                [//No.7-补货次数
                    "attribute" => "success_cnt",
                    "label" => "补货次数",
                    "value" => "success_cnt",
                ],
                [//No.8-补货数量
                    "attribute" => "inventory",
                    "label" => "补货数量",
                    "value" => "inventory",
                ],
                [//No.9-补货时间
                    "attribute" => "updated",
                    "label" => "最近补货时间",
                    "value" => "updated",
                    'format' => ['date', 'php:Y-m-d'],
                ],

                [//No.10-操作
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update}{delete}',
                    "header" => "设置操作",
                    "buttons" => [
                        "update" => function ($url, $model, $key) {
                            $htmlStr =Html::beginTag('a',[
                                'href'=> $url,
                                'data-toggle'=>'modal',
                                'data-target' => '#update-modal',
                                'onclick'=>'autoInventory.oneUpdate('.intval($model["id"]).')']);
                            $htmlStr.=Html::beginTag('span',['class'=>'glyphicon glyphicon-pencil','style'=>'width:20px']);
                            $htmlStr.=Html::endTag('span');
                            $htmlStr.=Html::endTag('a');
                            return $htmlStr;
                        },
                        "delete" => function ($url, $model, $key) {
                            $htmlStr =Html::beginTag('a',[
                                'href'=> $url,
                                'data-toggle'=>'modal',
                                'data-target' => '#update-modal',
                                'onclick'=>'autoInventory.oneDelete('.intval($model["id"]).')']);
                            $htmlStr.=Html::beginTag('span',['class'=>'glyphicon glyphicon-trash','style'=>'width:20px']);
                            $htmlStr.=Html::endTag('span');
                            $htmlStr.=Html::endTag('a');
                            return $htmlStr;
                        },
                    ],

                ],
            ],
        ]);

        ?>
    </div>
</div>
