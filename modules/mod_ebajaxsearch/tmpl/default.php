<?php 
/**
 * @package Module EB Ajax Search for Joomla!
 * @version 1.52: mod_ebajaxsearch.php Dec 2023
 * @author url: https://www/extnbakers.com
 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
**/
defined('_JEXEC') or die; 

if(JVERSION >= 4){
    require JModuleHelper::getLayoutPath('mod_ebajaxsearch', $params->get('layout', 'default') . '_4');
} else {
    require JModuleHelper::getLayoutPath('mod_ebajaxsearch', $params->get('layout', 'default') . '_3');
}
// exit();

?>