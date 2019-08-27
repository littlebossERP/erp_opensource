<?php

namespace eagle\models\assistant;

use Yii;

/**
 * This is the model class for table "om_order_message_template".
 *
 * @property string $id
 * @property string $template_name
 * @property string $content
 * @property integer $status
 * @property integer $is_active
 * @property string $create_time
 * @property string $update_time
 */
class OmOrderMessageTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'om_order_message_template';
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
            [['content', 'is_active'], 'required'],
            [['content'], 'string'],
            [['status', 'is_active'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['template_name'], 'string', 'max' => 50]
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
            'content' => 'Content',
            'status' => 'Status',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
