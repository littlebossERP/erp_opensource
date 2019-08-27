<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lt_customized_page_prod".
 *
 * @property string $id
 * @property string $page_id
 * @property string $prod_id
 * @property string $puid
 * @property integer $priority
 * @property string $addi_info
 */
class LtCustomizedPageProd extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_customized_page_prod';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['page_id', 'prod_id', 'puid', 'priority'], 'required'],
            [['page_id', 'prod_id', 'puid', 'priority'], 'integer'],
            [['addi_info'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'page_id' => 'Page ID',
            'prod_id' => 'Prod ID',
            'puid' => 'Puid',
            'priority' => 'Priority',
            'addi_info' => 'Addi Info',
        ];
    }
}
