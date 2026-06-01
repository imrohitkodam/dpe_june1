<?php 
/**
 * @package Module EB Ajax Search for Joomla!
 * @version 1.52: mod_ebajaxsearch.php Dec 2023
 * @author url: https://www/extnbakers.com
 * @copyright Copyright (C) 2022 extnbakers.com. All rights reserved.
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html 
**/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
class JFormFieldK2categories extends JFormField {
	protected $type = 'K2categories';
	
	public function getInput() { // added class hidden in fieldset
		if(JVERSION < 4){			require_once dirname(__FILE__) . './../helper.php';		}
		$db = JFactory::getDbo();
		$db->getQuery(true);
		$query = "SHOW TABLES LIKE '#__k2_categories'";
		$db->setQuery($query);
		    try
		    {
		        // If it fails, it will throw a RuntimeException
		        $result = $db->loadResult(); 
		        $db1 = JFactory::getDbo();
				$query6 = $db1->getQuery(true);
				$query6
	            ->select('id as value, name as title')
	            ->from('#__k2_categories')
	            ->where('published=1');
	            $db1->setQuery($query6);
	            $db1->execute();
	            $num_rows = $db1->getNumRows();
	            $results6 = $db1->loadObjectList();
				
	            $options = array();
		        if($results6!=''){
		            foreach ($results6 as $list) {
		                $options[] = array(
		                    'id'   => $list->value,
		                    'id'   => $list->value,
		                    'name' => $list->title
		                );
		            }
		        }
		        $attribs = 'multiple="multiple" class="multipleCategories"';
	        	return JHtml::_('select.genericlist', $options, 'jform[params][k2catid][]', $attribs, 'id', 'name', $this->value, $this->id);
		    }
		    catch (RuntimeException $e)
		    {
		    	$search_in_k2 = $this->form->getValue('search_in_k2', 'params');
		    	if($search_in_k2){
		    		JFactory::getApplication()->enqueueMessage(JText::_('MOD_SEARCHAJAX_FIELD_K2_NOT_EXITS'), 'error');
		    	}
		        
		        $options = array();
		        $attribs = 'multiple="multiple" class="multipleCategories"';
			    return JHtml::_('select.genericlist', $options, 'jform[params][k2catid][]', $attribs, 'id', 'name', $this->value, $this->id);
		    }
		
	}
}