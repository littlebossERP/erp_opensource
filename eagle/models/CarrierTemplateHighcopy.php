<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "carrier_template_highcopy".
 *
 * @property integer $id
 * @property string $type
 * @property string $template_name
 * @property string $template_img
 * @property string $helper_class
 * @property string $helper_function
 * @property string $additional_print_options
 */
class CarrierTemplateHighcopy extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_template_highcopy';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type'], 'required'],
            [['template_img', 'additional_print_options'], 'string'],
            [['type'], 'string', 'max' => 50],
            [['template_name', 'helper_function'], 'string', 'max' => 100],
            [['helper_class'], 'string', 'max' => 300]
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
            'template_name' => 'Template Name',
            'template_img' => 'Template Img',
            'helper_class' => 'Helper Class',
            'helper_function' => 'Helper Function',
            'additional_print_options' => 'Additional Print Options',
        ];
    }
}
