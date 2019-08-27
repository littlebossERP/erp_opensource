<?php

namespace eagle\models\message;

use Yii;

/**
 * This is the model class for table "cs_customer_msg_template_detail".
 *
 * @property integer $id
 * @property integer $template_id
 * @property string $lang
 * @property string $subject
 * @property string $content
 * @property string $addi_info
 */
class CustomerMsgTemplateDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_customer_msg_template_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template_id', 'lang'], 'required'],
            [['template_id'], 'integer'],
            [['subject', 'content', 'addi_info'], 'string'],
            [['lang'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_id' => 'Template ID',
            'lang' => 'Lang',
            'subject' => 'Subject',
            'content' => 'Content',
            'addi_info' => 'Addi Info',
        ];
    }
}
