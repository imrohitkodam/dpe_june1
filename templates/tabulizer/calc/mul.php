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
defined( '_JEXEC' ) or die( 'Restricted access' );

class TabulizerCalcMul {
	function calc($row_id, $column_id, $values, $custom_arg) {				
		$sum = null;
		$count = 0;				
				
		if (!empty($values)) {
			$sum = 1;
			foreach ($values as $coord => $value) {				
				if (!empty($custom_arg)) {					
					list($value_row_id, $value_column_id) = explode(',',$coord);
					switch ($custom_arg) {
						case 'above':
							$id = intval($value_row_id);
							if ($id >= $row_id) continue 2; 
							break;
						case 'below':
							$id = intval($value_row_id);
							if ($id <= $row_id) continue 2;
							break;
						case 'left':
							$id = intval($value_column_id);
							if ($id >= $row_id) continue 2;
							break;
						case 'right':
							$id = intval($value_column_id);
							if ($id <= $row_id) continue 2;
							break;
						default:
							// unknow case
							continue 2;								
					}
				}								
				
				$value = TabulizerMath::getNumericValue($value);
				if (is_numeric($value)) {
					$sum *= $value;
					$count++;
				}
			}
		}
		
		if ($count) {			
			return $sum;
		} else {
			return null;
		}				
				
	}
}
?>