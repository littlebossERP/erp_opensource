<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_promotion".
 *
 * @property string $id
 * @property string $selleruserid
 * @property string $promotionalsaleid
 * @property string $promotionalsalename
 * @property string $action
 * @property string $discounttype
 * @property double $discountvalue
 * @property integer $promotionalsaleendtime
 * @property integer $promotionalsalestarttime
 * @property string $promotionalsaletype
 * @property string $status
 * @property integer $created
 * @property integer $updated
 */
class EbayPromotion extends \yii\db\ActiveRecord
{
	public static $promotiontype = [
		'FreeShippingOnly'=>'免运费(第一运输)',
		'PriceDiscountOnly'=>'价格优惠',
		'PriceDiscountAndFreeShipping'=>'价格优惠且免运费(第一运输)'
	];
	
	public static $discounttype = [
		'Price'=>'金额折扣',
		'Percentage'=>'比例折扣'
	];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_promotion';
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
            [['discountvalue'], 'number'],
            [['promotionalsaleendtime', 'promotionalsalestarttime', 'created', 'updated'], 'integer'],
            [['selleruserid'], 'string', 'max' => 100],
            [['promotionalsaleid', 'action', 'discounttype', 'promotionalsaletype', 'status'], 'string', 'max' => 50],
            [['promotionalsalename'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleruserid' => 'Selleruserid',
            'promotionalsaleid' => 'Promotionalsaleid',
            'promotionalsalename' => 'Promotionalsalename',
            'action' => 'Action',
            'discounttype' => 'Discounttype',
            'discountvalue' => 'Discountvalue',
            'promotionalsaleendtime' => 'Promotionalsaleendtime',
            'promotionalsalestarttime' => 'Promotionalsalestarttime',
            'promotionalsaletype' => 'Promotionalsaletype',
            'status' => 'Status',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
