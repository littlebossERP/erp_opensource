<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_auto_roles".
 *
 * @property integer $id
 * @property string $name
 * @property string $platform
 * @property string $accounts
 * @property string $nations
 * @property string $status
 * @property integer $template_id
 * @property integer $priority
 */
class AutoRoles extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_auto_roles';
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
            [['name', 'platform', 'accounts', 'status', 'template_id'], 'required'],
            [['nations'], 'string'],
            [['template_id', 'priority'], 'integer'],
            [['name'], 'string', 'max' => 200],
            [['platform'], 'string', 'max' => 30],
            [['accounts', 'status'], 'string', 'max' => 500]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'platform' => 'Platform',
            'accounts' => 'Accounts',
            'nations' => 'Nations',
            'status' => 'Status',
            'template_id' => 'Template ID',
            'priority' => 'Priority',
        ];
    }
}
