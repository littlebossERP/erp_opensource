<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_country".
 *
 * @property string $country
 * @property string $description
 * @property string $chinese
 * @property integer $detailversion
 * @property integer $record_updatetime
 * @property integer $updatetime
 */
class EbayCountry extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country'], 'required'],
            [['detailversion', 'record_updatetime', 'updatetime'], 'integer'],
            [['country'], 'string', 'max' => 3],
            [['description'], 'string', 'max' => 50],
            [['chinese'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'country' => 'Country',
            'description' => 'Description',
            'chinese' => 'Chinese',
            'detailversion' => 'Detailversion',
            'record_updatetime' => 'Record Updatetime',
            'updatetime' => 'Updatetime',
        ];
    }
}
