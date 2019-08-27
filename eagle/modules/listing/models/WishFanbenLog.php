<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "{{%wish_fanben_log}}".
 *
 * @property integer $id
 * @property string $wish_fanben_action
 * @property string $wish_fanben_info
 * @property string $wish_fanben_return_info
 * @property integer $wish_fanben_status
 * @property string $create_time
 * @property string $update_time
 */
class WishFanbenLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_fanben_log';
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
            [['wish_fanben_info', 'wish_fanben_return_info'], 'required'],
            [['wish_fanben_info', 'wish_fanben_return_info'], 'string'],
            [['wish_fanben_status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['wish_fanben_action'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'wish_fanben_action' => 'Wish Fanben Action',
            'wish_fanben_info' => 'Wish Fanben Info',
            'wish_fanben_return_info' => 'Wish Fanben Return Info',
            'wish_fanben_status' => 'Wish Fanben Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
