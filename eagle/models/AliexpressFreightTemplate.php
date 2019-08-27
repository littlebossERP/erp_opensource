<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_category".
 *
 * @property integer $cateid
 * @property integer $pid
 * @property integer $level
 * @property string $name_zh
 * @property string $name_en
 * @property string $isleaf
 * @property string $attribute
 * @property integer $created
 * @property integer $updated
 */
class AliexpressFreightTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_freight_template';
    }
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
            [['templateid','selleruserid'], 'required'],
            [['uid', 'created', 'updated'], 'integer'],
            [['selleruserid','template_name'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'templateid' => 'TemplateId',
            'uid' => 'Uid',
            'selleruserid' => 'selleruserId',
            'template_name' => 'Template Name',
            'created' => 'Created',
            'updated' => 'Updated',
            'freight_setting' => 'Freight Setting',
            'default' => 'Default',

        ];
    }
}
