<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "warehouse_matching_rule".
 *
 * @property string $id
 * @property string $rule_name
 * @property string $rules
 * @property string $warehouse_id
 * @property integer $priority
 * @property integer $is_active
 * @property integer $created
 * @property integer $updated
 */
class WarehouseMatchingRule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'warehouse_matching_rule';
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
            [['rule_name'], 'required'],
            [['rules'], 'string'],
            [['warehouse_id', 'priority', 'is_active', 'created', 'updated'], 'integer'],
            [['rule_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'rule_name' => 'Rule Name',
            'rules' => 'Rules',
            'warehouse_id' => 'Warehouse ID',
            'priority' => 'Priority',
            'is_active' => 'Is Active',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
