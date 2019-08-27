<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_photo_cache".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $status
 * @property string $orig_url
 * @property string $local_path
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 */
class PhotoCache extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_photo_cache';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'status', 'orig_url', 'create_time'], 'required'],
            [['puid'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['addi_info'], 'string'],
            [['status'], 'string', 'max' => 1],
            [['orig_url'], 'string', 'max' => 255],
            [['local_path'], 'string', 'max' => 2000]
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
            'status' => 'Status',
            'orig_url' => 'Orig Url',
            'local_path' => 'Local Path',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
