<?php

namespace eagle\modules\delivery\models;

use Yii;

/**
 * This is the model class for table "od_delivery".
 *
 * @property integer $id
 * @property string $deliveryid
 * @property string $creater
 * @property integer $ordercount
 * @property integer $skucount
 * @property integer $jianhuo_status
 * @property integer $peihuo_status
 */
class OdDelivery extends \yii\db\ActiveRecord
{

    CONST PICKING_PRINT_NO =0;
    CONST PICKING_PRINT_ALREADY = 1;
    CONST PICKING_ALREADY = 2;

    static $pickingStatus = [
        self::PICKING_PRINT_NO =>'未打印',
        self::PICKING_PRINT_ALREADY =>'已打印',
        self::PICKING_ALREADY =>'拣货完成',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_delivery';
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
            [['deliveryid'], 'required'],
            [['deliveryid', 'ordercount', 'skucount', 'jianhuo_status', 'peihuo_status'], 'integer'],
            [['creater'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'deliveryid' => 'Deliveryid',
            'creater' => 'Creater',
            'ordercount' => 'Ordercount',
            'skucount' => 'Skucount',
            'jianhuo_status' => 'Jianhuo Status',
            'peihuo_status' => 'Peihuo Status',
        ];
    }
}
