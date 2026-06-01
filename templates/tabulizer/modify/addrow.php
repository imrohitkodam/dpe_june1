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

class TabulizerModifyAddrow {
    function modify(&$rows, $args = null) {
        if (empty($rows)) {
            return;
        }
        $num_of_rows = count($rows);
        $num_of_columns = count($rows[1]);
        if (empty($args)) return;

        list($row_id, $delimiter, $columns_str) = explode(':',$args,3);

        // sanity check
        if (($row_id <1)||($row_id>$num_of_rows)) return;
        if (empty($delimiter)) return;
        if (empty($columns_str)) return;

        $columns = explode($delimiter,$columns_str,$num_of_columns);
        if (count($columns)!=$num_of_columns) return;

        // construct empty row
        $empty_row = array();
        for ($k=1;$k<=$num_of_columns;$k++) $empty_row[$k] = '';

        $k = 1;
        $modified_rows = array();
        for ($i=1;$i<=$num_of_rows;$i++) {
            if ($i == $row_id) {
                $modified_rows[$k++] = $columns;
            }
            $modified_rows[$k++] = $rows[$i];
        }
        $rows = $modified_rows;
    }
}
?>