<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cm_ebay_usercase_ebpdetail".
 *
 * @property integer $id
 * @property string $caseid
 * @property string $agreedrefundamount
 * @property string $appeal
 * @property string $buyerreturnshipment
 * @property string $casedocumentinfo
 * @property string $decision
 * @property string $decisiondate
 * @property string $decisionreason
 * @property string $decisionreasondetail
 * @property string $detailstatus
 * @property string $detailstatusinfo
 * @property string $fvfcredited
 * @property string $globalid
 * @property string $initialbuyerexpectation
 * @property string $initialbuyerexpectationdetail
 * @property string $notcountedinbuyerprotectioncases
 * @property string $openreason
 * @property string $paymentdetail
 * @property string $responsehistory
 * @property string $returnmerchandiseauthorization
 * @property string $sellershipment
 * @property integer $create_time
 * @property integer $update_time
 */
class CmEbayUsercaseEbpdetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_usercase_ebpdetail';
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
//             [['caseid', 'create_time', 'update_time'], 'integer'],
//             [['appeal', 'buyerreturnshipment', 'casedocumentinfo', 'decisionreasondetail', 'detailstatusinfo', 'initialbuyerexpectationdetail', 'paymentdetail', 'responsehistory', 'sellershipment'], 'string'],
//             [['agreedrefundamount'], 'string', 'max' => 20],
//             [['decision', 'decisiondate', 'decisionreason', 'detailstatus', 'globalid', 'initialbuyerexpectation', 'openreason'], 'string', 'max' => 100],
//             [['fvfcredited', 'notcountedinbuyerprotectioncases'], 'string', 'max' => 10],
//             [['returnmerchandiseauthorization'], 'string', 'max' => 255],
//             [['caseid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'caseid' => 'Caseid',
            'agreedrefundamount' => 'Agreedrefundamount',
            'appeal' => 'Appeal',
            'buyerreturnshipment' => 'Buyerreturnshipment',
            'casedocumentinfo' => 'Casedocumentinfo',
            'decision' => 'Decision',
            'decisiondate' => 'Decisiondate',
            'decisionreason' => 'Decisionreason',
            'decisionreasondetail' => 'Decisionreasondetail',
            'detailstatus' => 'Detailstatus',
            'detailstatusinfo' => 'Detailstatusinfo',
            'fvfcredited' => 'Fvfcredited',
            'globalid' => 'Globalid',
            'initialbuyerexpectation' => 'Initialbuyerexpectation',
            'initialbuyerexpectationdetail' => 'Initialbuyerexpectationdetail',
            'notcountedinbuyerprotectioncases' => 'Notcountedinbuyerprotectioncases',
            'openreason' => 'Openreason',
            'paymentdetail' => 'Paymentdetail',
            'responsehistory' => 'Responsehistory',
            'returnmerchandiseauthorization' => 'Returnmerchandiseauthorization',
            'sellershipment' => 'Sellershipment',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
