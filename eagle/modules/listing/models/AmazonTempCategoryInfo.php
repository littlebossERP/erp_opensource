<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "amazon_temp_category_info".
 *
 * @property integer $id
 * @property integer $BrowseNodeId
 * @property string $marketplace_short
 * @property string $marketplace_id
 * @property string $cat_info
 * @property integer $is_get_info
 * @property integer $err_times
 * @property string $err_msg
 * @property string $create_time
 * @property string $update_time
 */
class AmazonTempCategoryInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_temp_category_info';
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
            [['BrowseNodeId', 'marketplace_short', 'marketplace_id', 'create_time'], 'required'],
            [['BrowseNodeId', 'is_get_info', 'err_times'], 'integer'],
            [['cat_info'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['marketplace_short'], 'string', 'max' => 2],
            [['marketplace_id'], 'string', 'max' => 50],
            [['err_msg'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'BrowseNodeId' => 'Browse Node ID',
            'marketplace_short' => 'Marketplace Short',
            'marketplace_id' => 'Marketplace ID',
            'cat_info' => 'Cat Info',
            'is_get_info' => 'Is Get Info',
            'err_times' => 'Err Times',
            'err_msg' => 'Err Msg',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
