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
    if (!class_exists('fpdi_bridge')) {
    }
    class FPDF_TPL extends \fpdi\fpdi_bridge
    {
        protected $_tpls = array();
        public $tpl = 0;
        protected $_inTpl = false;
        public $tplPrefix = '/TPL';
        protected $_res = array();
        public $lastUsedTemplateData = array();
        public function beginTemplate($x = null, $y = null, $w = null, $h = null)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                throw new \LogicException('This method is only usable with FPDF. Use TCPDF methods startTemplate() instead.');
            }
            if ($this->page <= 0) {
                throw new \LogicException('You have to add at least a page first!');
            }
            if ($x == null) {
                $x = 0;
            }
            if ($y == null) {
                $y = 0;
            }
            if ($w == null) {
                $w = $this->w;
            }
            if ($h == null) {
                $h = $this->h;
            }
            $this->tpl++;
            $tpl =& $this->_tpls[$this->tpl];
            $tpl = array('o_x' => $this->x, 'o_y' => $this->y, 'o_AutoPageBreak' => $this->AutoPageBreak, 'o_bMargin' => $this->bMargin, 'o_tMargin' => $this->tMargin, 'o_lMargin' => $this->lMargin, 'o_rMargin' => $this->rMargin, 'o_h' => $this->h, 'o_w' => $this->w, 'o_FontFamily' => $this->FontFamily, 'o_FontStyle' => $this->FontStyle, 'o_FontSizePt' => $this->FontSizePt, 'o_FontSize' => $this->FontSize, 'buffer' => '', 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h);
            $this->SetAutoPageBreak(false);
            $this->h = $h;
            $this->w = $w;
            $this->_inTpl = true;
            $this->SetXY($x + $this->lMargin, $y + $this->tMargin);
            $this->SetRightMargin($this->w - $w + $this->rMargin);
            if ($this->CurrentFont) {
                $fontKey = $this->FontFamily . $this->FontStyle;
                if ($fontKey) {
                    $this->_res['tpl'][$this->tpl]['fonts'][$fontKey] =& $this->fonts[$fontKey];
                    $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
                }
            }
            return $this->tpl;
        }
        public function endTemplate()
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::endTemplate'), $args);
            }
            if ($this->_inTpl) {
                $this->_inTpl = false;
                $tpl = $this->_tpls[$this->tpl];
                $this->SetXY($tpl['o_x'], $tpl['o_y']);
                $this->tMargin = $tpl['o_tMargin'];
                $this->lMargin = $tpl['o_lMargin'];
                $this->rMargin = $tpl['o_rMargin'];
                $this->h = $tpl['o_h'];
                $this->w = $tpl['o_w'];
                $this->SetAutoPageBreak($tpl['o_AutoPageBreak'], $tpl['o_bMargin']);
                $this->FontFamily = $tpl['o_FontFamily'];
                $this->FontStyle = $tpl['o_FontStyle'];
                $this->FontSizePt = $tpl['o_FontSizePt'];
                $this->FontSize = $tpl['o_FontSize'];
                $fontKey = $this->FontFamily . $this->FontStyle;
                if ($fontKey) {
                    $this->CurrentFont =& $this->fonts[$fontKey];
                }
                return $this->tpl;
            } else {
                return false;
            }
        }
        public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0)
        {
            if ($this->page <= 0) {
                throw new \LogicException('You have to add at least a page first!');
            }
            if (!isset($this->_tpls[$tplIdx])) {
                throw new \InvalidArgumentException('Template does not exist!');
            }
            if ($this->_inTpl) {
                $this->_res['tpl'][$this->tpl]['tpls'][$tplIdx] =& $this->_tpls[$tplIdx];
            }
            $tpl = $this->_tpls[$tplIdx];
            $_w = $tpl['w'];
            $_h = $tpl['h'];
            if ($x == null) {
                $x = 0;
            }
            if ($y == null) {
                $y = 0;
            }
            $x += $tpl['x'];
            $y += $tpl['y'];
            $wh = $this->getTemplateSize($tplIdx, $w, $h);
            $w = $wh['w'];
            $h = $wh['h'];
            $tplData = array('x' => $this->x, 'y' => $this->y, 'w' => $w, 'h' => $h, 'scaleX' => $w / $_w, 'scaleY' => $h / $_h, 'tx' => $x, 'ty' => $this->h - $y - $h, 'lty' => $this->h - $y - $h - ($this->h - $_h) * ($h / $_h));
            $this->_out(sprintf('q %.4F 0 0 %.4F %.4F %.4F cm', $tplData['scaleX'], $tplData['scaleY'], $tplData['tx'] * $this->k, $tplData['ty'] * $this->k));
            $this->_out(sprintf('%s%d Do Q', $this->tplPrefix, $tplIdx));
            $this->lastUsedTemplateData = $tplData;
            return array('w' => $w, 'h' => $h);
        }
        public function getTemplateSize($tplIdx, $w = 0, $h = 0)
        {
            if (!isset($this->_tpls[$tplIdx])) {
                return false;
            }
            $tpl = $this->_tpls[$tplIdx];
            $_w = $tpl['w'];
            $_h = $tpl['h'];
            if ($w == 0 && $h == 0) {
                $w = $_w;
                $h = $_h;
            }
            if ($w == 0) {
                $w = $h * $_w / $_h;
            }
            if ($h == 0) {
                $h = $w * $_h / $_w;
            }
            return array('w' => $w, 'h' => $h);
        }
        public function SetFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::SetFont'), $args);
            }
            parent::SetFont($family, $style, $size);
            $fontkey = $this->FontFamily . $this->FontStyle;
            if ($this->_inTpl) {
                $this->_res['tpl'][$this->tpl]['fonts'][$fontkey] =& $this->fonts[$fontkey];
            } else {
                $this->_res['page'][$this->page]['fonts'][$fontkey] =& $this->fonts[$fontkey];
            }
        }
        public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array())
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::Image'), $args);
            }
            $ret = parent::Image($file, $x, $y, $w, $h, $type, $link);
            if ($this->_inTpl) {
                $this->_res['tpl'][$this->tpl]['images'][$file] =& $this->images[$file];
            } else {
                $this->_res['page'][$this->page]['images'][$file] =& $this->images[$file];
            }
            return $ret;
        }
        public function AddPage($orientation = '', $format = '', $rotationOrKeepmargins = false, $tocpage = false)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::AddPage'), $args);
            }
            if ($this->_inTpl) {
                throw new \LogicException('Adding pages in templates is not possible!');
            }
            parent::AddPage($orientation, $format, $rotationOrKeepmargins);
        }
        public function Link($x, $y, $w, $h, $link, $spaces = 0)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::Link'), $args);
            }
            if ($this->_inTpl) {
                throw new \LogicException('Using links in templates is not posible!');
            }
            parent::Link($x, $y, $w, $h, $link);
        }
        public function AddLink()
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::AddLink'), $args);
            }
            if ($this->_inTpl) {
                throw new \LogicException('Adding links in templates is not possible!');
            }
            return parent::AddLink();
        }
        public function SetLink($link, $y = 0, $page = -1)
        {
            if (is_subclass_of($this, '\\TCPDF')) {
                $args = func_get_args();
                return call_user_func_array(array($this, '\\TCPDF::SetLink'), $args);
            }
            if ($this->_inTpl) {
                throw new \LogicException('Setting links in templates is not possible!');
            }
            parent::SetLink($link, $y, $page);
        }
        protected function _putformxobjects()
        {
            $filter = $this->compress ? '/Filter /FlateDecode ' : '';
            reset($this->_tpls);
            foreach ($this->_tpls as $tplIdx => $tpl) {
                $this->_newobj();
                $this->_tpls[$tplIdx]['n'] = $this->n;
                $this->_out('<<' . $filter . '/Type /XObject');
                $this->_out('/Subtype /Form');
                $this->_out('/FormType 1');
                $this->_out(sprintf('/BBox [%.2F %.2F %.2F %.2F]', $tpl['x'] * $this->k, -$tpl['y'] * $this->k, ($tpl['w'] + $tpl['x']) * $this->k, ($tpl['h'] - $tpl['y']) * $this->k));
                if ($tpl['x'] != 0 || $tpl['y'] != 0) {
                    $this->_out(sprintf('/Matrix [1 0 0 1 %.5F %.5F]', -$tpl['x'] * $this->k * 2, $tpl['y'] * $this->k * 2));
                }
                $this->_out('/Resources ');
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
                    if (isset($res['images']) || isset($res['tpls'])) {
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
                }
                $this->_out('>>');
                $buffer = $this->compress ? gzcompress($tpl['buffer']) : $tpl['buffer'];
                $this->_out('/Length ' . strlen($buffer) . ' >>');
                $this->_putstream($buffer);
                $this->_out('endobj');
            }
        }
        public function _putimages()
        {
            parent::_putimages();
            $this->_putformxobjects();
        }
        public function _putxobjectdict()
        {
            parent::_putxobjectdict();
            foreach ($this->_tpls as $tplIdx => $tpl) {
                $this->_out(sprintf('%s%d %d 0 R', $this->tplPrefix, $tplIdx, $tpl['n']));
            }
        }
        public function _out($s)
        {
            if ($this->state == 2 && $this->_inTpl) {
                $this->_tpls[$this->tpl]['buffer'] .= $s . '
';
            } else {
                parent::_out($s);
            }
        }
    }
}