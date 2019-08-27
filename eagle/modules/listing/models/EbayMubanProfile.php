<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_muban_profile".
 *
 * @property integer $id
 * @property string $savename
 * @property string $type
 * @property string $detail
 * @property integer $crated
 * @property integer $updated
 */
class EbayMubanProfile extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_muban_profile';
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
//            [['detail'], 'string'],
            [['created', 'updated'], 'integer'],
            [['savename'], 'string', 'max' => 32],
            [['type'], 'string', 'max' => 16]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'savename' => 'Savename',
            'type' => 'Type',
            'detail' => 'Detail',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
    

    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('detail'),
    			)
    	);
    }
}
