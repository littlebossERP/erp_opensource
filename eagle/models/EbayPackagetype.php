<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_packagetype".
 *
 * @property string $pid
 * @property integer $siteid
 * @property string $packagevalue
 * @property string $packagedescription
 */
class EbayPackagetype extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_packagetype';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['siteid'], 'integer'],
            [['packagevalue', 'packagedescription'], 'required'],
            [['packagevalue', 'packagedescription'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pid' => 'Pid',
            'siteid' => 'Siteid',
            'packagevalue' => 'Packagevalue',
            'packagedescription' => 'Packagedescription',
        ];
    }
}
