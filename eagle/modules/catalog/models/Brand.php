<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_brand".
 *
 * @property integer $brand_id
 * @property string $name
 * @property string $addi_info
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 */
class Brand extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_brand';
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
            [['capture_user_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 100],
            [['addi_info', 'comment'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'brand_id' => 'Brand ID',
            'name' => 'Name',
            'addi_info' => 'Addi Info',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
