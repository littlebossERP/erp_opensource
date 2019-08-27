<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "carrier_tcpdf_img".
 *
 * @property integer $id
 * @property string $photo_primary
 * @property string $photo_file_path
 * @property integer $puid
 * @property integer $create_time
 * @property integer $run_status
 * @property integer $times
 */
class CarrierTcpdfImg extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_tcpdf_img';
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
            [['photo_primary'], 'required'],
            [['puid', 'create_time', 'run_status', 'times'], 'integer'],
            [['photo_primary', 'photo_file_path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'photo_primary' => 'Photo Primary',
            'photo_file_path' => 'Photo File Path',
            'puid' => 'Puid',
            'create_time' => 'Create Time',
            'run_status' => 'Run Status',
            'times' => 'Times',
        ];
    }
}
