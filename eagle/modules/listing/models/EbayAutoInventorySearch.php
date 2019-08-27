<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use eagle\modules\listing\models\EbayAutoInventory;

/**
 * EbayAutoInventorySearch represents the model behind the search form about `eagle\modules\listing\models\EbayAutoInventory`.
 */
class EbayAutoInventorySearch extends EbayAutoInventory
{
    public static $arrayProc = array(
                    0=>"检查",
                    1=>"检查运行中",
                    3=>"检查异常",
                    4=>"检查无item",
                    2=>"补货",
                    10=>"补货运行中",
                    20=>"补货完成",
                    30=>"补货异常",);

    // public function attributes()
    // {
    //     return array_merge(parent::attributes(),['mainimg']);
    // }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'draft_id', 'itemid', 'item_type','online_quantity','status', 'status_process', 'less_than_equal_to', 'inventory', 'success_cnt', 'ebay_uid', 'created', 'updated'], 'integer'],
            [['var_specifics','selleruserid', 'sku','type'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params,$post,$atvUsers)
    {
        \yii::info($params,"file");
        /*
         * No.1-search inventory
         */
        $query = EbayAutoInventory::find()->where('status != 2')->andwhere(['selleruserid'=>$atvUsers]);
        if (isset($params['EbayAutoInventorySearch'])) {
            $loadParams=$params['EbayAutoInventorySearch'];
            if ($loadParams['itemid']!='') {
                $query->andwhere(['itemid' => $loadParams['itemid']]);
            }
            if ($loadParams['sku']!='') {
                $query->andwhere(['sku' => $loadParams['sku']]);
            }
            if ($loadParams['online_quantity']!='') {
                $query->andwhere(['online_quantity' => $loadParams['online_quantity']]);
            }
            if ($loadParams['status']!='') {
                $query->andwhere(['status' => $loadParams['status']]);
            }
            if (isset($loadParams['status_process']) && $loadParams['status_process']!='') {
                $query->andwhere(['status_process' => $loadParams['status_process']]);
            }
            if ($loadParams['success_cnt']!='') {
                $query->andwhere(['success_cnt' => $loadParams['success_cnt']]);
            }
            if ($loadParams['inventory']!='') {
                $query->andwhere(['inventory' => $loadParams['inventory']]);
            }
            if ($loadParams['updated']!='') {
                $query->andwhere(['updated' => $loadParams['updated']]);
            }
        }

        $query=$query->orderby('id DESC')->asArray()->all();
        $itemIdArry=ArrayHelper::getColumn($query, 'itemid');
        /*
         * No.2-search item
         */
        if (!empty($query)) {
            $item=EbayItem::find()->where(['itemid'=>$itemIdArry]);
            if (!empty($post)) {
                if (!empty($post['itemtitle'])){//标题搜索
                    $item->andWhere('itemtitle like :t',[':t'=>'%'.$post['itemtitle'].'%']);
                }
                if (!empty($post['itemid'])){//itemid搜索
                    $item->andWhere(['itemid'=>$post['itemid']]);
                }
                if (!empty($post['selleruserid'])){//sellerid搜索
                    $item->andWhere(['selleruserid'=>$post['selleruserid']]);
                }
                if (!empty($post['listingtype'])){//类型搜索
                    if ($post['listingtype']=='FixedPriceItem') {
                        $item->andWhere(['isvariation'=>0]);
                    }else if ($post['listingtype']=='IsVariation') {
                        $item->andWhere(['isvariation'=>1]);
                    }
                }
                if (!empty($post['site'])){//站点搜索
                    $item->andWhere(['site'=>$post['site']]);
                }
                if (!empty($post['sku'])){//sku搜索
                    $itemid = EbayItemVariationMap::find()->where(['like','sku',$post['sku']])->select('itemid')->asArray()->all();
                    $itemid = Helper_Array::getCols($itemid, 'itemid');
                    $item->andWhere(['itemid'=>$itemid]);
                }
                if (isset($post['hassold'])&&$post['hassold']!=''){//已售出搜索
                    if ($post['hassold'] == '0'){
                        $item->andWhere('quantitysold=0');
                    }else{
                        $item->andWhere('quantitysold>0');
                    }
                }
                if (isset($post['outofstockcontrol'])&&$post['outofstockcontrol']!=''){//永久在线搜索
                    if ($post['outofstockcontrol'] == '0'){
                        $item->andWhere(['outofstockcontrol'=>0]);
                    }else{
                        $item->andWhere(['outofstockcontrol'=>1]);
                    }
                }
            }
            $item=$item->asArray()->all();
            if (!empty($item)) {
                foreach ($query as $qkey => $qval) {
                    foreach ($item as $ikey => $ival) {
                        if ($ival['itemid']==$qval['itemid']) {
                            $data[]=array_merge($ival,$qval);//相同会后面覆盖前面,不能调换参数位置
                        }
                    }

                }
                if (!isset($data)) {
                    $data=NULL;
                }
            }else{
                $data=NULL;
            }

        }else{
            $data=$query;
        }
        // \yii::info($data,"file");
        /*
         * No.3-data provider
         */
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => 50,
            ],
            // 'sort' => [
            //     // 'attributes' => ['id', 'name'],
            //     // 'defaultOrder'=>[
            //     //     'id'=>SORT_DESC,
            //     // ]
            // ],
        ]);

        return $dataProvider;
    }
}//end class
