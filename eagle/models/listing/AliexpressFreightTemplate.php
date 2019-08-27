<?php

namespace eagle\models\listing;

use Yii;

/**
 * This is the model class for table "aliexpress_freight_template".
 *
 * @property integer $templateid
 * @property string $selleruserid
 * @property integer $uid
 * @property string $default
 * @property string $template_name
 * @property string $freight_setting
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
            [['templateid'], 'required'],
            [['templateid', 'uid', 'created', 'updated'], 'integer'],
            [['default', 'freight_setting'], 'string'],
            [['selleruserid'], 'string', 'max' => 100],
            [['template_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'templateid' => 'Templateid',
            'selleruserid' => 'Selleruserid',
            'uid' => 'Uid',
            'default' => 'Default',
            'template_name' => 'Template Name',
            'freight_setting' => 'Freight Setting',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
