<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "matching_rule".
 *
 * @property string $id
 * @property string $uid
 * @property string $operator
 * @property string $rule_name
 * @property string $rules
 * @property string $source
 * @property string $site
 * @property string $selleruserid
 * @property string $buyer_transportation_service
 * @property string $warehouse
 * @property string $receiving_country
 * @property string $total_amount
 * @property string $freight_amount
 * @property string $total_weight
 * @property string $product_tag
 * @property string $transportation_service_id
 * @property string $priority
 * @property integer $is_active
 * @property integer $created
 * @property integer $updated
 */
class MatchingRule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'matching_rule';
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
            [['uid', 'operator', 'created', 'updated'], 'required'],
        	[['rule_name'], 'required','message' => '规则名必填！'],
        	[['transportation_service_id'], 'required','message' => '请选择运输服务！'],
        	[['priority'], 'required','message' => '优先级必填,数字越大优先级越低！'],
        	[['is_active'], 'required','message' => '请选择是否启用！'],
            [['uid', 'operator', 'transportation_service_id', 'priority', 'is_active', 'created', 'updated'], 'integer'],
            //[['rules', 'source', 'site', 'selleruserid', 'buyer_transportation_service', 'warehouse', 'receiving_country', 'total_amount', 'freight_amount', 'total_weight', 'product_tag'], 'string'],
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
            'uid' => 'Uid',
            'operator' => 'Operator',
            'rule_name' => 'Rule Name',
            'rules' => 'Rules',
            'source' => 'Source',
            'site' => 'Site',
            'selleruserid' => 'Selleruserid',
            'buyer_transportation_service' => 'Buyer Transportation Service',
            'warehouse' => 'Warehouse',
            'receiving_country' => 'Receiving Country',
            'total_amount' => 'Total Amount',
            'freight_amount' => 'Freight Amount',
            'total_weight' => 'Total Weight',
            'product_tag' => 'Product Tag',
            'transportation_service_id' => 'Transportation Service ID',
            'priority' => 'Priority',
            'is_active' => 'Is Active',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
