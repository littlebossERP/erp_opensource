<?php

namespace eagle\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "ebay_categoryfeature".
 *
 * @property integer $id
 * @property integer $siteid
 * @property integer $categoryid
 * @property string $conditionenabled
 * @property string $conditionvalues
 * @property integer $specificsenabled
 * @property string $variationsenabled
 * @property string $isbnenabled
 * @property string $upcenabled
 * @property string $eanenabled
 * @property integer $record_updatetime
 * @property integer $category_version
 * @property string $category_feature
 */
class EbayCategoryfeature extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_categoryfeature';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteid', 'categoryid', 'specificsenabled', 'record_updatetime', 'category_version'], 'integer'],
            [['conditionvalues', 'category_feature'], 'string'],
            [['conditionenabled', 'variationsenabled', 'isbnenabled', 'upcenabled', 'eanenabled'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'siteid' => 'Siteid',
            'categoryid' => 'Categoryid',
            'conditionenabled' => 'Conditionenabled',
            'conditionvalues' => 'Conditionvalues',
            'specificsenabled' => 'Specificsenabled',
            'variationsenabled' => 'Variationsenabled',
            'isbnenabled' => 'Isbnenabled',
            'upcenabled' => 'Upcenabled',
            'eanenabled' => 'Eanenabled',
            'record_updatetime' => 'Record Updatetime',
            'category_version' => 'Category Version',
            'category_feature' => 'Category Feature',
        ];
    }

    public function behaviors(){
        return array(
                'SerializeBehavior' => array(
                        'class' => SerializeBehavior::className(),
                        'serialAttributes' => array('conditionvalues'),
                )
        );
    }
}
