<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%cms_help_word_management}}".
 *
 * @property integer $id
 * @property string $help_word_title
 * @property string $help_word_content
 * @property string $help_word_icon
 * @property integer $help_type_id
 * @property string $help_word_id
 * @property integer $help_word_status
 * @property string $help_word_keywords
 * @property string $create_time
 * @property string $update_time
 */
class CmsHelpWordManagement extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_help_word_management}}';
    }
    
    public static function getDb()
    {
        return Yii::$app->get('cmsdb');
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['help_word_content', 'help_word_icon'], 'string'],
            [['help_type_id'], 'required'],
            [['help_type_id', 'help_word_id', 'help_word_status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['help_word_title'], 'string', 'max' => 50],
            [['help_word_keywords'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'help_word_title' => '文档标题',
            'help_word_content' => '文档内容',
            'help_word_icon' => '文档ICON路径',
            'help_type_id' => '所属模块 1订单管理 2刊登管理 3功能模块 4APP应用',
            'help_word_id' => '上级分类ID',
            'help_word_status' => '应用状态 1待发布 2发布 3停用',
            'help_word_keywords' => '关键词',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }
}
