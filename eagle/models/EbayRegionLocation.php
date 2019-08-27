<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_region_location".
 *
 * @property integer $id
 * @property string $description
 * @property string $location
 * @property string $region
 * @property string $zhname
 */
class EbayRegionLocation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_region_location';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description', 'region', 'zhname'], 'string', 'max' => 200],
            [['location'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'description' => 'Description',
            'location' => 'Location',
            'region' => 'Region',
            'zhname' => 'Zhname',
        ];
    }
}
