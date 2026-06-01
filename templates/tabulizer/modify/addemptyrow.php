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

class TabulizerModifyAddemptyrow {
    function modify(&$rows, $args = null) {
        if (empty($rows)) {
            return;
        }
        $num_of_rows = count($rows);
        $num_of_columns = count($rows[1]);
        if (empty($args)) return;
        else $empty_row_id = intval($args);

        // sanity check
        if (($empty_row_id <1)||($empty_row_id>$num_of_rows)) return;

        // construct empty row
        $empty_row = array();
        for ($k=1;$k<=$num_of_columns;$k++) $empty_row[$k] = '';

        $k = 1;
        $modified_rows = array();
        for ($i=1;$i<=$num_of_rows;$i++) {
            if ($i == $empty_row_id) {
                $modified_rows[$k++] = $empty_row;
            }
            $modified_rows[$k++] = $rows[$i];
        }
        $rows = $modified_rows;
    }
}
?>