<?php
/**
 * @version		6.6.0 tabulizer $
 * @package		tabulizer
 * @copyright	Copyright © 2011 - All rights reserved.
 * @license		GNU/GPL
 * @author		Dimitrios Mourloukos
 * @author mail	info@alterora.gr
 * @website		www.tabulizer.com
 * 
 */


// no direct access
defined('_JEXEC') or die('Restricted access');

class TabulizerModifyConvertcharset {
    function modify(&$rows, $args = null) {
        if (empty($rows)) {
            return;
        }
        if (isset($args)) {
            $encoding = trim(strtoupper($args));
        } else {
            if (function_exists(mb_detect_encoding)) {
                $num_of_rows = count($rows);
                $num_of_columns = count($rows[1]);
                $sample_text = '';
                $ic = ($num_of_rows < 10)?$num_of_rows:10;
                for ($i = 1; $i<$ic;$i++) {
                    for ($j=1;$j<=$num_of_columns;$j++) {
                        if (isset($rows[$i][$j])) $sample_text .= $rows[$i][$j];
                    }
                }
                $encoding = mb_detect_encoding($sample_text, mb_detect_order(), true);
            } else {
                $encoding = 'ISO-8859-1//TRANSLIT';
            }
        }
        if (function_exists('iconv')) {
            foreach ($rows as &$row) {
                foreach ($row as &$cell) {
                    if ($cell != '') $cell = iconv($encoding, "UTF-8", $cell);
                }
            }
        } else if (function_exists('mb_convert_encoding')) {
            foreach ($rows as &$row) {
                foreach ($row as &$cell) {
                    if ($cell != '') $cell = mb_convert_encoding($cell, "UTF-8", $encoding);
                }
            }
        } else {
            echo "No PHP character conversion library was found. Please try to install either iconv or MBString.";
        }
    }
}
?>