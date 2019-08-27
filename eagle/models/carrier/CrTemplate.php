<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "cr_template".
 *
 * @property string $template_id
 * @property string $template_name
 * @property string $template_content
 * @property string $create_time
 * @property string $update_time
 */
class CrTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cr_template';
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
            [['template_content'], 'string'],
            [['create_time', 'update_time'], 'integer'],
            [['template_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'template_id' => '模版编号',
            'template_name' => '模版名称',
            'template_content' => '内容',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
