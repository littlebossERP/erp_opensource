<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_ebay_vip".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $selleruserid
 * @property string $vip_type
 * @property integer $vip_rank
 * @property integer $valid_period
 * @property integer $create_time
 * @property integer $update_time
 */
class SaasEbayVip extends \yii\db\ActiveRecord
{
    public static $vipRank= array(0=>20,1=>1000);

    public static $vipTypeRank = array(
                                    'inventory' => array(0=>20,1=>1000),
                                    'timer_listing'=> array(0=>5,1=>1000),
                                    );
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_ebay_vip';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'selleruserid'], 'required'],
            [['puid', 'vip_rank', 'valid_period', 'create_time', 'update_time'], 'integer'],
            [['selleruserid'], 'string', 'max' => 50],
            [['vip_type'], 'string', 'max' => 32]
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
            'selleruserid' => 'Selleruserid',
            'vip_type' => 'Vip Type',
            'vip_rank' => 'Vip Rank',
            'valid_period' => 'Valid Period',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
