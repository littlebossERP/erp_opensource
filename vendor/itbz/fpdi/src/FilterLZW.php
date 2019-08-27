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
    class FilterLZW
    {
        protected $_sTable = array();
        protected $_data = null;
        protected $_dataLength = 0;
        protected $_tIdx;
        protected $_bitsToGet = 9;
        protected $_bytePointer;
        protected $_bitPointer;
        protected $_nextData = 0;
        protected $_nextBits = 0;
        protected $_andTable = array(511, 1023, 2047, 4095);
        public function decode($data)
        {
            if ($data[0] == 0 && $data[1] == 1) {
                throw new \Exception('LZW flavour not supported.');
            }
            $this->_initsTable();
            $this->_data = $data;
            $this->_dataLength = strlen($data);
            $this->_bytePointer = 0;
            $this->_bitPointer = 0;
            $this->_nextData = 0;
            $this->_nextBits = 0;
            $oldCode = 0;
            $unCompData = '';
            while (($code = $this->_getNextCode()) != 257) {
                if ($code == 256) {
                    $this->_initsTable();
                    $code = $this->_getNextCode();
                    if ($code == 257) {
                        break;
                    }
                    if (!isset($this->_sTable[$code])) {
                        throw new \Exception('Error while decompression LZW compressed data.');
                    }
                    $unCompData .= $this->_sTable[$code];
                    $oldCode = $code;
                } else {
                    if ($code < $this->_tIdx) {
                        $string = $this->_sTable[$code];
                        $unCompData .= $string;
                        $this->_addStringToTable($this->_sTable[$oldCode], $string[0]);
                        $oldCode = $code;
                    } else {
                        $string = $this->_sTable[$oldCode];
                        $string = $string . $string[0];
                        $unCompData .= $string;
                        $this->_addStringToTable($string);
                        $oldCode = $code;
                    }
                }
            }
            return $unCompData;
        }
        protected function _initsTable()
        {
            $this->_sTable = array();
            for ($i = 0; $i < 256; $i++) {
                $this->_sTable[$i] = chr($i);
            }
            $this->_tIdx = 258;
            $this->_bitsToGet = 9;
        }
        protected function _addStringToTable($oldString, $newString = '')
        {
            $string = $oldString . $newString;
            $this->_sTable[$this->_tIdx++] = $string;
            if ($this->_tIdx == 511) {
                $this->_bitsToGet = 10;
            } else {
                if ($this->_tIdx == 1023) {
                    $this->_bitsToGet = 11;
                } else {
                    if ($this->_tIdx == 2047) {
                        $this->_bitsToGet = 12;
                    }
                }
            }
        }
        protected function _getNextCode()
        {
            if ($this->_bytePointer == $this->_dataLength) {
                return 257;
            }
            $this->_nextData = $this->_nextData << 8 | ord($this->_data[$this->_bytePointer++]) & 255;
            $this->_nextBits += 8;
            if ($this->_nextBits < $this->_bitsToGet) {
                $this->_nextData = $this->_nextData << 8 | ord($this->_data[$this->_bytePointer++]) & 255;
                $this->_nextBits += 8;
            }
            $code = $this->_nextData >> $this->_nextBits - $this->_bitsToGet & $this->_andTable[$this->_bitsToGet - 9];
            $this->_nextBits -= $this->_bitsToGet;
            return $code;
        }
        public function encode($in)
        {
            throw new \LogicException('LZW encoding not implemented.');
        }
    }
}