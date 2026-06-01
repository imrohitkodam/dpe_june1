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

class TabulizerModifyReplaceemptycell {
    function modify(&$rows, $args = null) {
        if (empty($rows)) {
            return;
        }
        $replace_str = (isset($args))?$args:'&nbsp;';
        foreach ($rows as &$row) {
            foreach ($row as &$cell) {
                if ($cell == '') $cell = $replace_str;
            }
        }
    }
}
?>