<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "base_fitmentmuban".
 *
 * @property string $id
 * @property integer $siteid
 * @property integer $primarycategory
 * @property string $name
 * @property string $itemcompatibilitylist
 * @property integer $created
 * @property integer $updated
 */
class BaseFitmentmuban extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'base_fitmentmuban';
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
            [['siteid', 'primarycategory', 'created', 'updated'], 'integer'],
            [['name', 'itemcompatibilitylist', 'created'], 'required'],
//            [['itemcompatibilitylist'], 'string'],
            [['name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'siteid' => 'Siteid',
            'primarycategory' => 'Primarycategory',
            'name' => 'Name',
            'itemcompatibilitylist' => 'Itemcompatibilitylist',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
    
    //序列化
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('itemcompatibilitylist'),
    			)
    	);
    }
}
