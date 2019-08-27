<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_account_map".
 *
 * @property integer $id
 * @property string $selleruserid
 * @property string $paypal
 * @property string $desc
 * @property integer $created
 * @property integer $updated
 */
class EbayAccountMap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_account_map';
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
            [['selleruserid', 'paypal'], 'required'],
            [['created', 'updated'], 'integer'],
            [['selleruserid', 'paypal', 'desc'], 'string', 'max' => 32]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleruserid' => 'Selleruserid',
            'paypal' => 'Paypal',
            'desc' => 'Desc',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
