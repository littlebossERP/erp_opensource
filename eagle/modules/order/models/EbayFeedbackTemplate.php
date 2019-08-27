<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "cm_ebay_template".
 *
 * @property string $id
 * @property integer $template_type
 * @property string $template
 * @property integer $create_time
 * @property integer $update_time
 */
class EbayFeedbackTemplate extends \yii\db\ActiveRecord
{
	/**
	 * template_type代表的评价类型
	 */
	const Positive='1';
	const Neutral='2';
	const Negative='3';
	
	static $type=[
		'1'=>'Positive',	//好评
		'2'=>'Neutral',		//中评
		'3'=>'Negative',	//差评
	];
	
	static $typeval=[
		'Positive'=>'好评',
		'Neutral'=>'中评',
		'Negative'=>'差评',
	];
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_template';
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
            [['template_type', 'create_time', 'update_time'], 'required'],
            [['template_type', 'create_time', 'update_time'], 'integer'],
            [['template'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_type' => 'Template Type',
            'template' => 'Template',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
