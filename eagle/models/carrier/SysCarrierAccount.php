<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_carrier_account".
 *
 * @property string $id
 * @property string $carrier_code
 * @property string $carrier_name
 * @property integer $carrier_type
 * @property string $api_params
 * @property string $create_time
 * @property string $update_time
 * @property string $user_id
 * @property integer $is_used
 * @property string $address
 */
class SysCarrierAccount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_carrier_account';
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
            [['carrier_code'], 'required','message' => '请选择物流商！'],
        	[['carrier_type'], 'required','message' => '物流商类型数据错误！'],
        	[['user_id'], 'required'],
        	[['is_used'], 'required','message' => '请选择是否启用！'],
        	[['carrier_name'], 'required','message' => '请填写账号昵称！'],
            [['carrier_type', 'create_time', 'update_time', 'user_id', 'is_used'], 'integer'],
            [['carrier_code'], 'string', 'max' => 50],
            [['carrier_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'carrier_code' => 'Carrier Code',
            'carrier_name' => 'Carrier Name',
            'carrier_type' => 'Carrier Type',
            'api_params' => 'Api Params',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'user_id' => 'User ID',
            'is_used' => 'Is Used',
            'address' => 'Address',
        ];
    }
}
