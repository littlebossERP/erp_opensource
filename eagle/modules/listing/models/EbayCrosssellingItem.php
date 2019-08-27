<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_crossselling_item".
 *
 * @property integer $id
 * @property integer $crosssellingid
 * @property integer $sort
 * @property string $data
 * @property string $html
 */
class EbayCrosssellingItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_crossselling_item';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['crosssellingid'], 'required'],
            [['crosssellingid', 'sort'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'crosssellingid' => 'Crosssellingid',
            'sort' => 'Sort',
            'data' => 'Data',
            'html' => 'Html',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('data'),
    			)
    	);
    }
}
