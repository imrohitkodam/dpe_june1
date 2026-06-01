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
class JFormFieldHikashopcategories extends JFormField {
	protected $type = 'Hikashopcategories';
	public function getInput() { // added class hidden in fieldset
		$db = JFactory::getDbo();
		$db->getQuery(true);
		$query = "SHOW TABLES LIKE '#__hikashop_category'";
		$db->setQuery($query);
		    try
		    {
		        // If it fails, it will throw a RuntimeException
		        $result = $db->loadResult(); 
		        $db1 = JFactory::getDbo();
				$query7 = $db1->getQuery(true);
				$query7
	            ->select('category_id as value, category_name as title')
	            ->from('#__hikashop_category')
	            ->where('category_published=1 AND category_type="product"');
	            $db1->setQuery($query7);
	            $db1->execute();
	            $num_rows = $db1->getNumRows();
	            $results7 = $db1->loadObjectList();

	            $options = array();
		        if($results7!=''){
		            foreach ($results7 as $list) {
		                $options[] = array(
		                    'id'   => $list->value,
		                    'id'   => $list->value,
		                    'name' => $list->title
		                );
		            }
		        }
		        $attribs = 'multiple="multiple" class="multipleCategories"';
	        	return JHtml::_('select.genericlist', $options, 'jform[params][hikashopcatid][]', $attribs, 'id', 'name', $this->value, $this->id);
		    }
		    catch (RuntimeException $e)
		    {
		        $search_in_hikashop = $this->form->getValue('search_in_hikashop', 'params');
		    	if($search_in_hikashop){
		    		JFactory::getApplication()->enqueueMessage(JText::_('MOD_SEARCHAJAX_FIELD_HIKASHOP_NOT_EXITS'), 'error');
		    	}
		        $options = array();
		        $attribs = 'multiple="multiple" class="multipleCategories"';
			    return JHtml::_('select.genericlist', $options, 'jform[params][hikashopcatid][]', $attribs, 'id', 'name', $this->value, $this->id);
		    }
		
	}
}