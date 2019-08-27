<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_developer_account_used".
 *
 * @property integer $dev_account_id
 * @property string $sellerid
 */
class EbayDeveloperAccountUsed extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_developer_account_used';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['dev_account_id', 'sellerid'], 'required'],
            [['dev_account_id'], 'integer'],
            [['sellerid'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'dev_account_id' => 'Dev Account ID',
            'sellerid' => 'Sellerid',
        ];
    }
}
