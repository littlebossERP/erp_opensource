<?php

namespace eagle\models\message;

use Yii;

/**
 * This is the model class for table "cs_customer_msg_template".
 *
 * @property integer $id
 * @property string $type
 * @property integer $puid
 * @property integer $seq
 * @property string $template_name
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 */
class CustomerMsgTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_customer_msg_template';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'puid', 'seq', 'template_name', 'create_time'], 'required'],
            [['puid', 'seq'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['addi_info'], 'string'],
            [['type'], 'string', 'max' => 1],
            [['template_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'puid' => 'Puid',
            'seq' => 'Seq',
            'template_name' => 'Template Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
