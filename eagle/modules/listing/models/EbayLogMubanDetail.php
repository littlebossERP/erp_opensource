<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "ebay_log_muban_detail".
 *
 * @property integer $logid
 * @property string $message
 * @property string $description
 * @property string $fee
 * @property string $error
 */
class EbayLogMubanDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_log_muban_detail';
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
            [['logid'], 'required'],
            [['logid'], 'integer'],
            [['description'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'logid' => 'Logid',
            'message' => 'Message',
            'description' => 'Description',
            'fee' => 'Fee',
            'error' => 'Error',
        ];
    }
    

    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('message','fee','error'),
    			)
    	);
    }
}
