<?php

namespace eagle\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "ebay_specific".
 *
 * @property integer $id
 * @property integer $categoryid
 * @property integer $siteid
 * @property string $name
 * @property integer $maxvalue
 * @property integer $minvalue
 * @property string $selectionmode
 * @property string $relationship
 * @property string $value
 * @property integer $record_updatetime
 * @property string $specifics_jobid
 * @property string $variationspecifics
 */
class EbaySpecific extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_specific';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['categoryid', 'siteid', 'maxvalue', 'minvalue', 'record_updatetime'], 'integer'],
            [['relationship', 'value'], 'string'],
            [['name'], 'string', 'max' => 100],
            [['selectionmode'], 'string', 'max' => 50],
            [['specifics_jobid'], 'string', 'max' => 64],
            [['variationspecifics'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'categoryid' => 'Categoryid',
            'siteid' => 'Siteid',
            'name' => 'Name',
            'maxvalue' => 'Maxvalue',
            'minvalue' => 'Minvalue',
            'selectionmode' => 'Selectionmode',
            'relationship' => 'Relationship',
            'value' => 'Value',
            'record_updatetime' => 'Record Updatetime',
            'specifics_jobid' => 'Specifics Jobid',
            'variationspecifics' => 'Variationspecifics',
        ];
    }
    public function behaviors(){
        return array(
                'SerializeBehavior' => array(
                        'class' => SerializeBehavior::className(),
                        'serialAttributes' => array('value','relationship'),
                )
        );
    }
}
