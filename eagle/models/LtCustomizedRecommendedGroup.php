<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lt_customized_recommended_group".
 *
 * @property string $id
 * @property string $puid
 * @property string $seller_id
 * @property string $platform
 * @property string $group_name
 * @property string $group_comment
 * @property string $addi_info
 * @property integer $member_count
 * @property integer $create_time
 * @property integer $update_time
 */
class LtCustomizedRecommendedGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_customized_recommended_group';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'seller_id', 'platform', 'group_name'], 'required'],
            [['puid', 'member_count', 'create_time', 'update_time'], 'integer'],
            [['addi_info'], 'string'],
            [['seller_id', 'platform'], 'string', 'max' => 50],
            [['group_name', 'group_comment'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'seller_id' => 'Seller ID',
            'platform' => 'Platform',
            'group_name' => 'Group Name',
            'group_comment' => 'Group Comment',
            'addi_info' => 'Addi Info',
            'member_count' => 'Member Count',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
