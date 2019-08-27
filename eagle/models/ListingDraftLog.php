<?php namespace eagle\models;

use Yii;

/**
 * This is the model class for table "listing_draft_log".
 *
 * @property integer $id
 * @property string $parent_sku
 * @property string $platform_from
 * @property string $shop_from
 * @property string $platform_to
 * @property string $shop_to
 * @property string $create_time
 */
class ListingDraftLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'listing_draft_log';
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
            [['parent_sku', 'platform_from', 'shop_from', 'platform_to'], 'required'],
            [['create_time'], 'safe'],
            [['parent_sku'], 'string', 'max' => 255],
            [['platform_from', 'platform_to'], 'string', 'max' => 50],
            [['shop_from', 'shop_to'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_sku' => 'Parent Sku',
            'platform_from' => 'Platform From',
            'shop_from' => 'Shop From',
            'platform_to' => 'Platform To',
            'shop_to' => 'Shop To',
            'create_time' => 'Create Time',
        ];
    }
}
