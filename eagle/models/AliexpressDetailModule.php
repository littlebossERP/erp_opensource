<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_category".
 *
 * @property integer $cateid
 * @property integer $pid
 * @property integer $level
 * @property string $name_zh
 * @property string $name_en
 * @property string $isleaf
 * @property string $attribute
 * @property integer $created
 * @property integer $updated
 */
class AliexpressDetailModule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_detail_module';
    }
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
            [['id'], 'required']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Id',
            'display_content' => 'DisplayContent',
            'module_contents' => 'ModuleContents',
            'status' => 'Status',
            'name' => 'Name',
            'updated' => 'Updated',
            'type' => 'Type',
            'ali_member_id' => 'AliMemberId',
            'sellerloginid'=>'Sellerloginid'
        ];
    }
}
