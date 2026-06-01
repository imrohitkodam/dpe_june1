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
class JFormFieldVMcategories extends JFormField {
	protected $type = 'VMcategories';
	
	public function getInput() { // added class hidden in fieldset
		if(JVERSION < 4){			require_once dirname(__FILE__) . './../helper.php';		}		
		
		if(file_exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'config.php'))		{      				require(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_virtuemart'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'config.php'); 				if(JVERSION < 4){										VmConfig::loadConfig(true);					$lsfs = VmConfig::get('vmDefLang'); 				}else{					$lsfs = VmConfig::$jDefLangTag;  				}								if($lsfs == ''){					$lsfs = "en-GB";				}				$lower_language =  strtolower($lsfs);				$correct_language = str_replace("-","_",$lower_language);								
		        $db1 = JFactory::getDbo();
				$query6 = $db1->getQuery(true);
				$query6
				->select(array('ca.virtuemart_category_id as value', 'ca.category_name as title'))
				->from($db1->quoteName('#__virtuemart_categories_'.$correct_language).'AS ca')
				->leftJoin($db1->quoteName('#__virtuemart_categories').'AS vc ON ca.virtuemart_category_id = vc.virtuemart_category_id')
				->where('vc.published=1');
	            $db1->setQuery($query6);
	            $db1->execute();
	            $num_rows = $db1->getNumRows();
	            $results6 = $db1->loadObjectList();
				//print_r($results6);
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
	        	return JHtml::_('select.genericlist', $options, 'jform[params][vmcatid][]', $attribs, 'id', 'name', $this->value, $this->id);
		}else{
		    	$search_in_vm = $this->form->getValue('search_in_vm', 'params');
		    	if($search_in_vm){
		    		JFactory::getApplication()->enqueueMessage(JText::_('MOD_SEARCHAJAX_FIELD_VIRTUEMART_NOT_EXITS'), 'error');
		    	}
		        
		        $options = array();
		        $attribs = 'multiple="multiple" class="multipleCategories"';
			    return JHtml::_('select.genericlist', $options, 'jform[params][vmcatid][]', $attribs, 'id', 'name', $this->value, $this->id);
		    }
		
	}
}