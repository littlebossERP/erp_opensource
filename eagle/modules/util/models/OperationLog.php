<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "operation_log".
 *
 * @property integer $id
 * @property string $log_key
 * @property string $log_type
 * @property string $log_operation
 * @property string $capture_user_name
 * @property string $update_time
 * @property string $comment
 */
class OperationLog extends \yii\db\ActiveRecord
{
	static $ordertype=[
		'修改订单'=>'修改订单',
		'创建订单'=>'创建订单',
		'取消订单'=>'取消订单',
		'合并订单'=>'合并订单',
		'拆分订单'=>'拆分订单',
		'检测订单'=>'检测订单',
		'添加备注'=>'添加备注',
		'移动订单'=>'移动订单',
		'重新发货'=>'重新发货',
		'暂停发货'=>'暂停发货'
	];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'operation_log_v2';
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
            [['log_type'], 'required'],
            [['log_type'], 'string'],
            [['update_time'], 'safe'],
            [['log_key', 'log_operation'], 'string', 'max' => 45],
            [['capture_user_name'], 'string', 'max' => 50],
            [['comment'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'log_key' => 'Log Key',
            'log_type' => 'Log Type',
            'log_operation' => 'Log Operation',
            'capture_user_name' => 'Capture User Name',
            'update_time' => 'Update Time',
            'comment' => 'Comment',
        ];
    }
}
