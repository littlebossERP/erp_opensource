<?php

namespace eagle\modules\html_catcher\models;

use Yii;

/**
 * This is the model class for table "hc_analyse_html_role".
 *
 * @property integer $id
 * @property string $platform
 * @property string $name
 * @property string $create_time
 * @property string $last_time
 * @property integer $is_active
 * @property string $content
 * @property string $addi_info
 */
class AnalyseHtmlRole extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hc_analyse_html_role';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform', 'name', 'create_time', 'last_time', 'is_active', 'content', 'addi_info'], 'required'],
            [['create_time', 'last_time'], 'safe'],
            [['is_active'], 'integer'],
            [['content', 'addi_info'], 'string'],
            [['platform'], 'string', 'max' => 30],
            [['name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'name' => 'Name',
            'create_time' => 'Create Time',
            'last_time' => 'Last Time',
            'is_active' => 'Is Active',
            'content' => 'Content',
            'addi_info' => 'Addi Info',
        ];
    }
}
