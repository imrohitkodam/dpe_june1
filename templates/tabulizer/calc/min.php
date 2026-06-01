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

class TabulizerCalcMin {
	function calc($row_id, $column_id, $values, $custom_arg) {				
		$sum = null;
		$numeric_values = array();				
				
		if (!empty($values)) {
			$sum = 0;			
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
					$numeric_values[] = $value;
				}
			}			
		}				
		
		if (count($numeric_values)) {
			$value = min($numeric_values);
			return $value;
		} else {
			return null;
		}		
	}
}
?>