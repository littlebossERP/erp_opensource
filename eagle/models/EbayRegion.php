<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_region".
 *
 * @property integer $regionid
 * @property string $description
 * @property integer $siteid
 * @property integer $detailversion
 * @property integer $updatetime
 */
class EbayRegion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['regionid'], 'required'],
            [['regionid', 'siteid', 'detailversion', 'updatetime'], 'integer'],
            [['description'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'regionid' => 'Regionid',
            'description' => 'Description',
            'siteid' => 'Siteid',
            'detailversion' => 'Detailversion',
            'updatetime' => 'Updatetime',
        ];
    }
}
