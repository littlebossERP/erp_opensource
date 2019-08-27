<?php
/**
 * This file is part of FPDI
 *
 * @package   FPDI
 * @copyright Copyright (c) 2015 Setasign - Jan Slabon (http://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 * @version   1.6.1
 */

/**
* PLEASE NOTE THAT THIS FILE IS PROCESSED PROGRAMMATICALLY FOR THE itbz\fpdi
* RELEASE BUG REPORTS AND SUGGESTED CHANGES SHOULD BE DIRECTED TO SETASIGN
* DIRECTLY BUGS RELATED TO THIS CONVERSION CAN BE REPORTED AT
* https://github.com/hanneskod/fpdi/issues
*/

namespace fpdi {
    if (!class_exists('FPDF_TPL')) {
    }
    class FPDI extends \fpdi\FPDF_TPL
    {
        const VERSION = '1.6.1';
        public $currentFilename;
        public $parsers = array();
        public $currentParser;
        public $lastUsedPageBox;
        protected $_objStack;
        protected $_doneObjStack;
        protected $_currentObjId;
        protected $_importedPages = array();
        public function setSourceFile($filename)
        {
            $_filename = realpath($filename);
            if (false !== $_filename) {
                $filename = $_filename;
            }
            $this->currentFilename = $filename;
            if (!isset($this->parsers[$filename])) {
                $this->parsers[$filename] = $this->_getPdfParser($filename);
                $this->setPdfVersion(max($this->getPdfVersion(), $this->parsers[$filename]->getPdfVersion()));
            }
            $this->currentParser = $this->parsers[$filename];
            return $this->parsers[$filename]->getPageCount();
        }
        protected function _getPdfParser($filename)
        {
            if (!class_exists('fpdi_pdf_parser')) {
            }
            return new \fpdi\fpdi_pdf_parser($filename);
        }
        public function getPdfVersion()
        {
            return $this->PDFVersion;
        }
        public function setPdfVersion($version = '1.3')
        {
            $this->PDFVersion = sprintf('%.1F', $version);
        }
        public function importPage($pageNo, $boxName = 'CropBox', $groupXObject = true)
        {
            if ($this->_inTpl) {
                throw new \LogicException('Please import the desired pages before creating a new template.');
            }
            $fn = $this->currentFilename;
            $boxName = '/' . ltrim($boxName, '/');
            $pageKey = $fn . '-' . (int) $pageNo . $boxName;
            if (isset($this->_importedPages[$pageKey])) {
                return $this->_importedPages[$pageKey];
            }
            $parser = $this->parsers[$fn];
            $parser->setPageNo($pageNo);
            if (!in_array($boxName, $parser->availableBoxes)) {
                throw new \InvalidArgumentException(sprintf('Unknown box: %s', $boxName));
            }
            $pageBoxes = $parser->getPageBoxes($pageNo, $this->k);
            if (!isset($pageBoxes[$boxName]) && ($boxName == '/BleedBox' || $boxName == '/TrimBox' || $boxName == '/ArtBox')) {
                $boxName = '/CropBox';
            }
            if (!isset($pageBoxes[$boxName]) && $boxName == '/CropBox') {
                $boxName = '/MediaBox';
            }
            if (!isset($pageBoxes[$boxName])) {
                return false;
            }
            $this->lastUsedPageBox = $boxName;
            $box = $pageBoxes[$boxName];
            $this->tpl++;
            $this->_tpls[$this->tpl] = array();
            $tpl =& $this->_tpls[$this->tpl];
            $tpl['parser'] = $parser;
            $tpl['resources'] = $parser->getPageResources();
            $tpl['buffer'] = $parser->getContent();
            $tpl['box'] = $box;
            $tpl['groupXObject'] = $groupXObject;
            if ($groupXObject) {
                $this->setPdfVersion(max($this->getPdfVersion(), 1.4));
            }
            $this->_tpls[$this->tpl] = array_merge($this->_tpls[$this->tpl], $box);
            $tpl['x'] = 0;
            $tpl['y'] = 0;
            $rotation = $parser->getPageRotation($pageNo);
            $tpl['_rotationAngle'] = 0;
            if (isset($rotation[1]) && ($angle = $rotation[1] % 360) != 0) {
                $steps = $angle / 90;
                $_w = $tpl['w'];
                $_h = $tpl['h'];
                $tpl['w'] = $steps % 2 == 0 ? $_w : $_h;
                $tpl['h'] = $steps % 2 == 0 ? $_h : $_w;
                if ($angle < 0) {
                    $angle += 360;
                }
                $tpl['_rotationAngle'] = $angle * -1;
            }
            $this->_importedPages[$pageKey] = $this->tpl;
            return $this->tpl;
        }
        public function getLastUsedPageBox()
        {
            return $this->lastUsedPageBox;
        }
        public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0, $adjustPageSize = false)
        {
            if ($adjustPageSize == true && is_null($x) && is_null($y)) {
                $size = $this->getTemplateSize($tplIdx, $w, $h);
                $orientation = $size['w'] > $size['h'] ? 'L' : 'P';
                $size = array($size['w'], $size['h']);
                if (is_subclass_of($this, '\\TCPDF')) {
                    $this->setPageFormat($size, $orientation);
                } else {
                    $size = $this->_getpagesize($size);
                    if ($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
                        if ($orientation == 'P') {
                            $this->w = $size[0];
                            $this->h = $size[1];
                        } else {
                            $this->w = $size[1];
                            $this->h = $size[0];
                        }
                        $this->wPt = $this->w * $this->k;
                        $this->hPt = $this->h * $this->k;
                        $this->PageBreakTrigger = $this->h - $this->bMargin;
                        $this->CurOrientation = $orientation;
                        $this->CurPageSize = $size;
                        if (FPDF_VERSION >= 1.8) {
                            $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
                        } else {
                            $this->PageSizes[$this->page] = array($this->wPt, $this->hPt);
                        }
                    }
                }
            }
            $this->_out('q 0 J 1 w 0 j 0 G 0 g');
            $size = parent::useTemplate($tplIdx, $x, $y, $w, $h);
            $this->_out('Q');
            return $size;
        }
        protected function _putimportedobjects()
        {
            foreach ($this->parsers as $filename => $p) {
                $this->currentParser = $p;
                if (!isset($this->_objStack[$filename]) || !is_array($this->_objStack[$filename])) {
                    continue;
                }
                while (($n = key($this->_objStack[$filename])) !== null) {
                    try {
                        $nObj = $this->currentParser->resolveObject($this->_objStack[$filename][$n][1]);
                    } catch (\Exception $e) {
                        $nObj = array(\fpdi\pdf_parser::TYPE_OBJECT, \fpdi\pdf_parser::TYPE_NULL);
                    }
                    $this->_newobj($this->_objStack[$filename][$n][0]);
                    if ($nObj[0] == \fpdi\pdf_parser::TYPE_STREAM) {
                        $this->_writeValue($nObj);
                    } else {
                        $this->_writeValue($nObj[1]);
                    }
                    $this->_out('
endobj');
                    $this->_objStack[$filename][$n] = null;
                    unset($this->_objStack[$filename][$n]);
                    reset($this->_objStack[$filename]);
                }
            }
        }
        protected function _putformxobjects()
        {
            $filter = $this->compress ? '/Filter /FlateDecode ' : '';
            reset($this->_tpls);
            foreach ($this->_tpls as $tplIdx => $tpl) {
                $this->_newobj();
                $currentN = $this->n;
                $this->_tpls[$tplIdx]['n'] = $this->n;
                $this->_out('<<' . $filter . '/Type /XObject');
                $this->_out('/Subtype /Form');
                $this->_out('/FormType 1');
                $this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]', (isset($tpl['box']['llx']) ? $tpl['box']['llx'] : $tpl['x']) * $this->k, (isset($tpl['box']['lly']) ? $tpl['box']['lly'] : -$tpl['y']) * $this->k, (isset($tpl['box']['urx']) ? $tpl['box']['urx'] : $tpl['w'] + $tpl['x']) * $this->k, (isset($tpl['box']['ury']) ? $tpl['box']['ury'] : $tpl['h'] - $tpl['y']) * $this->k));
                $c = 1;
                $s = 0;
                $tx = 0;
                $ty = 0;
                if (isset($tpl['box'])) {
                    $tx = -$tpl['box']['llx'];
                    $ty = -$tpl['box']['lly'];
                    if ($tpl['_rotationAngle'] != 0) {
                        $angle = $tpl['_rotationAngle'] * M_PI / 180;
                        $c = cos($angle);
                        $s = sin($angle);
                        switch ($tpl['_rotationAngle']) {
                            case -90:
                                $tx = -$tpl['box']['lly'];
                                $ty = $tpl['box']['urx'];
                                break;
                            case -180:
                                $tx = $tpl['box']['urx'];
                                $ty = $tpl['box']['ury'];
                                break;
                            case -270:
                                $tx = $tpl['box']['ury'];
                                $ty = -$tpl['box']['llx'];
                                break;
                        }
                    }
                } else {
                    if ($tpl['x'] != 0 || $tpl['y'] != 0) {
                        $tx = -$tpl['x'] * 2;
                        $ty = $tpl['y'] * 2;
                    }
                }
                $tx *= $this->k;
                $ty *= $this->k;
                if ($c != 1 || $s != 0 || $tx != 0 || $ty != 0) {
                    $this->_out(sprintf('/Matrix [%.5F %.5F %.5F %.5F %.5F %.5F]', $c, $s, -$s, $c, $tx, $ty));
                }
                $this->_out('/Resources ');
                if (isset($tpl['resources'])) {
                    $this->currentParser = $tpl['parser'];
                    $this->_writeValue($tpl['resources']);
                } else {
                    $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
                    if (isset($this->_res['tpl'][$tplIdx])) {
                        $res = $this->_res['tpl'][$tplIdx];
                        if (isset($res['fonts']) && count($res['fonts'])) {
                            $this->_out('/Font <<');
                            foreach ($res['fonts'] as $font) {
                                $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
                            }
                            $this->_out('>>');
                        }
                        if (isset($res['images']) && count($res['images']) || isset($res['tpls']) && count($res['tpls'])) {
                            $this->_out('/XObject <<');
                            if (isset($res['images'])) {
                                foreach ($res['images'] as $image) {
                                    $this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
                                }
                            }
                            if (isset($res['tpls'])) {
                                foreach ($res['tpls'] as $i => $_tpl) {
                                    $this->_out($this->tplPrefix . $i . ' ' . $_tpl['n'] . ' 0 R');
                                }
                            }
                            $this->_out('>>');
                        }
                        $this->_out('>>');
                    }
                }
                if (isset($tpl['groupXObject']) && $tpl['groupXObject']) {
                    $this->_out('/Group <</Type/Group/S/Transparency>>');
                }
                $newN = $this->n;
                $this->n = $currentN;
                $buffer = $this->compress ? gzcompress($tpl['buffer']) : $tpl['buffer'];
                if (is_subclass_of($this, '\\TCPDF')) {
                    $buffer = $this->_getrawstream($buffer);
                    $this->_out('/Length ' . strlen($buffer) . ' >>');
                    $this->_out('stream
' . $buffer . '
endstream');
                } else {
                    $this->_out('/Length ' . strlen($buffer) . ' >>');
                    $this->_putstream($buffer);
                }
                $this->_out('endobj');
                $this->n = $newN;
            }
            $this->_putimportedobjects();
        }
        public function _newobj($objId = false, $onlyNewObj = false)
        {
            if (!$objId) {
                $objId = ++$this->n;
            }
            if (!$onlyNewObj) {
                $this->offsets[$objId] = is_subclass_of($this, '\\TCPDF') ? $this->bufferlen : strlen($this->buffer);
                $this->_out($objId . ' 0 obj');
                $this->_currentObjId = $objId;
            }
            return $objId;
        }
        protected function _writeValue(&$value)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                parent::_prepareValue($value);
            }
            switch ($value[0]) {
                case \fpdi\pdf_parser::TYPE_TOKEN:
                    $this->_straightOut($value[1] . ' ');
                    break;
                case \fpdi\pdf_parser::TYPE_NUMERIC:
                case \fpdi\pdf_parser::TYPE_REAL:
                    if (is_float($value[1]) && $value[1] != 0) {
                        $this->_straightOut(rtrim(rtrim(sprintf('%F', $value[1]), '0'), '.') . ' ');
                    } else {
                        $this->_straightOut($value[1] . ' ');
                    }
                    break;
                case \fpdi\pdf_parser::TYPE_ARRAY:
                    $this->_straightOut('[');
                    for ($i = 0; $i < count($value[1]); $i++) {
                        $this->_writeValue($value[1][$i]);
                    }
                    $this->_out(']');
                    break;
                case \fpdi\pdf_parser::TYPE_DICTIONARY:
                    $this->_straightOut('<<');
                    reset($value[1]);
                    while (list($k, $v) = each($value[1])) {
                        $this->_straightOut($k . ' ');
                        $this->_writeValue($v);
                    }
                    $this->_straightOut('>>');
                    break;
                case \fpdi\pdf_parser::TYPE_OBJREF:
                    $cpfn =& $this->currentParser->filename;
                    if (!isset($this->_doneObjStack[$cpfn][$value[1]])) {
                        $this->_newobj(false, true);
                        $this->_objStack[$cpfn][$value[1]] = array($this->n, $value);
                        $this->_doneObjStack[$cpfn][$value[1]] = array($this->n, $value);
                    }
                    $objId = $this->_doneObjStack[$cpfn][$value[1]][0];
                    $this->_out($objId . ' 0 R');
                    break;
                case \fpdi\pdf_parser::TYPE_STRING:
                    $this->_straightOut('(' . $value[1] . ')');
                    break;
                case \fpdi\pdf_parser::TYPE_STREAM:
                    $this->_writeValue($value[1]);
                    $this->_out('stream');
                    $this->_out($value[2][1]);
                    $this->_straightOut('endstream');
                    break;
                case \fpdi\pdf_parser::TYPE_HEX:
                    $this->_straightOut('<' . $value[1] . '>');
                    break;
                case \fpdi\pdf_parser::TYPE_BOOLEAN:
                    $this->_straightOut($value[1] ? 'true ' : 'false ');
                    break;
                case \fpdi\pdf_parser::TYPE_NULL:
                    $this->_straightOut('null ');
                    break;
            }
        }
        protected function _straightOut($s)
        {
            if (!is_subclass_of($this, '\\TCPDF')) {
                if ($this->state == 2) {
                    $this->pages[$this->page] .= $s;
                } else {
                    $this->buffer .= $s;
                }
            } else {
                if ($this->state == 2) {
                    if ($this->inxobj) {
                        $this->xobjects[$this->xobjid]['outdata'] .= $s;
                    } else {
                        if (!$this->InFooter and isset($this->footerlen[$this->page]) and $this->footerlen[$this->page] > 0) {
                            $pagebuff = $this->getPageBuffer($this->page);
                            $page = substr($pagebuff, 0, -$this->footerlen[$this->page]);
                            $footer = substr($pagebuff, -$this->footerlen[$this->page]);
                            $this->setPageBuffer($this->page, $page . $s . $footer);
                            $this->footerpos[$this->page] += strlen($s);
                        } else {
                            $this->setPageBuffer($this->page, $s, true);
                        }
                    }
                } else {
                    if ($this->state > 0) {
                        $this->setBuffer($s);
                    }
                }
            }
        }
        public function _enddoc()
        {
            parent::_enddoc();
            $this->_closeParsers();
        }
        protected function _closeParsers()
        {
            if ($this->state > 2) {
                $this->cleanUp();
                return true;
            }
            return false;
        }
        public function cleanUp()
        {
            while (($parser = array_pop($this->parsers)) !== null) {
                $parser->closeFile();
            }
        }
    }
}