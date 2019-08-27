<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_warehouse_cover_nation".
 *
 * @property integer $id
 * @property string $nation
 * @property integer $warehouse_id
 * @property integer $priority
 */
class WarehouseCoverNation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_warehouse_cover_nation';
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
            [['nation', 'warehouse_id'], 'required'],
            [['warehouse_id', 'priority'], 'integer'],
            [['nation'], 'string', 'max' => 2],
            [['nation', 'warehouse_id'], 'unique', 'targetAttribute' => ['nation', 'warehouse_id'], 'message' => 'The combination of Nation and Warehouse ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nation' => 'Nation',
            'warehouse_id' => 'Warehouse ID',
            'priority' => 'Priority',
        ];
    }
}
