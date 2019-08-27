<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cr_carrier_template".
 *
 * @property string $id
 * @property string $template_name
 * @property string $template_content
 * @property integer $template_height
 * @property integer $template_width
 * @property integer $is_use
 * @property string $create_time
 * @property string $template_type
 */
class CrCarrierTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cr_carrier_template';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template_name'], 'required'],
            [['template_content'], 'string'],
            [['template_height', 'template_width', 'is_use', 'create_time'], 'integer'],
            [['template_name'], 'string', 'max' => 100],
            [['template_type'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_name' => 'Template Name',
            'template_content' => 'Template Content',
            'template_height' => 'Template Height',
            'template_width' => 'Template Width',
            'is_use' => 'Is Use',
            'create_time' => 'Create Time',
            'template_type' => 'Template Type',
        ];
    }
}
