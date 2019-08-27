<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "app_user_statistic".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $key
 * @property string $update_time
 * @property string $url_path
 * @property integer $visit_count
 * @property string $is_landpage_statistic
 * @property string $visit_date
 */
class AppUserStatistic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_user_statistic';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'key', 'url_path', 'visit_count', 'is_landpage_statistic', 'visit_date'], 'required'],
            [['puid', 'visit_count'], 'integer'],
            [['update_time', 'visit_date'], 'safe'],
            [['key'], 'string', 'max' => 50],
            [['url_path'], 'string', 'max' => 255],
            [['is_landpage_statistic'], 'string', 'max' => 1]
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
            'key' => 'Key',
            'update_time' => 'Update Time',
            'url_path' => 'Url Path',
            'visit_count' => 'Visit Count',
            'is_landpage_statistic' => 'Is Landpage Statistic',
            'visit_date' => 'Visit Date',
        ];
    }
}
