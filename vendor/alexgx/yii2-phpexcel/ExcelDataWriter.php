<?php

namespace alexgx\phpexcel;

use yii\helpers\ArrayHelper;

class ExcelDataWriter extends \yii\base\Object
{
    /**
     * @var \PHPExcel_Worksheet
     */
    public $sheet;

    /**
     * @var array
     */
    public $data = [];

    /**
     * @var array
     */
    public $columns = [];

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var string
     */
    public $defaultDateFormat = \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY;

    /**
     * @var
     */
    protected $j;

    /**
     *
     */
    public function write()
    {
        if (!is_array($this->data) || !is_array($this->columns)) {
            return;
        }

        $this->j = 1;

        $this->writeHeaderRow();
        $this->writeDataRows();
        $this->writeFooterRow();
    }

    protected function writeHeaderRow()
    {
        $i = 0;
        foreach ($this->columns as $column) {
            if (isset($column['header'])) {
                $this->sheet->setCellValueByColumnAndRow($i, $this->j, $column['header']);
            }
            if (isset($column['headerStyles'])) {
                $this->sheet->getStyleByColumnAndRow($i, $this->j)->applyFromArray($column['headerStyles']);
            }
            if (isset($column['width'])) {
                $this->sheet->getColumnDimensionByColumn($i)->setWidth($column['width']);
            }
            ++$i;
        }
        ++$this->j;
    }

    protected function writeDataRows()
    {
        foreach ($this->data as $key => $row) {
            $i = 0;
            if (isset($this->options['rowOptions']) && $this->options['rowOptions'] instanceof \Closure) {
                $rowOptions = call_user_func($this->options['rowOptions'], $row, $key);
            }
            foreach ($this->columns as $column) {
                if (isset($rowOptions)) {
                    $column = ArrayHelper::merge($column, $rowOptions);
                }
                if (isset($column['cellOptions']) && $column['cellOptions'] instanceof \Closure) {
                    $column = ArrayHelper::merge($column, call_user_func($column['cellOptions'], $row, $key, $i, $this->j));
                }
                $value = null;
                if (isset($column['value'])) {
                    $value = ($column['value'] instanceof \Closure) ? call_user_func($column['value'], $row, $key) : $column['value'];
                } elseif (isset($column['attribute']) && isset($row[$column['attribute']])) {
                    $value = $row[$column['attribute']];
                }
                $this->writeCell($value, $i, $this->j, $column);
                ++$i;
            }
            ++$this->j;
        }
    }

    protected function writeFooterRow()
    {
        $i = 0;
        foreach ($this->columns as $column) {
            // footer config
            $config = [];
            if (isset($column['footerStyles'])) {
                $config['styles'] = $column['footerStyles'];
            }
            if (isset($column['footerType'])) {
                $config['type'] = $column['footerType'];
            }
            if (isset($column['footerLabel'])) {
                $config['label'] = $column['footerLabel'];
            }
            if (isset($column['footerOptions']) && $column['footerOptions'] instanceof \Closure) {
                $config = ArrayHelper::merge($config, call_user_func($column['footerOptions'], null, null, $i, $this->j));
            }
            $value = null;
            if (isset($column['footer'])) {
                $value = ($column['footer'] instanceof \Closure) ? call_user_func($column['footer'], null, null) : $column['footer'];
            }
            $this->writeCell($value, $i, $this->j, $config);
            ++$i;
        }
        ++$this->j;
    }

    protected function writeCell($value, $column, $row, $config)
    {
        // auto type
        if (!isset($config['type']) || $config['type'] === null) {
            $this->sheet->setCellValueByColumnAndRow($column, $row, $value);
        } elseif ($config['type'] === 'date') {
            if (!is_int($value)) {
                $timestamp = strtotime($value);
            }
            $this->sheet->SetCellValueByColumnAndRow($column, $row, \PHPExcel_Shared_Date::PHPToExcel($timestamp));
            if (!isset($config['styles']['numberformat']['code'])) {
                $config['styles']['numberformat']['code'] = $this->defaultDateFormat;
            }
        } elseif ($config['type'] === 'url') {
            if (isset($config['label'])) {
                if ($config['label'] instanceof \Closure) {
                    // NOTE: calculate label on top level
                    $label = call_user_func($config['label']/*, TODO */);
                } else {
                    $label = $config['label'];
                }
            } else {
                $label = $value;
            }
            $urlValid = (filter_var($value, FILTER_VALIDATE_URL) !== false);
            if (!$urlValid) {
                $label = '';
            }
            $this->sheet->setCellValueByColumnAndRow($column, $row, $label);
            if ($urlValid) {
                $this->sheet->getCellByColumnAndRow($column, $row)->getHyperlink()->setUrl($value);
            }
        } else {
            $this->sheet->setCellValueExplicitByColumnAndRow($column, $row, $value, $config['type']);
        }
        if (isset($config['styles'])) {
            $this->sheet->getStyleByColumnAndRow($column, $row)->applyFromArray($config['styles']);
        }
    }
}
