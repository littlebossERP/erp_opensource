<?php

namespace eagle\models\assistant;

use Yii;

/**
 * This is the model class for table "dp_enable".
 *
 * @property integer $dp_enable_id
 * @property string $dp_shop_id
 * @property integer $dp_puid
 * @property integer $enable_status
 * @property string $platform
 * @property string $create_time
 * @property string $update_time
 * @property integer $status
 */
class DpEnable extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dp_enable';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['dp_puid', 'enable_status', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['dp_shop_id', 'platform'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'dp_enable_id' => 'Dp Enable ID',
            'dp_shop_id' => 'Dp Shop ID',
            'dp_puid' => 'Dp Puid',
            'enable_status' => 'Enable Status',
            'platform' => 'Platform',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
        ];
    }
}
