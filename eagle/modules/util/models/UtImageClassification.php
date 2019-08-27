<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_image_classification".
 *
 * @property integer $ID
 * @property string $name
 * @property string $operation
 */
class UtImageClassification extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_image_classification';
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
            [['name'], 'required'],
            [['name'], 'string', 'max' => 50],
            [['operation'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'name' => 'Name',
            'operation' => 'Operation',
        ];
    }
    
    /**
     * 返回包含自己的所有子节点
     * @param 父节点 $parent
     * @return Ambigous <string>
     */
    public static function getAllson($parent){
    	$all="";

    	$sonarr=self::find()->select(["ID"])->where(["parentID"=>$parent])->asArray()->all();
    	
    	if(!empty($sonarr)){
    		$all.=$parent.",";
    		foreach ($sonarr as $sonarrone){
    			$all.=$sonarrone['ID'].",";
    			$son_all=self::getAllson($sonarrone['ID']);
    			if(!empty($son_all))
    				$all.=$son_all;
    		}
    	}
    	
    	return $all;
    }
}
