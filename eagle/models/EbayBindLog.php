<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_bind_log".
 *
 * @property integer $id
 * @property integer $puid
 * @property integer $devid
 * @property string $selleruserid
 * @property string $createtime
 * @property string $add_info
 */
class EbayBindLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_bind_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'devid', 'selleruserid', 'createtime', 'add_info'], 'required'],
            [['puid', 'devid'], 'integer'],
            [['createtime'], 'safe'],
            [['add_info'], 'string'],
            [['selleruserid'], 'string', 'max' => 255]
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
            'devid' => 'Devid',
            'selleruserid' => 'Selleruserid',
            'createtime' => 'Createtime',
            'add_info' => 'Add Info',
        ];
    }
}
