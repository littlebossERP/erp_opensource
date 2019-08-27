<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_photo".
 *
 * @property integer $id
 * @property string $sku
 * @property integer $priority
 * @property string $photo_scale
 * @property string $file_name
 * @property string $photo_url
 */
class Photo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_photo';
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
            [['sku', 'photo_scale'], 'required'],
            [['priority'], 'integer'],
            [['sku'], 'string', 'max' => 255],
            [['photo_scale'], 'string', 'max' => 2],
			[['photo_url'], 'string', 'max' => 455],
            [['file_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sku' => 'Sku',
            'priority' => 'Priority',
            'photo_scale' => 'Photo Scale',
            'file_name' => 'File Name',
            'photo_url' => 'Photo Url',
        ];
    }
}
