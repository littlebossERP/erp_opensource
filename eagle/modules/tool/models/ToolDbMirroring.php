<?php

namespace eagle\modules\tool\models;

use Yii;

/**
 * This is the model class for table "tool_db_mirroring".
 *
 * @property integer $id
 * @property string $commit
 * @property integer $createtime
 * @property integer $alerttime
 * @property integer $old_puid
 */
class ToolDbMirroring extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tool_db_mirroring';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['commit'], 'required'],
            [['createtime', 'alerttime', 'old_puid'], 'integer'],
            [['commit'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'commit' => 'Commit',
            'createtime' => 'Createtime',
            'alerttime' => 'Alerttime',
            'old_puid' => 'Old Puid',
        ];
    }
}
