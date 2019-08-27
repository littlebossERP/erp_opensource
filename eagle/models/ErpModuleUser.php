<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "erp_module_user".
 *
 * @property string $id
 * @property integer $puid
 * @property string $module_type
 * @property string $platform_type
 * @property integer $limit_cnt
 * @property integer $cnt
 * @property integer $create_time
 * @property integer $update_time
 */
class ErpModuleUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'erp_module_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'limit_cnt', 'cnt', 'create_time', 'update_time'], 'integer'],
            [['module_type'], 'string', 'max' => 100],
            [['platform_type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'module_type' => 'Module Type',
            'platform_type' => 'Platform Type',
            'limit_cnt' => 'Limit Cnt',
            'cnt' => 'Cnt',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
