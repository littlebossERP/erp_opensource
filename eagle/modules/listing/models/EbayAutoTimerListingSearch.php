<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use eagle\modules\listing\models\EbayAutoTimerListing;

/**
 * EbayAutoTimerListingSearch  represents the model behind the search form about `eagle\modules\listing\models\EbayAutoTimerListing`.
 */
class EbayAutoTimerListingSearch extends EbayAutoTimerListing
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'draft_id', 'itemid', 'status', 'status_process', 'runtime', 'set_hour', 'set_min', 'err_cnt', 'ebay_uid', 'puid', 'created', 'updated'], 'integer'],
            [['selleruserid', 'itemtitle', 'set_gmt', 'set_date', 'verify_result', 'listing_result'], 'safe'],
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
    public function search($params,$sellers)
    {
        $query = EbayAutoTimerListing::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination'=>['pageSize'=>10],
            'sort' => [
                'defaultOrder' => [
                'id' => SORT_DESC,
                ]
            ]
        ]);
        $query->andWhere(['selleruserid'=>$sellers]);
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'draft_id' => $this->draft_id,
            'itemid' => $this->itemid,
            'status' => $this->status,
            'status_process' => $this->status_process,
            'runtime' => $this->runtime,
            'set_date' => $this->set_date,
            'set_hour' => $this->set_hour,
            'set_min' => $this->set_min,
            'err_cnt' => $this->err_cnt,
            'ebay_uid' => $this->ebay_uid,
            'puid' => $this->puid,
            'created' => $this->created,
            'updated' => $this->updated,
        ]);

        $query->andFilterWhere(['like', 'selleruserid', $this->selleruserid])
            ->andFilterWhere(['like', 'itemtitle', $this->itemtitle])
            ->andFilterWhere(['like', 'set_gmt', $this->set_gmt])
            ->andFilterWhere(['like', 'verify_result', $this->verify_result])
            ->andFilterWhere(['like', 'listing_result', $this->listing_result]);

        return $dataProvider;
    }
}
