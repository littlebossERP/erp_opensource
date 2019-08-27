<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_user_pdf".
 *
 * @property integer $id
 * @property string $origin_url
 * @property string $origin_size
 * @property string $original_name
 * @property integer $service
 * @property string $file_key
 * @property string $language
 * @property string $create_time
 * @property string $add_info
 */
class UserPdf extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_user_pdf';
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
            [['original_name'], 'required'],
            [['service'], 'integer'],
            [['create_time'], 'safe'],
            [['origin_url', 'original_name', 'file_key', 'add_info'], 'string', 'max' => 255],
            [['origin_size', 'language'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'origin_url' => 'Origin Url',
            'origin_size' => 'Origin Size',
            'original_name' => 'Original Name',
            'service' => 'Service',
            'file_key' => 'File Key',
            'language' => 'Language',
            'create_time' => 'Create Time',
            'add_info' => 'Add Info',
        ];
    }
}
