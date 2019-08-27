<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_shippinglocation".
 *
 * @property integer $id
 * @property string $shippinglocation
 * @property string $description
 * @property integer $siteid
 * @property integer $record_updatetime
 * @property integer $detailversion
 * @property integer $updatetime
 */
class EbayShippinglocation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_shippinglocation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteid', 'record_updatetime', 'detailversion', 'updatetime'], 'integer'],
            [['shippinglocation'], 'string', 'max' => 20],
            [['description'], 'string', 'max' => 100],
            [['shippinglocation', 'siteid'], 'unique', 'targetAttribute' => ['shippinglocation', 'siteid'], 'message' => 'The combination of Shippinglocation and Siteid has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shippinglocation' => 'Shippinglocation',
            'description' => 'Description',
            'siteid' => 'Siteid',
            'record_updatetime' => 'Record Updatetime',
            'detailversion' => 'Detailversion',
            'updatetime' => 'Updatetime',
        ];
    }
}
