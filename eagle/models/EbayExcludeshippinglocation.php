<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_excludeshippinglocation".
 *
 * @property integer $id
 * @property string $description
 * @property string $location
 * @property string $region
 * @property integer $siteid
 * @property integer $updatetime
 * @property integer $detailversion
 * @property integer $record_updatetime
 */
class EbayExcludeshippinglocation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_excludeshippinglocation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteid', 'updatetime', 'detailversion', 'record_updatetime'], 'integer'],
            [['description', 'region'], 'string', 'max' => 200],
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
            'siteid' => 'Siteid',
            'updatetime' => 'Updatetime',
            'detailversion' => 'Detailversion',
            'record_updatetime' => 'Record Updatetime',
        ];
    }
}
