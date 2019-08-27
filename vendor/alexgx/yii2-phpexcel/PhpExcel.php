<?php

namespace alexgx\phpexcel;

/**
 * Class PhpExcel
 */
class PhpExcel extends \yii\base\Object
{
    /**
     * @var string
     */
    public $defaultFormat = 'Excel2007';

    /**
     * Creates new workbook
     * @return \PHPExcel
     */
    public function create()
    {
        return new \PHPExcel();
    }

    /**
     * @param string $filename name of the spreadsheet file
     * @return \PHPExcel
     */
    public function load($filename)
    {
        return \PHPExcel_IOFactory::load($filename);
    }

    /**
     * @param \PHPExcel $object
     * @param string $name attachment name
     * @param string $format output format
     */
    public function responseFile(\PHPExcel $object, $filename, $format = null)
    {
        if ($format === null) {
            $format = $this->resolveFormat($filename);
        }
        $writer = \PHPExcel_IOFactory::createWriter($object, $format);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();
        \Yii::$app->response->sendContentAsFile($content, $filename, $this->resolveMime($format));
        \Yii::$app->end();
    }

    /**
     * @param $sheet
     * @param $config
     */
    public function writeSheetData($sheet, $data, $config)
    {
        $config['sheet'] = &$sheet;
        $config['data'] = $data;
        $writer = new ExcelDataWriter($config);
        $writer->write();
        return $sheet;
    }

    public function writeTemplateData(/* TODO */)
    {
        // TODO: implement
    }

    public function readSheetData($sheet, $config)
    {
        // TODO: implement
    }

    /**
     *
     * @param $format
     * @return string
     */
    protected function resolveMime($format)
    {
        $list = [
            'CSV' => 'text/csv',
            'HTML' => 'text/html',
            'PDF' => 'application/pdf',
            'OpenDocument' => 'application/vnd.oasis.opendocument.spreadsheet',
            'Excel5' => 'application/vnd.ms-excel',
            'Excel2007' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return isset($list[$format]) ? $list[$format] : 'application/octet-stream';
    }

    /**
     *
     * @param $filename
     * @return string
     */
    protected function resolveFormat($filename)
    {
        // see IOFactory::createReaderForFile etc.
        $list = [
            'ods' => 'OpenDocument',
            'xls' => 'Excel5',
            'xlsx' => 'Excel2007',
            'csv' => 'CSV',
            'pdf' => 'PDF',
            'html' => 'HTML',
        ];
        // TODO: check strtolower
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return isset($list[$extension]) ? $list[$extension] : $this->defaultFormat;
    }
}
