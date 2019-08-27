<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_category".
 *
 * @property integer $id
 * @property integer $categoryid
 * @property string $name
 * @property integer $pid
 * @property integer $level
 * @property integer $leaf
 * @property integer $siteid
 * @property integer $variationenabled
 * @property integer $version
 * @property integer $iscompatibility
 * @property string $compatibilityname
 * @property integer $islsd
 * @property integer $bestofferenabled
 * @property integer $record_updatetime
 * @property integer $autopayenable
 * @property integer $orpa
 * @property integer $orra
 * @property integer $virtual
 * @property integer $expired
 * @property integer $feature_version
 * @property string $specifics_jobid
 */
class EbayCategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['categoryid', 'pid', 'level', 'leaf', 'siteid', 'variationenabled', 'version', 'iscompatibility', 'islsd', 'bestofferenabled', 'record_updatetime', 'autopayenable', 'orpa', 'orra', 'virtual', 'expired', 'feature_version'], 'integer'],
            [['name'], 'string', 'max' => 384],
            [['compatibilityname'], 'string', 'max' => 255],
            [['specifics_jobid'], 'string', 'max' => 64]
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
            'name' => 'Name',
            'pid' => 'Pid',
            'level' => 'Level',
            'leaf' => 'Leaf',
            'siteid' => 'Siteid',
            'variationenabled' => 'Variationenabled',
            'version' => 'Version',
            'iscompatibility' => 'Iscompatibility',
            'compatibilityname' => 'Compatibilityname',
            'islsd' => 'Islsd',
            'bestofferenabled' => 'Bestofferenabled',
            'record_updatetime' => 'Record Updatetime',
            'autopayenable' => 'Autopayenable',
            'orpa' => 'Orpa',
            'orra' => 'Orra',
            'virtual' => 'Virtual',
            'expired' => 'Expired',
            'feature_version' => 'Feature Version',
            'specifics_jobid' => 'Specifics Jobid',
        ];
    }

    ///////////////////////////////////////自定义函数/////////////  
    /**
     * 通过一个类目的ID，取的这个类目的倒数第二根类的ID
     * @author fanjs
     * */
    static function getRootcategoryid($categoryid,$siteid){
        $node=self::find()->where('categoryid = :categoryid AND siteid = :siteid',array(':categoryid'=>$categoryid,':siteid'=>$siteid))->one();
        if ($node->level>2){
            $p=self::find()->where('categoryid = :categoryid AND level=:level AND siteid = :siteid',array(':categoryid'=>$node->pid,':level'=>$node->level-1,':siteid'=>$siteid))->one();
            return self::getRootcategoryid($p->pid, $siteid);
        }else{
            return $categoryid;
        }
    }
    
    /**
     * 构建类目的具体路径
     * @author fanjs
     */
    static function getPath($node,$path='',$siteid=0){
        if($node->level>1){
            $p=self::find()->where('categoryid=:categoryid And level=:level And siteid=:siteid',array(':categoryid'=>$node->pid,':level'=>$node->level-1,':siteid'=>$siteid))->one();
            return self::getPath($p,$p->name.' -> '.$path,$siteid);
        }else{
            return $path;
        }
    }
}
