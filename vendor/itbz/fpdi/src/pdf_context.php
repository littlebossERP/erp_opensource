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
    class pdf_context
    {
        protected $_mode = 0;
        public $file;
        public $buffer;
        public $offset;
        public $length;
        public $stack;
        public function __construct(&$f)
        {
            $this->file =& $f;
            if (is_string($this->file)) {
                $this->_mode = 1;
            }
            $this->reset();
        }
        public function getPos()
        {
            if ($this->_mode == 0) {
                return ftell($this->file);
            } else {
                return 0;
            }
        }
        public function reset($pos = null, $l = 100)
        {
            if ($this->_mode == 0) {
                if (!is_null($pos)) {
                    fseek($this->file, $pos);
                }
                $this->buffer = $l > 0 ? fread($this->file, $l) : '';
                $this->length = strlen($this->buffer);
                if ($this->length < $l) {
                    $this->increaseLength($l - $this->length);
                }
            } else {
                $this->buffer = $this->file;
                $this->length = strlen($this->buffer);
            }
            $this->offset = 0;
            $this->stack = array();
        }
        public function ensureContent()
        {
            if ($this->offset >= $this->length - 1) {
                return $this->increaseLength();
            } else {
                return true;
            }
        }
        public function increaseLength($l = 100)
        {
            if ($this->_mode == 0 && feof($this->file)) {
                return false;
            } else {
                if ($this->_mode == 0) {
                    $totalLength = $this->length + $l;
                    do {
                        $toRead = $totalLength - $this->length;
                        if ($toRead < 1) {
                            break;
                        }
                        $this->buffer .= fread($this->file, $toRead);
                    } while (($this->length = strlen($this->buffer)) != $totalLength && !feof($this->file));
                    return true;
                } else {
                    return false;
                }
            }
        }
    }
}