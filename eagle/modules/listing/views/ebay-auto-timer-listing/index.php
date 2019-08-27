<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\helpers\Helper_Siteinfo;

/* @var $this yii\web\View */
/* @var $searchModel eagle\modules\listing\models\EbayAutoTimerListingSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$this->registerCssFile(\Yii::getAlias('@web').'/css/listing/ebay/ebayautotimerlisting.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/ebaylistingv2/timerlisting.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->title = '定时刊登';
$this->params['breadcrumbs'][] = $this->title;
?>
<!-- 左侧栏 -->
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>

    <div class="ebay-auto-timer-listing-index">
    <!--
    No.1 search item 
    -->
        <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
        <?php // echo $this->render('../ebay-listing-common/_searchItem',['sellers'=>$sellers]); ?>

        <p>
            <?= Html::a('创建', ['create'], ['class' => 'btn btn-success']) ?>
        </p>

    <!--
    No.2 grid view 
    -->
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            // 'filterModel' => $searchModel,
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
                        $htmlStr =Html::img($model->ebay_muban->mainimg,['width'=>"60px",'height'=>"60px"]);
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    "attribute" => "draft_id",
                    "label" => "范本编号",
                    "value" => function ($model,$key) {
                        $htmlStr =Html::tag('p',$model->draft_id);
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.3-item信息
                    // "attribute" => "itemid",
                    "label" => "item 信息",
                    "value" => function ($model,$key) {
                        $htmlStr =Html::beginTag('div',['style'=>'text-align:left;vertical-align:middle']);
                        $htmlStr.=Html::tag('span',$model->ebay_muban->itemtitle);
                        $htmlStr.=Html::beginTag('span',['class'=>'pull-right']);
                        if ($model->ebay_muban->isvariation) {
                            $htmlStr.=Html::tag('span','多',['class'=>'squareSpan','style'=>'background-color:#0087e0;']);
                        }else if($model->ebay_muban->listingtype=='FixedPriceItem'){
                            $htmlStr.=Html::tag('span','固',['class'=>'squareSpan','style'=>'background-color:#096;']);
                        }elseif(($model->ebay_muban->listingtype=='Chinese')&&($model->ebay_muban->isvariation==0)){
                            $htmlStr.=Html::tag('span','拍',['class'=>'squareSpan','style'=>'background-color:#C9D12D;']);
                        }
                        $htmlStr.=Html::endTag('span');
                        $site=Helper_Siteinfo::getEbaySiteIdList('no','code');
                        $htmlStr.=Html::tag('p','「'.$model->selleruserid.'」「'.
                            $site[$model->ebay_muban->siteid].'」',["class"=>"e_color e_m0"]);
                        $htmlStr.=Html::endTag('div');
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    // "attribute" => "runtime",
                    "label" => "sku",
                    "value" => function ($model,$key) {
                        $htmlStr =Html::tag('p',$model->ebay_muban->sku);
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],

                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    "attribute" => "runtime",
                    "label" => "设定时间",
                    "value" => function ($model,$key) {
                        $datetime = new \DateTime();
                        $datetime->setTimestamp($model->runtime);
                        $datetime->setTimezone(new \DateTimeZone('Etc/GMT'.$model->set_gmt));
                        $htmlStr =Html::tag('p',$datetime->format(\DateTime::ISO8601));
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    "attribute" => "status",
                    "label" => "状态",
                    "value" => function ($model,$key) {
                        $arr = array(0=>"已暂停",1=>"已开启");
                        $htmlStr =Html::beginTag('div');
                        if ($model['status']) {
                            $color='#009d95';
                        }else{
                            $color='#3f56a1';
                        }
                        $htmlStr.=Html::tag('span',$arr[$model['status']],['style'=>'font-weight:bold;color:'.$color]);
                        $htmlStr.=Html::endTag('div');
                        return $htmlStr;
                    },

                    "format" => "raw",
                ],
                [//No.2-图片
                    // "class"=>'yii\grid\DataColumn',
                    // "attribute" => "runtime",
                    "label" => "刊登结果",
                    "value" => function ($model,$key) {
                        $result=is_null($model->listing_result)?NULL:json_decode($model->listing_result,true);
                        $htmlStr =Html::tag('p',@$result['Ack']);
                        // $htmlStr.=Html::tag('p',@$result['Errors']['ShortMessage']);
                        return $htmlStr;
                    },
                    "format" => "raw",
                ],
                [//No.10-操作
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{update}{delete}',
                    "header" => "操作",
                    "buttons" => [
                        "update" => function ($url, $model, $key) {
                            $htmlStr =Html::beginTag('a',[
                                'href'=> $url,
                                'data-toggle'=>'modal',
                                'data-target' => '#update-modal',
                                'onclick'=>'autoTimerListing.update_one('.intval($model->id).')']);
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
                                'onclick'=>'autoTimerListing.delete_one('.intval($model->id).')']);
                            $htmlStr.=Html::beginTag('span',['class'=>'glyphicon glyphicon-trash','style'=>'width:20px']);
                            $htmlStr.=Html::endTag('span');
                            $htmlStr.=Html::endTag('a');
                            return $htmlStr;
                        },
                    ],

                ],

            ],
        ]); ?>

    </div>
</div>
