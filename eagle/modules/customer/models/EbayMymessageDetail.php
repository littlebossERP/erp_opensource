<?php

namespace eagle\modules\customer\models;

use Yii;

/**
 * This is the model class for table "ebay_mymessage_detail".
 *
 * @property string $messageid
 * @property string $responseurl
 * @property string $text
 */
class EbayMymessageDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_mymessage_detail';
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
            [['messageid'], 'required'],
            [['messageid'], 'integer'],
            [['responseurl', 'text'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'messageid' => 'Messageid',
            'responseurl' => 'Responseurl',
            'text' => 'Text',
        ];
    }
}
