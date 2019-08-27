<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_global_pdf_info".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $total_size
 * @property integer $pdf_number
 * @property string $library_size
 */
class GlobalPdfInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_global_pdf_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'total_size', 'pdf_number'], 'required'],
            [['puid', 'total_size', 'pdf_number', 'library_size'], 'integer']
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
            'total_size' => 'Total Size',
            'pdf_number' => 'Pdf Number',
            'library_size' => 'Library Size',
        ];
    }
}
